<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\RepoUser;

/**
 * Авторизация портала защищённого файлового хранилища. Полностью независима
 * от админ-панели (App\Core\Auth): использует собственные ключи сессии с
 * префиксом repo_*, свою таблицу repo_users и свой rate-limit namespace.
 * Один и тот же браузер может быть залогинен и в админку, и в портал
 * одновременно, не мешая друг другу.
 */
final class RepoAuth
{
    /**
     * @return array{status: string, retry_after?: int}
     */
    public static function attemptLogin(string $username, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifiers = self::loginIdentifiers($username, $ip);

        foreach ($identifiers as $identifier => $limit) {
            if (RateLimiter::tooManyAttempts($identifier, $limit)) {
                return ['status' => 'locked', 'retry_after' => RateLimiter::secondsUntilRetry($identifier)];
            }
        }

        $user = RepoUser::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            foreach (array_keys($identifiers) as $identifier) {
                RateLimiter::recordAttempt($identifier, false);
            }
            return ['status' => 'invalid'];
        }

        if ((int) $user['is_active'] !== 1) {
            foreach (array_keys($identifiers) as $identifier) {
                RateLimiter::recordAttempt($identifier, false);
            }
            return ['status' => 'disabled'];
        }

        RateLimiter::clearAttempts(array_key_first($identifiers));
        RateLimiter::clearAttempts(array_key_last($identifiers));

        session_regenerate_id(true);
        $_SESSION['repo_pending_user_id'] = (int) $user['id'];
        $_SESSION['repo_pending_since'] = time();

        $totpOn = (int) $user['totp_enabled'] === 1;
        $telegramOn = self::telegramChannelAvailable($user);

        if ($totpOn || $telegramOn) {
            $sent = $telegramOn && self::sendTelegramCode($user);
            if ($telegramOn && !$sent && !$totpOn) {
                // Telegram — единственный второй фактор, а код не ушёл:
                // не оставляем пользователя на шаге, который нельзя пройти.
                self::clearPending();

                return ['status' => 'send_failed'];
            }
            $_SESSION['repo_2fa_totp'] = $totpOn;
            $_SESSION['repo_2fa_telegram'] = $sent;

            return ['status' => 'needs_2fa'];
        }

        self::establishSession($user);

        return ['status' => 'ok'];
    }

    /** Привязан Telegram и настроен бот — второй фактор через Telegram доступен. */
    private static function telegramChannelAvailable(array $user): bool
    {
        return TelegramBot::isConfigured() && (int) ($user['telegram_chat_id'] ?? 0) !== 0;
    }

    /**
     * @return array<string, int> identifier => max attempts
     */
    private static function loginIdentifiers(string $username, string $ip): array
    {
        $account = mb_strtolower(trim($username));
        $base = max(1, (int) Config::get('security.login_max_attempts', 5));

        return [
            'repo_login|pair|' . $ip . '|' . $account => $base,
            'repo_login|ip|' . $ip => max(25, $base * 5),
            'repo_login|account|' . $account => max(10, $base * 2),
        ];
    }

    /** Генерирует одноразовый код, хэш — в сессию, код — в Telegram. */
    private static function sendTelegramCode(array $user): bool
    {
        $code = (string) random_int(100000, 999999);
        $_SESSION['repo_tg_code_hash'] = hash('sha256', $code);
        $_SESSION['repo_tg_code_expires'] = time() + 300;

        $safeCode = htmlspecialchars($code, ENT_QUOTES);
        $text = "<b>Код входа в файловый портал</b>\n\n"
            . "Код: <code>{$safeCode}</code>\n\n"
            . "⏱ <i>Действует 5 минут. Никому не сообщайте этот код.</i>";

        return TelegramBot::sendMessage(
            (int) $user['telegram_chat_id'],
            $text
        );
    }

    /** Повторная отправка кода в Telegram (не чаще 3 раз за 5 минут с IP). */
    public static function resendTelegramCode(): bool
    {
        $userId = self::pendingUserId();
        if ($userId === null || empty($_SESSION['repo_2fa_telegram'])) {
            return false;
        }
        if (!RateLimiter::throttle('repo_2fa_resend', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 3, 5, false)) {
            return false;
        }

        $user = RepoUser::findById($userId);
        if (!$user || !self::telegramChannelAvailable($user)) {
            return false;
        }

        return self::sendTelegramCode($user);
    }

    /** Каналы второго фактора текущего ожидающего входа (для вьюхи). */
    public static function pendingChannels(): array
    {
        return [
            'totp' => !empty($_SESSION['repo_2fa_totp']),
            'telegram' => !empty($_SESSION['repo_2fa_telegram']),
        ];
    }

    public static function completeTwoFactor(string $code): bool
    {
        $userId = $_SESSION['repo_pending_user_id'] ?? null;
        if (!$userId || (time() - (int) ($_SESSION['repo_pending_since'] ?? 0)) > 300) {
            self::clearPending();
            return false;
        }

        $user = RepoUser::findById((int) $userId);
        if (!$user || (int) $user['is_active'] !== 1) {
            self::clearPending();
            return false;
        }

        $identifier = 'repo2fa|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . mb_strtolower($user['username']);
        if (RateLimiter::tooManyAttempts($identifier)) {
            return false;
        }

        // Принимается код любого включённого канала: TOTP из приложения
        // или одноразовый код, отправленный в Telegram.
        $valid = false;
        if ((int) $user['totp_enabled'] === 1 && !empty($user['totp_secret'])) {
            $valid = TOTP::verify($user['totp_secret'], $code);
        }
        if (!$valid && !empty($_SESSION['repo_2fa_telegram'])) {
            $hash = (string) ($_SESSION['repo_tg_code_hash'] ?? '');
            $fresh = time() <= (int) ($_SESSION['repo_tg_code_expires'] ?? 0);
            $valid = $hash !== '' && $fresh && hash_equals($hash, hash('sha256', $code));
        }

        if (!$valid) {
            RateLimiter::recordAttempt($identifier, false);
            return false;
        }

        RateLimiter::clearAttempts($identifier);
        self::clearPending();
        self::establishSession($user);

        return true;
    }

    public static function pendingUserId(): ?int
    {
        $id = $_SESSION['repo_pending_user_id'] ?? null;

        return $id ? (int) $id : null;
    }

    private static function establishSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['repo_user_id'] = (int) $user['id'];
        $_SESSION['repo_username'] = $user['username'];
        $_SESSION['repo_authenticated_at'] = time();
        $_SESSION['repo_fingerprint'] = self::fingerprint();
        $_SESSION['repo_password_version'] = self::passwordVersion($user);

        RepoUser::touchLastLogin((int) $user['id']);

        Logger::security('Успешный вход в файловый портал', [
            'user' => (string) $user['username'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $subnet = '';
        if (str_contains($ip, '.')) {
            $octets = explode('.', $ip);
            $subnet = ($octets[0] ?? '') . '.' . ($octets[1] ?? '');
        } elseif (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            $subnet = ($parts[0] ?? '') . ':' . ($parts[1] ?? '');
        }

        return hash('sha256', 'repo|' . $ua . '|' . $subnet);
    }

    private static function clearPending(): void
    {
        unset(
            $_SESSION['repo_pending_user_id'],
            $_SESSION['repo_pending_since'],
            $_SESSION['repo_2fa_totp'],
            $_SESSION['repo_2fa_telegram'],
            $_SESSION['repo_tg_code_hash'],
            $_SESSION['repo_tg_code_expires']
        );
    }

    public static function check(): bool
    {
        if (empty($_SESSION['repo_user_id'])) {
            return false;
        }

        if (!isset($_SESSION['repo_fingerprint']) || !hash_equals($_SESSION['repo_fingerprint'], self::fingerprint())) {
            self::logout();
            return false;
        }

        // Отзыв доступа администратором: деактивированный аккаунт немедленно
        // теряет сессию при следующем запросе.
        try {
            $user = RepoUser::findById((int) $_SESSION['repo_user_id']);
            if ($user === null
                || (int) $user['is_active'] !== 1
                || empty($_SESSION['repo_password_version'])
                || !hash_equals((string) $_SESSION['repo_password_version'], self::passwordVersion($user))) {
                self::logout();
                return false;
            }
        } catch (\Throwable $e) {
            Logger::error('RepoAuth check failed: ' . $e->getMessage());
        }

        return true;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['repo_user_id']) ? (int) $_SESSION['repo_user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return RepoUser::findById((int) $_SESSION['repo_user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /repo/login');
            exit;
        }
    }

    public static function logout(): void
    {
        unset(
            $_SESSION['repo_user_id'],
            $_SESSION['repo_username'],
            $_SESSION['repo_authenticated_at'],
            $_SESSION['repo_fingerprint'],
            $_SESSION['repo_password_version'],
            $_SESSION['repo_pending_user_id'],
            $_SESSION['repo_pending_since'],
            $_SESSION['repo_totp_setup_secret'],
            $_SESSION['repo_2fa_totp'],
            $_SESSION['repo_2fa_telegram'],
            $_SESSION['repo_tg_code_hash'],
            $_SESSION['repo_tg_code_expires'],
            $_SESSION['repo_tg_link_code']
        );
    }

    private static function passwordVersion(array $user): string
    {
        return hash('sha256', (string) ($user['password_hash'] ?? ''));
    }
}

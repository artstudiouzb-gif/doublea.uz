<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\SessionRegistry;
use App\Models\User;

final class Auth
{
    /** Срок жизни кода подтверждения входа, секунд. */
    private const CODE_TTL = 300;

    /**
     * Вход по паролю с подтверждением одноразовым кодом через Telegram
     * (официальный канал Verification Codes, Telegram Gateway API).
     * Другие методы 2FA (TOTP, backup-коды) для админки отключены.
     *
     * Статусы: needs_code — код отправлен, ждём подтверждения;
     * setup_required — пароль верен, но второй фактор ещё не настроен;
     * send_failed — шлюз не принял сообщение; invalid/locked — как раньше.
     *
     * @return array{status: string, retry_after?: int}
     */
    public static function attemptLogin(string $username, string $password): array
    {
        Session::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifiers = self::loginIdentifiers($username, $ip);

        foreach ($identifiers as $identifier => $limit) {
            if (RateLimiter::tooManyAttempts($identifier, $limit)) {
                return ['status' => 'locked', 'retry_after' => RateLimiter::secondsUntilRetry($identifier)];
            }
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            foreach (array_keys($identifiers) as $identifier) {
                RateLimiter::recordAttempt($identifier, false);
            }
            return ['status' => 'invalid'];
        }

        // Успешный вход очищает корзины аккаунта и конкретной пары. Корзина
        // IP сохраняется до истечения окна, чтобы известный пароль одного
        // аккаунта не позволял сбрасывать защиту от password spraying.
        RateLimiter::clearAttempts(array_key_first($identifiers));
        RateLimiter::clearAttempts(array_key_last($identifiers));

        session_regenerate_id(true);

        $totpOn = self::totpChannelAvailable($user);
        $telegramOn = self::telegramChannelAvailable($user);

        if (!$totpOn && !$telegramOn) {
            // Разрешаем только ограниченную onboarding-сессию: middleware во
            // front controller пропустит её лишь в профиль/настройки, пока
            // пользователь не подключит второй фактор. Админка не должна тихо
            // деградировать до одного пароля.
            self::establishSession($user);
            $_SESSION['2fa_setup_required'] = true;
            Logger::security('Вход ограничен до настройки второго фактора', [
                'user' => (string) $user['username'],
                'ip' => $ip,
            ]);

            return ['status' => 'setup_required'];
        }

        $_SESSION['pending_user_id'] = (int) $user['id'];
        $_SESSION['pending_since'] = time();

        // Код в Telegram отправляем, только если этот канал вообще подключён.
        // Приложение-аутентификатор работает офлайн, поэтому недоступный
        // Telegram больше не запирает вход.
        $sent = $telegramOn && self::sendLoginCode($user);
        if ($telegramOn && !$sent && !$totpOn) {
            self::clearPending();

            return ['status' => 'send_failed'];
        }

        $_SESSION['pending_totp'] = $totpOn;
        $_SESSION['pending_telegram'] = $sent;

        return ['status' => 'needs_code'];
    }

    /**
     * Доступен ли пользователю хоть один канал доставки кода: бесплатный
     * бот (telegram_chat_id) или платный шлюз Verification Codes (телефон).
     */
    private static function hasCodeChannel(array $user): bool
    {
        return self::totpChannelAvailable($user) || self::telegramChannelAvailable($user);
    }

    /**
     * Приложение-аутентификатор: коды считаются на устройстве, поэтому канал
     * работает без сети и не зависит от чужого сервиса.
     */
    private static function totpChannelAvailable(array $user): bool
    {
        return (int) ($user['totp_enabled'] ?? 0) === 1 && trim((string) ($user['totp_secret'] ?? '')) !== '';
    }

    /** Бесплатный бот (telegram_chat_id) или платный шлюз Verification Codes. */
    private static function telegramChannelAvailable(array $user): bool
    {
        if (TelegramBot::isConfigured() && (int) ($user['telegram_chat_id'] ?? 0) > 0) {
            return true;
        }

        return TelegramGateway::isConfigured() && trim((string) ($user['phone'] ?? '')) !== '';
    }

    /**
     * @return array<string, int> identifier => max attempts
     */
    private static function loginIdentifiers(string $username, string $ip): array
    {
        $account = mb_strtolower(trim($username));
        $base = max(1, (int) Config::get('security.login_max_attempts', 5));

        return [
            'admin_login|pair|' . $ip . '|' . $account => $base,
            'admin_login|ip|' . $ip => max(25, $base * 5),
            'admin_login|account|' . $account => max(10, $base * 2),
        ];
    }

    /**
     * Генерирует одноразовый код, сохраняет его хэш в сессии и отправляет в
     * Telegram. Приоритет — бесплатный бот; иначе платный шлюз (канал
     * Verification Codes). Используется при входе и при повторной отправке.
     */
    private static function sendLoginCode(array $user): bool
    {
        $code = (string) random_int(100000, 999999);
        $_SESSION['pending_code_hash'] = hash('sha256', $code);
        $_SESSION['pending_code_expires'] = time() + self::CODE_TTL;

        $chatId = (int) ($user['telegram_chat_id'] ?? 0);
        if (TelegramBot::isConfigured() && $chatId > 0) {
            return TelegramBot::sendLoginCode($chatId, $code);
        }

        return TelegramGateway::sendCode((string) $user['phone'], $code);
    }

    /**
     * Повторная отправка кода (по кнопке на странице подтверждения).
     * Ограничена: не чаще 3 раз за 5 минут с одного IP.
     */
    public static function resendCode(): bool
    {
        Session::start();
        $userId = $_SESSION['pending_user_id'] ?? null;
        if (!$userId) {
            return false;
        }
        if (!RateLimiter::throttle('2fa_resend', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 3, 5)) {
            return false;
        }

        // Пересылать нечего, если код не отправляют, а считают в приложении.
        $user = User::findById((int) $userId);
        if (!$user || !self::telegramChannelAvailable($user)) {
            return false;
        }

        if (!self::sendLoginCode($user)) {
            return false;
        }

        $_SESSION['pending_telegram'] = true;

        return true;
    }

    /**
     * Проверка кода второго фактора: TOTP из приложения (считается на
     * устройстве) или одноразовый код из Telegram (hash_equals с хэшем из
     * сессии, срок жизни 5 минут). Перебор ограничен RateLimiter.
     */
    public static function completeTwoFactor(string $code): bool
    {
        Session::start();
        $userId = $_SESSION['pending_user_id'] ?? null;
        if (!$userId || (time() - (int) ($_SESSION['pending_since'] ?? 0)) > self::CODE_TTL) {
            self::clearPending();
            return false;
        }

        $user = User::findById((int) $userId);
        if (!$user) {
            self::clearPending();
            return false;
        }

        $identifier = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|2fa|' . mb_strtolower($user['username']);
        if (RateLimiter::tooManyAttempts($identifier)) {
            return false;
        }

        // Принимается код любого включённого канала: из приложения-аутентифи-
        // катора или одноразовый, отправленный в Telegram.
        $code = preg_replace('/\s+/', '', $code) ?? '';
        $valid = false;
        if (!empty($_SESSION['pending_totp']) && trim((string) ($user['totp_secret'] ?? '')) !== '') {
            $valid = TOTP::verify((string) $user['totp_secret'], $code);
        }
        // Отправленный код проверяем и без метки канала: сессии, начатые до
        // появления TOTP, метки не знают, а настоящий секрет здесь — сам хэш.
        // Иначе обновление выбило бы всех, кто в этот момент вводил код.
        $expectedHash = (string) ($_SESSION['pending_code_hash'] ?? '');
        $codeExpired = $expectedHash !== '' && time() > (int) ($_SESSION['pending_code_expires'] ?? 0);
        if (!$valid && $expectedHash !== '' && !$codeExpired) {
            $valid = hash_equals($expectedHash, hash('sha256', $code));
        }

        if (!$valid) {
            // Одноразовый код протух — вход начинают заново. У приложения
            // срока годности нет, поэтому такой вход не сбрасываем.
            if ($codeExpired && empty($_SESSION['pending_totp'])) {
                self::clearPending();

                return false;
            }
            RateLimiter::recordAttempt($identifier, false);

            return false;
        }

        RateLimiter::clearAttempts($identifier);
        self::clearPending();
        self::establishSession($user);

        return true;
    }

    private static function establishSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['authenticated_at'] = time();
        $_SESSION['fingerprint'] = self::fingerprint();

        if (!empty($user['admin_lang'])) {
            Locale::set((string) $user['admin_lang']);
        }

        User::touchLastLogin((int) $user['id']);

        // Регистрируем сессию в реестре: даёт список устройств и мгновенный
        // серверный отзыв (страница «Мои сессии»).
        try {
            SessionRegistry::register(
                (int) $user['id'],
                session_id(),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        } catch (\Throwable $e) {
            Logger::error('SessionRegistry::register failed: ' . $e->getMessage());
        }

        Logger::security('Успешный вход в панель управления', [
            'user' => (string) $user['username'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        // Вероятностная очистка старых записей брутфорса и ротация логов.
        RateLimiter::garbageCollect();
    }

    /**
     * Фингерпринт клиента: хэш от User-Agent и первых двух октетов IP
     * (подсеть /16). Привязывает сессию к устройству/сети, затрудняя
     * использование украденного cookie с другого клиента, но не ломает
     * сессию при смене последнего октета динамического IP.
     */
    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $subnet = '';
        if (str_contains($ip, '.')) {
            $octets = explode('.', $ip);
            $subnet = ($octets[0] ?? '') . '.' . ($octets[1] ?? '');
        } elseif (str_contains($ip, ':')) {
            // IPv6: первые два хекстета.
            $parts = explode(':', $ip);
            $subnet = ($parts[0] ?? '') . ':' . ($parts[1] ?? '');
        }

        return hash('sha256', $ua . '|' . $subnet);
    }

    private static function clearPending(): void
    {
        unset(
            $_SESSION['pending_user_id'],
            $_SESSION['pending_since'],
            $_SESSION['pending_code_hash'],
            $_SESSION['pending_code_expires'],
            $_SESSION['pending_totp'],
            $_SESSION['pending_telegram']
        );
    }

    public static function check(): bool
    {
        // Без cookie сессии посетитель войти не мог — значит и заводить ему
        // сессию незачем. Раньше здесь стоял безусловный Session::start(), и
        // публичная страница выдавала Set-Cookie каждому анониму: шапка зовёт
        // AppToolbar::isVisible() → sessionUser() → check() на каждом рендере.
        // Побочный эффект был дороже: активная сессия делает ответ
        // некэшируемым (PublicResponseCache), поэтому общий HTTP-кэш публички
        // не работал вообще ни на одной странице.
        // Восстанавливать нечего: данных сессии в процессе нет, она не
        // запущена и cookie не прислана. Условие проверяет все три источника —
        // в CLI и тестах $_SESSION заполняют напрямую, там cookie не бывает.
        if (empty($_SESSION['user_id'])
            && session_status() !== PHP_SESSION_ACTIVE
            && !Session::hasCookie()) {
            return false;
        }

        Session::start();
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        // Защита от перехвата сессии: фингерпринт должен совпадать.
        if (!isset($_SESSION['fingerprint']) || !hash_equals($_SESSION['fingerprint'], self::fingerprint())) {
            Logger::security('Несовпадение фингерпринта сессии — принудительный выход', [
                'user' => (string) ($_SESSION['username'] ?? ''),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            self::logout();
            return false;
        }

        // Мгновенный серверный отзыв: сессия действительна, только пока её
        // строка присутствует в реестре. Удаление строки («выйти на этом
        // устройстве»/«везде»/смена пароля) немедленно завершает сессию.
        try {
            if (!SessionRegistry::exists((int) $_SESSION['user_id'], session_id())) {
                self::logout();
                return false;
            }
            // Обновляем «последнюю активность» не чаще раза в минуту.
            if ((time() - (int) ($_SESSION['sid_seen_at'] ?? 0)) > 60) {
                SessionRegistry::touch((int) $_SESSION['user_id'], session_id());
                $_SESSION['sid_seen_at'] = time();
            }
        } catch (\Throwable $e) {
            // Транзиентная ошибка БД не должна разлогинивать всех — фингерпринт
            // уже проверен; логируем и пропускаем.
            Logger::error('SessionRegistry check failed: ' . $e->getMessage());
        }

        return true;
    }

    public static function id(): ?int
    {
        Session::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return User::findById((int) $_SESSION['user_id']);
    }

    /**
     * Минимальный профиль из уже проверенной сессии. Подходит для интерфейса
     * и RBAC, где новый запрос к users на каждом рендере не нужен.
     *
     * @return array{id:int,username:string,role:string}|null
     */
    public static function sessionUser(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'username' => (string) ($_SESSION['username'] ?? ''),
            'role' => (string) ($_SESSION['role'] ?? 'editor'),
        ];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login');
            exit;
        }

        $user = self::user();
        if ($user) {
            $activeCodes = \App\Models\Language::activeCodes();
            $requestedCode = LocalePreference::requestedCode($_SERVER['REQUEST_URI'] ?? '', $activeCodes);

            if ($requestedCode !== null) {
                if (($user['admin_lang'] ?? '') !== $requestedCode) {
                    User::updateAdminLang((int) $user['id'], $requestedCode);
                }
                Locale::set($requestedCode);
            } elseif (!empty($user['admin_lang']) && in_array($user['admin_lang'], $activeCodes, true)) {
                Locale::set((string) $user['admin_lang']);
            }
        }
    }

    public static function role(): string
    {
        Session::start();
        return (string) ($_SESSION['role'] ?? 'editor');
    }

    /**
     * Какими каналами можно подтвердить текущий вход. Нужно странице ввода
     * кода: «код из Telegram» и «код из приложения» — разные инструкции.
     *
     * @return array{totp: bool, telegram: bool}
     */
    public static function pendingChannels(): array
    {
        Session::start();

        return [
            'totp' => !empty($_SESSION['pending_totp']),
            'telegram' => !empty($_SESSION['pending_telegram']),
        ];
    }

    public static function requiresTwoFactorSetup(): bool
    {
        Session::start();
        return !empty($_SESSION['2fa_setup_required']);
    }

    public static function completeTwoFactorSetup(): void
    {
        Session::start();
        unset($_SESSION['2fa_setup_required']);
    }

    /** Синхронизирует ограничения текущей сессии после изменения каналов 2FA. */
    public static function syncTwoFactorSetup(array $user): void
    {
        Session::start();
        if (self::hasCodeChannel($user)) {
            self::completeTwoFactorSetup();
            return;
        }

        $_SESSION['2fa_setup_required'] = true;
    }

    /**
     * Супер-администратор имеет полный доступ. Роль 'editor' ограничена
     * только управлением контентом. Исторически роль называлась 'admin' —
     * она эквивалентна super_admin.
     */
    public static function isSuperAdmin(): bool
    {
        return in_array(self::role(), ['super_admin', 'admin'], true);
    }

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            \App\Core\View::render('errors/403');
            exit;
        }
    }

    public static function logout(): void
    {
        Session::start();
        // Снимаем сессию с реестра активных сессий.
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                SessionRegistry::remove(session_id());
            }
        } catch (\Throwable $e) {
            Logger::error('SessionRegistry::remove failed: ' . $e->getMessage());
        }

        $_SESSION = [];

        // Всё, что ниже, имеет смысл только при живой сессии. Session::start()
        // ничего не открывает в CLI, а без этой проверки session_destroy()
        // писал в лог «Trying to destroy uninitialized session» — шум, за
        // которым легко пропустить настоящую ошибку.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}

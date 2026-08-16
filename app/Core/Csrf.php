<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(?string $token): bool
    {
        Session::start();
        if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            if (!self::verify($token)) {
                http_response_code(419);
                exit('Сессия устарела (CSRF token mismatch). Обновите страницу и попробуйте снова.');
            }
        }
    }

    /**
     * Honeypot-поля для публичных форм: невидимое текстовое поле, которое
     * заполняют боты, и скрытая метка времени рендера формы. Капча не нужна.
     */
    public static function honeypotField(): string
    {
        $ts = (string) time();

        return '<div class="csrf-honeypot" aria-hidden="true">'
            . '<label>Не заполняйте это поле<input type="text" name="hp_website" tabindex="-1" autocomplete="off" value=""></label>'
            . '</div>'
            . '<input type="hidden" name="hp_ts" value="' . htmlspecialchars($ts, ENT_QUOTES) . '">';
    }

    /**
     * Признак спам-отправки: honeypot заполнен либо форма отправлена
     * подозрительно быстро (менее 2 секунд после рендера).
     */
    public static function isSpam(int $minSeconds = 2): bool
    {
        $spam = false;
        if (trim((string) ($_POST['hp_website'] ?? '')) !== '') {
            $spam = true;
        } else {
            $ts = (int) ($_POST['hp_ts'] ?? 0);
            if ($ts <= 0 || (time() - $ts) < $minSeconds) {
                $spam = true;
            }
        }

        if ($spam) {
            // SECURITY-событие с длинным троттлингом — поток спама не заливает чат.
            Logger::security('Honeypot: подозрительная отправка формы отклонена', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'throttle' => 3600,
            ]);
        }

        return $spam;
    }
}

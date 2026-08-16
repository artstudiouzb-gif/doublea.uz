<?php

declare(strict_types=1);

namespace App\Core;

/** Централизованный ленивый запуск защищённой PHP-сессии. */
final class Session
{
    public static function hasCookie(): bool
    {
        $name = (string) Config::get('session.name', 'asc_session');
        return isset($_COOKIE[$name]) && is_string($_COOKIE[$name]) && $_COOKIE[$name] !== '';
    }

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = (int) Config::get('session.lifetime', 7200);
        $absoluteLifetime = max(
            $lifetime,
            (int) Config::get('session.absolute_lifetime', 28800)
        );
        session_name((string) Config::get('session.name', 'asc_session'));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => RequestUrl::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $now = time();
        $idleExpired = !empty($_SESSION['last_activity'])
            && $now - (int) $_SESSION['last_activity'] > $lifetime;
        $absoluteExpired = !empty($_SESSION['started_at'])
            && $now - (int) $_SESSION['started_at'] > $absoluteLifetime;
        if ($idleExpired || $absoluteExpired) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['started_at'] ??= $now;
        $_SESSION['last_activity'] = $now;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

/** Безопасное определение внешней схемы/host за reverse proxy. */
final class RequestUrl
{
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $forwardedRaw = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        if ($forwardedRaw === '') {
            return false;
        }

        // Forwarded-заголовки контролируются клиентом, если origin доступен
        // напрямую. Доверяем схеме только когда непосредственный peer входит
        // в явный allowlist reverse proxy. После ClientIp::applyTrustedProxy()
        // исходный peer сохраняется в ASDR_PROXY_ADDR.
        $peer = trim((string) ($_SERVER['ASDR_PROXY_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''));
        if ($peer === '' || !ClientIp::isTrustedProxy($peer)) {
            return false;
        }

        // Reverse proxy может передать цепочку значений; внешняя схема — первая.
        $forwarded = strtolower(trim(explode(',', $forwardedRaw)[0]));
        return $forwarded === 'https';
    }

    public static function origin(): string
    {
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\[[0-9a-f:]+\])(?::[0-9]{1,5})?$/i', $host)) {
            $host = 'localhost';
        }

        return (self::isHttps() ? 'https' : 'http') . '://' . $host;
    }
}

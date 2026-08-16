<?php

declare(strict_types=1);

namespace App\Core;

final class ExternalJsonService
{
    /**
     * Безопасно загружает JSON с внешнего URL с кэшированием и отказоустойчивостью.
     *
     * @param string $url Публичный URL JSON-API
     * @param int $ttlSec Время кэширования в секундах (по умолчанию: 15 минут)
     * @return mixed Декодированные данные (array/null)
     */
    public static function fetch(string $url, int $ttlSec = 900): mixed
    {
        if (!UrlGuard::isSafeRemote($url)) {
            return null;
        }

        $cacheDir = APP_ROOT . "/storage/cache/json";
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $cacheFile = $cacheDir . "/" . md5($url) . ".json";

        // Проверяем актуальный кэш
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSec) {
            $content = file_get_contents($cacheFile);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        }

        // Запрашиваем внешнюю систему через нативный HTTP-клиент
        $res = Http::getSafeRemote($url, [], 4);

        if ($res["status"] === 200 && !empty($res["body"])) {
            $decoded = json_decode((string) $res["body"], true);
            if ($decoded !== null) {
                @file_put_contents($cacheFile, (string) $res["body"]);
                return $decoded;
            }
        }

        // Откат (Fallback): если внешний сайт недоступен, отдаём устаревший кэш
        if (is_file($cacheFile)) {
            $content = file_get_contents($cacheFile);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        return null;
    }
}

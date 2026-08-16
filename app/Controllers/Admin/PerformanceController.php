<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\FrontendAssets;
use App\Core\SettingsValidator;
use App\Core\View;
use App\Models\Setting;

/**
 * Настройки производительности: управление кэшем страниц, качеством WebP,
 * ленивой загрузкой изображений и CDN, плюс ручная очистка кэша.
 */
final class PerformanceController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $opcacheInfo = [
            'enabled' => false,
            'memory_used' => '0 MB',
            'memory_free' => '0 MB',
            'hit_rate' => '0%',
            'cached_scripts' => 0,
        ];

        if (function_exists('opcache_get_status')) {
            /** @var array<string, mixed>|false $status */
            $status = @opcache_get_status(false);
            if (is_array($status) && !empty($status['opcache_enabled'])) {
                /** @var array<string, mixed> $mem */
                $mem = is_array($status['memory_usage'] ?? null) ? $status['memory_usage'] : [];
                /** @var array<string, mixed> $stats */
                $stats = is_array($status['opcache_statistics'] ?? null) ? $status['opcache_statistics'] : [];

                $used = is_numeric($mem['used_memory'] ?? null) ? (float) $mem['used_memory'] : 0.0;
                $free = is_numeric($mem['free_memory'] ?? null) ? (float) $mem['free_memory'] : 0.0;
                $hits = is_numeric($stats['opcache_hit_rate'] ?? null) ? (float) $stats['opcache_hit_rate'] : 0.0;
                $scripts = is_numeric($stats['num_cached_scripts'] ?? null) ? (int) $stats['num_cached_scripts'] : 0;

                $usedMb = round($used / 1024 / 1024, 1);
                $freeMb = round($free / 1024 / 1024, 1);
                $hitRate = round($hits, 1);

                $opcacheInfo = [
                    'enabled' => true,
                    'memory_used' => $usedMb . ' MB',
                    'memory_free' => $freeMb . ' MB',
                    'hit_rate' => $hitRate . '%',
                    'cached_scripts' => $scripts,
                ];
            }
        }

        View::render('admin/performance/index', [
            'settings' => Setting::all(),
            'opcacheInfo' => $opcacheInfo,
            'assetStatus' => FrontendAssets::status(),
            'cfTokenConfigured' => Setting::get('cf_api_token', '') !== '',
            // Замеры с реальных посетителей: 75-й перцентиль за 28 дней.
            'vitals' => \App\Core\WebVitals::enabled() ? \App\Core\WebVitals::summary(28) : [],
            'vitalsMobile' => \App\Core\WebVitals::enabled() ? \App\Core\WebVitals::summary(28, 'mobile') : [],
        ]);
    }

    public function update(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $cacheTtl = SettingsValidator::boundedInt(
            (string) ($_POST['perf_cache_ttl'] ?? ''),
            0,
            31536000,
            0
        );
        $publicTtl = SettingsValidator::boundedInt(
            (string) ($_POST['perf_public_cache_ttl'] ?? ''),
            0,
            3600,
            60
        );
        $sharedTtl = SettingsValidator::boundedInt(
            (string) ($_POST['perf_shared_cache_ttl'] ?? ''),
            0,
            86400,
            300
        );
        $quality = SettingsValidator::boundedInt(
            (string) ($_POST['perf_webp_quality'] ?? ''),
            40,
            95,
            82
        );
        $imgMaxW = SettingsValidator::boundedInt(
            (string) ($_POST['perf_image_max_width'] ?? ''),
            1200,
            4000,
            2560
        );
        $cdn = SettingsValidator::publicBaseUrl((string) ($_POST['perf_cdn_url'] ?? ''));
        $zone = strtolower(trim((string) ($_POST['cf_zone_id'] ?? '')));
        $cfToken = trim((string) ($_POST['cf_api_token'] ?? ''));
        $cfEnabled = !empty($_POST['cf_enabled']);
        $clearCfToken = !empty($_POST['clear_cf_api_token']);
        $effectiveCfToken = $clearCfToken
            ? ''
            : ($cfToken !== '' ? $cfToken : trim((string) Setting::get('cf_api_token', '')));

        $errors = [];
        if ($cacheTtl === null) {
            $errors[] = 'TTL файлового кэша должен быть от 0 до 31536000 секунд.';
        }
        if ($publicTtl === null) {
            $errors[] = 'TTL браузера должен быть от 0 до 3600 секунд.';
        }
        if ($sharedTtl === null) {
            $errors[] = 'TTL CDN должен быть от 0 до 86400 секунд.';
        }
        if ($quality === null) {
            $errors[] = 'Качество WebP должно быть от 40 до 95.';
        }
        if ($imgMaxW === null) {
            $errors[] = 'Максимальная ширина изображения должна быть от 1200 до 4000 px.';
        }
        if ($cdn === null) {
            $errors[] = 'CDN URL должен быть полным http(s)-адресом без логина, query и fragment.';
        }
        if ($zone !== '' && preg_match('/^[a-f0-9]{32}$/', $zone) !== 1) {
            $errors[] = 'Cloudflare Zone ID должен содержать ровно 32 шестнадцатеричных символа.';
        }
        if (strlen($cfToken) > 5000
            || preg_match('/[\x00-\x1F\x7F]/', $cfToken) === 1
            || ($cfEnabled && (
                strlen($effectiveCfToken) > 5000
                || preg_match('/[\x00-\x1F\x7F]/', $effectiveCfToken) === 1
            ))) {
            $errors[] = 'Cloudflare API-токен имеет недопустимый формат.';
        }
        if ($cfEnabled && ($effectiveCfToken === '' || $zone === '')) {
            $errors[] = 'Для включения Cloudflare укажите API-токен и Zone ID.';
        }
        if ($errors !== []) {
            Flash::error(implode(' ', $errors));
            header('Location: /admin/performance');
            exit;
        }

        Setting::set('perf_page_cache', !empty($_POST['perf_page_cache']) ? '1' : '0');
        Setting::set('perf_asset_bundle', !empty($_POST['perf_asset_bundle']) ? '1' : '0');
        Setting::set('perf_cache_ttl', (string) $cacheTtl);
        Setting::set('perf_public_cache_ttl', (string) $publicTtl);
        Setting::set('perf_shared_cache_ttl', (string) $sharedTtl);
        Setting::set('perf_lazy_load', !empty($_POST['perf_lazy_load']) ? '1' : '0');
        Setting::set('perf_vitals_enabled', !empty($_POST['perf_vitals_enabled']) ? '1' : '0');
        // Доля посетителей, с которых собираем метрики: 1–100 %.
        $vitalsSample = max(1, min(100, (int) ($_POST['perf_vitals_sample'] ?? 100)));
        Setting::set('perf_vitals_sample', (string) round($vitalsSample / 100, 2));
        Setting::set('perf_webp_quality', (string) $quality);
        Setting::set('perf_image_max_width', (string) $imgMaxW);
        Setting::set('perf_cdn_url', (string) $cdn);

        // Cloudflare (очистка кэша по API).
        Setting::set('cf_enabled', $cfEnabled ? '1' : '0');
        if ($clearCfToken) {
            Setting::set('cf_api_token', '');
        } elseif ($cfToken !== '') {
            Setting::set('cf_api_token', $cfToken);
        }
        Setting::set('cf_zone_id', $zone);

        Cache::forgetPrefix('page:');
        Flash::success('Настройки производительности сохранены.');
        header('Location: /admin/performance');
        exit;
    }

    public function cloudflareVerify(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $r = \App\Core\Cloudflare::verify();
        $r['ok'] ? Flash::success($r['message']) : Flash::error($r['message']);
        header('Location: /admin/performance');
        exit;
    }

    public function cloudflarePurge(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        if (!\App\Core\Cloudflare::enabled()) {
            Flash::error('Интеграция с Cloudflare выключена или не настроена.');
        } elseif (\App\Core\Cloudflare::purgeEverything()) {
            Flash::success('Кэш Cloudflare очищен.');
        } else {
            Flash::error('Не удалось очистить кэш Cloudflare — проверьте токен и Zone ID (подробности в логах).');
        }
        header('Location: /admin/performance');
        exit;
    }

    public function resetOpcache(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        if (function_exists('opcache_reset') && @opcache_reset()) {
            Flash::success('Кэш PHP OPcache успешно сброшен.');
        } else {
            Flash::error('OPcache отключен на сервере или функция opcache_reset недоступна.');
        }
        header('Location: /admin/performance');
        exit;
    }

    public function clearCache(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $cfEnabled = \App\Core\Cloudflare::enabled();
        Cache::flush();
        $cfOk = $cfEnabled ? \App\Core\Cloudflare::purgeEverything() : null;
        $opcacheOk = function_exists('opcache_reset') ? @opcache_reset() : null;

        $parts = ['Файловый кэш очищен'];
        $parts[] = $cfOk === true
            ? 'Cloudflare очищен'
            : ($cfOk === false ? 'Cloudflare: ошибка очистки' : 'Cloudflare не настроен');
        $parts[] = $opcacheOk === true
            ? 'OPcache сброшен'
            : ($opcacheOk === false ? 'OPcache: сброс недоступен' : 'OPcache отключен');
        $message = implode(' · ', $parts) . '.';
        $cfOk === false ? Flash::error($message) : Flash::success($message);

        // Возврат на страницу, откуда нажали (только локальные /admin-пути).
        $back = (string) ($_POST['redirect'] ?? '');
        if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//') || !str_starts_with($back, '/admin')) {
            $back = '/admin/performance';
        }
        header('Location: ' . $back);
        exit;
    }
}

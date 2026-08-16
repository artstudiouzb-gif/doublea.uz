<?php

use App\Core\Csrf;

$pageTitle = 'Производительность';
$activeNav = 'performance';
require __DIR__ . '/../layout/header.php';

/** @var array $settings */
/** @var bool $cfTokenConfigured */
/** @var array<string, mixed> $assetStatus */
$val = fn (string $k, string $d = '') => htmlspecialchars($settings[$k] ?? $d, ENT_QUOTES);
$on = fn (string $k, string $d = '0') => ($settings[$k] ?? $d) === '1';
$size = static function (mixed $bytes): string {
    return is_numeric($bytes) ? number_format((float) $bytes / 1024, 1, ',', ' ') . ' КиБ' : '—';
};
?>
<p class="form-hint admin-section-intro">
    Управление публичным кэшем, оптимизированными ресурсами, изображениями и CDN.
    Изменения применяются к новым ответам сразу после сохранения.
</p>
<nav class="settings-jump-nav" aria-label="Разделы производительности">
    <a href="#perf-status"><?= \App\Core\AdminUi::icon('performance', 16) ?>Статус</a>
    <a href="#perf-pages"><?= \App\Core\AdminUi::icon('files', 16) ?>Кэш страниц</a>
    <a href="#perf-assets"><?= \App\Core\AdminUi::icon('code', 16) ?>CSS и JS</a>
    <a href="#perf-images"><?= \App\Core\AdminUi::icon('image', 16) ?>Изображения</a>
    <a href="#perf-cdn"><?= \App\Core\AdminUi::icon('world', 16) ?>CDN</a>
    <a href="#perf-cloudflare"><?= \App\Core\AdminUi::icon('cloud', 16) ?>Cloudflare</a>
</nav>

<div class="form-card u-inline-7dde5e56b3 performance-section" id="perf-status">
    <div class="header-builder__group u-inline-76084ee4e5">
        <h3>Статус PHP OPcache и системного кеша</h3>
        <p class="form-hint u-inline-291b7bbb01">
            OPcache ускоряет выполнение PHP в 3-5 раз за счет хранения байткода в оперативной памяти сервера.
        </p>
        <div class="u-inline-7561f48274">
            <div class="u-inline-2a7e2ecd89">
                <strong>Статус OPcache:</strong>
                <span class="badge <?= !empty($opcacheInfo['enabled']) ? 'badge--success' : 'badge--secondary' ?>">
                    <?= !empty($opcacheInfo['enabled']) ? 'Включен' : 'Отключен' ?>
                </span>
            </div>
            <?php if (!empty($opcacheInfo['enabled'])): ?>
                <div class="u-inline-2a7e2ecd89">
                    <strong>Память:</strong> <?= $opcacheInfo['memory_used'] ?> / <?= $opcacheInfo['memory_free'] ?> свободно
                </div>
                <div class="u-inline-2a7e2ecd89">
                    <strong>Эффективность (Hit Rate):</strong> <?= $opcacheInfo['hit_rate'] ?>
                </div>
                <div class="u-inline-2a7e2ecd89">
                    <strong>Скриптов в кэше:</strong> <?= $opcacheInfo['cached_scripts'] ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="u-inline-1e90930a6f">
            <form method="post" action="/admin/performance/clear-cache"
                  data-confirm="Очистить файловый кэш сайта, Cloudflare и OPcache?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Очистить весь кэш (файлы + Cloudflare + OPcache)</button>
            </form>
            <?php if (!empty($opcacheInfo['enabled'])): ?>
                <form method="post" action="/admin/performance/reset-opcache"
                      data-confirm="Сбросить PHP OPcache?">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--outline">Сбросить только PHP OPcache</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="form-card performance-settings-card">
    <form method="post" action="/admin/performance" class="form-grid">
        <?= Csrf::field() ?>

        <div class="header-builder__group performance-section" id="perf-pages">
            <h3>Кэширование страниц</h3>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="perf_page_cache" name="perf_page_cache" value="1" <?= $on('perf_page_cache', '1') ? 'checked' : '' ?>>
                <label for="perf_page_cache">Кэшировать скомпилированные страницы (рекомендуется на боевом)</label>
            </div>
            <div class="form-field">
                <label for="perf_cache_ttl">Время жизни кэша, секунд (0 — до следующей правки контента)</label>
                <input type="number" id="perf_cache_ttl" name="perf_cache_ttl" min="0" max="31536000" value="<?= $val('perf_cache_ttl', '0') ?>">
                <span class="form-hint">Например, 3600 — пересобирать страницы не чаще раза в час.</span>
            </div>
            <div class="form-field">
                <label for="perf_public_cache_ttl">Кеш браузера для публичных страниц, секунд</label>
                <input type="number" id="perf_public_cache_ttl" name="perf_public_cache_ttl" min="0" max="3600" value="<?= $val('perf_public_cache_ttl', '60') ?>">
                <span class="form-hint">Рекомендуется 60. Применяется только к ответам без пользовательской сессии.</span>
            </div>
            <div class="form-field">
                <label for="perf_shared_cache_ttl">Кеш CDN/reverse proxy, секунд</label>
                <input type="number" id="perf_shared_cache_ttl" name="perf_shared_cache_ttl" min="0" max="86400" value="<?= $val('perf_shared_cache_ttl', '300') ?>">
                <span class="form-hint">Рекомендуется 300. При изменении контента интеграция Cloudflare очищает кеш автоматически.</span>
            </div>
        </div>

        <div class="header-builder__group performance-section" id="perf-assets">
            <h3>Сжатие CSS и JavaScript</h3>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="perf_asset_bundle" name="perf_asset_bundle" value="1" <?= $on('perf_asset_bundle', '1') ? 'checked' : '' ?>>
                <label for="perf_asset_bundle">Использовать оптимизированные CSS/JS-бандлы (рекомендуется)</label>
            </div>
            <p class="form-hint">
                Уменьшает число запросов и объём загрузки. Отключайте только для диагностики:
                сайт загрузит отдельные исходные файлы. Если сборка отсутствует или повреждена,
                переключение на исходники произойдёт автоматически.
            </p>
            <div class="u-inline-e27394a4cc">
                <div class="u-inline-4f738c4fe1">
                    <strong>Режим:</strong>
                    <span class="badge <?= !empty($assetStatus['enabled']) ? 'badge--success' : 'badge--secondary' ?>">
                        <?= !empty($assetStatus['enabled']) ? 'Оптимизирован' : 'Исходные файлы' ?>
                    </span>
                </div>
                <div class="u-inline-4f738c4fe1">
                    <strong>Сборка:</strong>
                    <span class="badge <?= !empty($assetStatus['current']) ? 'badge--success' : 'badge--secondary' ?>">
                        <?= !empty($assetStatus['current']) ? 'Актуальна' : (!empty($assetStatus['ready']) ? 'Требует пересборки' : 'Недоступна') ?>
                    </span>
                </div>
                <div class="u-inline-4f738c4fe1">
                    <strong>Передача сервером:</strong>
                    Brotli <?= \App\Core\AdminUi::icon(!empty($assetStatus['brotliConfigured']) ? 'check' : 'minus', 13) ?> ·
                    gzip <?= \App\Core\AdminUi::icon(!empty($assetStatus['gzipConfigured']) ? 'check' : 'minus', 13) ?>
                </div>
            </div>
            <div class="u-inline-d0698c48c3">
                <table class="data-table">
                    <thead>
                    <tr><th>Ресурс</th><th>Без сжатия</th><th>gzip</th><th>Brotli</th></tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>CSS</td>
                        <td><?= $size($assetStatus['css']['raw'] ?? null) ?></td>
                        <td><?= $size($assetStatus['css']['gzip'] ?? null) ?></td>
                        <td><?= $size($assetStatus['css']['brotli'] ?? null) ?></td>
                    </tr>
                    <tr>
                        <td>JavaScript</td>
                        <td><?= $size($assetStatus['js']['raw'] ?? null) ?></td>
                        <td><?= $size($assetStatus['js']['gzip'] ?? null) ?></td>
                        <td><?= $size($assetStatus['js']['brotli'] ?? null) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <?php if (empty($assetStatus['current'])): ?>
                <p class="form-hint u-inline-018813c94c">
                    Выполните <code>npm ci &amp;&amp; npm run build:assets</code> перед публикацией.
                </p>
            <?php endif; ?>
        </div>

        <div class="header-builder__group performance-section" id="perf-images">
            <h3>Изображения</h3>
            <div class="form-field">
                <label for="perf_webp_quality">Качество WebP (40–95)</label>
                <input type="number" id="perf_webp_quality" name="perf_webp_quality" min="40" max="95" value="<?= $val('perf_webp_quality', '82') ?>">
                <span class="form-hint">Ниже — легче файлы, выше — качественнее. Применяется к новым загрузкам.</span>
            </div>
            <div class="form-field">
                <label for="perf_image_max_width">Макс. ширина оригинала (px, 1200–4000)</label>
                <input type="number" id="perf_image_max_width" name="perf_image_max_width" min="1200" max="4000" value="<?= $val('perf_image_max_width', '2560') ?>">
                <span class="form-hint">Загруженные фото шире этого значения автоматически уменьшаются — экономит вес страниц и место. Применяется к новым загрузкам.</span>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="perf_lazy_load" name="perf_lazy_load" value="1" <?= $on('perf_lazy_load', '1') ? 'checked' : '' ?>>
                <label for="perf_lazy_load">Ленивая загрузка изображений (loading="lazy")</label>
            </div>
        </div>

        <div class="header-builder__group performance-section" id="perf-vitals">
            <h3>Скорость у реальных посетителей</h3>
            <p class="form-hint">
                Лабораторные замеры (Lighthouse) не показывают INP — отзывчивость на
                нажатия — вообще, а LCP на тестовом сервере упирается в сам сервер.
                Здесь собираются метрики с браузеров посетителей. Ни адрес страницы,
                ни IP, ни идентификатор посетителя не сохраняются: только метрика,
                значение и крупный срез. Cookie не заводится.
            </p>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="perf_vitals_enabled" name="perf_vitals_enabled" value="1" <?= $on('perf_vitals_enabled', '0') ? 'checked' : '' ?>>
                <label for="perf_vitals_enabled">Собирать Core Web Vitals</label>
            </div>
            <div class="form-field">
                <label for="perf_vitals_sample">Доля посетителей, %</label>
                <input type="number" id="perf_vitals_sample" name="perf_vitals_sample" min="1" max="100"
                       value="<?= (int) round(((float) ($settings['perf_vitals_sample'] ?? '1')) * 100) ?>">
                <span class="form-hint">На небольшом сайте нужна вся выборка. Уменьшать имеет смысл, когда посещаемость вырастет.</span>
            </div>
<?php if (!empty($vitals)): ?>
            <table class="data-table">
                <thead><tr><th>Метрика</th><th>75-й перцентиль</th><th>Мобильные</th><th>Замеров</th></tr></thead>
                <tbody>
<?php foreach ($vitals as $name => $row): ?>
                    <?php
                    $unit = $name === 'CLS' ? '' : ' мс';
                    $fmt = static fn (float $v): string => $name === 'CLS'
                        ? number_format($v, 3, ',', ' ')
                        : number_format($v, 0, ',', ' ');
                    $label = ['good' => 'хорошо', 'needs-improvement' => 'нужно улучшить', 'poor' => 'плохо'];
                    $mobile = $vitalsMobile[$name] ?? null;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($name, ENT_QUOTES) ?></strong></td>
                        <td><?= $fmt($row['p75']) . $unit ?> — <?= $label[$row['rating']] ?? '' ?></td>
                        <td><?= $mobile ? $fmt($mobile['p75']) . $unit : '—' ?></td>
                        <td><?= (int) $row['count'] ?></td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
            <p class="form-hint">За последние 28 дней. 75-й перцентиль, а не среднее: среднее вытягивают быстрые заходы с горячим кэшем.</p>
<?php elseif ($on('perf_vitals_enabled', '0')): ?>
            <p class="form-hint">Замеров пока нет — они появятся после визитов на публичную часть.</p>
<?php endif; ?>
        </div>

        <div class="header-builder__group performance-section" id="perf-cdn">
            <h3>CDN для статики и загрузок</h3>
            <div class="form-field">
                <label for="perf_cdn_url">Базовый URL CDN (необязательно)</label>
                <input type="url" id="perf_cdn_url" name="perf_cdn_url" maxlength="500"
                       value="<?= $val('perf_cdn_url') ?>" placeholder="https://xxxxx.b-cdn.net">
                <span class="form-hint">
                    Ссылки на <code>/assets</code> (CSS/JS) и <code>/uploads/public</code> (картинки, видео)
                    будут отдаваться с этого хоста. <b>Домен переносить в Cloudflare не нужно</b> —
                    подойдёт любой <b>pull-zone CDN</b>: вы указываете origin (этот сайт), а CDN даёт вам
                    свой хост (например, BunnyCDN <code>*.b-cdn.net</code>, KeyCDN, Fastly, либо Cloudflare R2 /
                    поддомен за Cloudflare). CDN сам тянет файлы с сайта и кэширует их по миру.
                    Инвалидация не нужна: статика версионируется (<code>?v=</code>), а имена загрузок уникальны.
                </span>
            </div>
        </div>

        <div class="header-builder__group performance-section" id="perf-cloudflare">
            <h3>Cloudflare (очистка кэша по API)</h3>
            <p class="form-hint u-inline-291b7bbb01">
                Нужно только если сайт или его поддомен <b>проксируется через Cloudflare</b>
                (оранжевое облако). Тогда при изменении контента кэш Cloudflare очищается автоматически.
                Токен создайте в Cloudflare → My Profile → API Tokens с правом <code>Zone · Cache Purge</code>.
            </p>
            <label class="hb-switch u-inline-af3fee87a1">
                <input type="checkbox" name="cf_enabled" value="1" <?= $val('cf_enabled') === '1' ? 'checked' : '' ?>>
                <span class="hb-switch__track"></span> Включить интеграцию с Cloudflare
            </label>
            <div class="form-field">
                <label for="cf_api_token">API-токен</label>
                <input type="password" id="cf_api_token" name="cf_api_token" value=""
                       maxlength="5000"
                       placeholder="<?= $cfTokenConfigured ? 'Сохранён — оставьте пустым без изменений' : 'cf_xxx' ?>"
                       autocomplete="new-password">
                <?php if ($cfTokenConfigured): ?>
                    <label class="form-hint"><input type="checkbox" name="clear_cf_api_token" value="1"> Удалить API-токен</label>
                <?php endif; ?>
            </div>
            <div class="form-field">
                <label for="cf_zone_id">Zone ID</label>
                <input type="text" id="cf_zone_id" name="cf_zone_id" maxlength="32"
                       pattern="[A-Fa-f0-9]{32}" value="<?= $val('cf_zone_id') ?>"
                       placeholder="напр. 023e105f4ecef8ad9ca31a8372d0c353">
                <span class="form-hint">Zone ID — на странице обзора домена в панели Cloudflare (справа).</span>
            </div>
        </div>

        <div class="form-actions form-actions--sticky">
            <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить настройки производительности</button>
        </div>
    </form>

    <?php if ($cfTokenConfigured && ($val('cf_zone_id') !== '')): ?>
        <div class="header-builder__group u-inline-e7673d9ced">
            <h3>Cloudflare — проверка и очистка</h3>
            <div class="u-inline-1e90930a6f">
                <form class="u-inline-1da9facb4d" method="post" action="/admin/cloudflare/verify">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn">Проверить подключение</button>
                </form>
                <form class="u-inline-1da9facb4d" method="post" action="/admin/cloudflare/purge">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn--primary"
                            data-confirm="Очистить весь кэш выбранной зоны Cloudflare?">Очистить кэш Cloudflare</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>

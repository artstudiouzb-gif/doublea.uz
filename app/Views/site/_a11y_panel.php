<?php

/**
 * Панель настроек отображения — выдвижная колонка справа.
 * Разметка отдаётся сервером: она переводится вместе с сайтом, работает
 * при выключенном JS (значения видны) и не требует inline-скриптов (CSP).
 *
 * @var array $a11ySettings — нормализованные настройки из cookie
 */

use App\Core\A11ySettings;
use App\Core\Icon;

$settings = $a11ySettings ?? A11ySettings::DEFAULTS;

/** Кнопка выбора значения: подсвечена, если оно сейчас активно. */
$choice = static function (string $key, string $value, string $label, string $extraClass = '') use ($settings): string {
    $pressed = ((string) $settings[$key] === $value) ? 'true' : 'false';

    return '<button type="button" class="a11y-choice' . ($extraClass !== '' ? ' ' . $extraClass : '') . '"'
        . ' data-a11y-set="' . htmlspecialchars($key . ':' . $value, ENT_QUOTES) . '"'
        . ' aria-pressed="' . $pressed . '">' . htmlspecialchars($label, ENT_QUOTES) . '</button>';
};
?>
<div class="a11y-backdrop" data-a11y-close hidden></div>
<aside class="a11y-drawer" id="a11y-panel" aria-label="<?= htmlspecialchars(t('Настройки отображения'), ENT_QUOTES) ?>" hidden>
    <div class="a11y-drawer__head">
        <h2 class="a11y-drawer__title"><?= Icon::render('eye', 20) ?><?= htmlspecialchars(t('Настройки отображения'), ENT_QUOTES) ?></h2>
        <button type="button" class="a11y-drawer__close" data-a11y-close aria-label="<?= htmlspecialchars(t('Закрыть'), ENT_QUOTES) ?>">&times;</button>
    </div>

    <div class="a11y-drawer__body">
        <section class="a11y-group">
            <div class="a11y-group__head">
                <span class="a11y-group__title"><?= htmlspecialchars(t('Размер текста'), ENT_QUOTES) ?></span>
                <output class="a11y-group__value" data-a11y-size-value><?= (int) $settings['size'] ?>%</output>
            </div>
            <div class="a11y-range">
                <button type="button" class="a11y-range__step" data-a11y-step="-1" aria-label="<?= htmlspecialchars(t('Уменьшить текст'), ENT_QUOTES) ?>">&minus;</button>
                <input type="range" class="a11y-range__input" data-a11y-range="size"
                       min="<?= A11ySettings::SIZE_MIN ?>" max="<?= A11ySettings::SIZE_MAX ?>" step="<?= A11ySettings::SIZE_STEP ?>"
                       value="<?= (int) $settings['size'] ?>"
                       aria-label="<?= htmlspecialchars(t('Размер текста'), ENT_QUOTES) ?>">
                <button type="button" class="a11y-range__step" data-a11y-step="1" aria-label="<?= htmlspecialchars(t('Увеличить текст'), ENT_QUOTES) ?>">+</button>
            </div>
        </section>

        <section class="a11y-group">
            <span class="a11y-group__title"><?= htmlspecialchars(t('Контраст и фон'), ENT_QUOTES) ?></span>
            <div class="a11y-group__row a11y-group__row--schemes">
                <?= $choice('contrast', 'normal', t('Обычный'), 'a11y-choice--scheme a11y-choice--normal') ?>
                <?= $choice('contrast', 'mono', t('Чёрно-белый'), 'a11y-choice--scheme a11y-choice--mono') ?>
                <?= $choice('contrast', 'warm', t('Тёплый'), 'a11y-choice--scheme a11y-choice--warm') ?>
                <?php // Тёмный фон — это тёмная тема сайта, её переключатель общий. ?>
                <button type="button" class="a11y-choice a11y-choice--scheme a11y-choice--dark" data-a11y-theme aria-pressed="false"><?= htmlspecialchars(t('Тёмный'), ENT_QUOTES) ?></button>
            </div>
            <p class="a11y-group__hint"><?= htmlspecialchars(t('Тёплый фон снижает резь в глазах при долгом чтении.'), ENT_QUOTES) ?></p>
        </section>

        <section class="a11y-group">
            <span class="a11y-group__title"><?= htmlspecialchars(t('Шрифт'), ENT_QUOTES) ?></span>
            <div class="a11y-group__row">
                <?= $choice('font', 'default', t('Обычный')) ?>
                <?= $choice('font', 'readable', t('Читаемый'), 'a11y-choice--font-readable') ?>
                <?= $choice('font', 'serif', t('С засечками'), 'a11y-choice--font-serif') ?>
            </div>
        </section>

        <section class="a11y-group">
            <span class="a11y-group__title"><?= htmlspecialchars(t('Интервалы'), ENT_QUOTES) ?></span>
            <div class="a11y-group__row">
                <?= $choice('spacing', 'normal', t('Обычные')) ?>
                <?= $choice('spacing', 'wide', t('Увеличенные')) ?>
                <?= $choice('spacing', 'wider', t('Максимальные')) ?>
            </div>
        </section>

        <?php // Письменность узбекского текста выбирается в переключателе
              // языков («O‘zbekcha» / «Ўзбекча»): здесь — только внешний вид. ?>
        <section class="a11y-group">
            <span class="a11y-group__title"><?= htmlspecialchars(t('Чтение без помех'), ENT_QUOTES) ?></span>
            <div class="a11y-group__row a11y-group__row--toggles">
                <?= $choice('reading', 'on', t('Режим чтения'), 'a11y-choice--toggle') ?>
                <?= $choice('images', 'off', t('Скрыть картинки'), 'a11y-choice--toggle') ?>
                <?= $choice('motion', 'off', t('Остановить анимации'), 'a11y-choice--toggle') ?>
                <?= $choice('links', 'underline', t('Подчёркивать ссылки'), 'a11y-choice--toggle') ?>
            </div>
            <p class="a11y-group__hint"><?= htmlspecialchars(t('Режим чтения убирает баннеры и колонки: остаётся только текст страницы.'), ENT_QUOTES) ?></p>
        </section>
    </div>

    <div class="a11y-drawer__foot">
        <button type="button" class="a11y-reset" data-a11y-reset><?= htmlspecialchars(t('Сбросить настройки'), ENT_QUOTES) ?></button>
        <p class="a11y-drawer__note"><?= htmlspecialchars(t('Настройки сохраняются в этом браузере и действуют на всех страницах сайта.'), ENT_QUOTES) ?></p>
    </div>
</aside>

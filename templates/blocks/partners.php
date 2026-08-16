<?php

use App\Core\Icon;

/** @var array $data */
/** @var int $blockId */
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$columns = max(3, min(8, (int) ($data['columns'] ?? 6)));
$logoSize = in_array($data['logo_size'] ?? 'medium', ['small', 'medium', 'large'], true)
    ? (string) $data['logo_size']
    : 'medium';
$grayscale = !empty($data['grayscale']);
$autoplay = max(0, min(30, (int) ($data['autoplay'] ?? 0)));
$carousel = count($items) > 1;
// В полосу сетка превращается, когда логотипов больше, чем помещается в ряд.
$desktopCarousel = count($items) > $columns;

// Число колонок и высота логотипа — в scoped CSS: инлайн-стили в блоках
// запрещены тестами.
$templateCss = '@media (min-width:721px){#block-' . (int) $blockId
    . ' .block-partners__grid{--partners-cols:' . $columns . '}}';

$navHtml = '';
if ($carousel) {
    ob_start(); ?>
    <span class="carousel-nav" data-carousel-nav hidden>
        <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
        <span class="carousel-nav__dots" data-carousel-dots role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>"></span>
        <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
    </span>
    <?php $navHtml = (string) ob_get_clean();
}

$head = \App\Core\SectionHead::render([
    'title' => (string) ($data['title'] ?? ''),
    'description' => (string) ($data['description'] ?? ''),
    'all_text' => (string) ($data['all_text'] ?? ''),
    'all_url' => (string) ($data['all_url'] ?? ''),
    'tools' => $navHtml,
    'title_class' => 'block-partners__title',
]);
?>
<div class="block-partners block-partners--logo-<?= htmlspecialchars($logoSize, ENT_QUOTES) ?><?= $grayscale ? ' block-partners--grayscale' : '' ?>"<?= $carousel ? ' data-carousel' : '' ?><?= $carousel && $autoplay > 0 ? ' data-carousel-autoplay="' . $autoplay . '"' : '' ?>>
    <?= $head ?>
    <?php if (!empty($items)): ?>
        <div class="block-partners__grid<?= $desktopCarousel ? ' block-partners__grid--carousel' : '' ?>"<?= $carousel ? ' data-carousel-track tabindex="0" role="group" aria-label="' . htmlspecialchars(t('Партнёры — прокрутка вбок'), ENT_QUOTES) . '"' : '' ?>>
            <?php foreach ($items as $p): ?>
                <?php
                $logo = trim((string) ($p['logo'] ?? ''));
                $nameRaw = (string) ($p['name'] ?? '');
                $name = htmlspecialchars($nameRaw, ENT_QUOTES);
                $url = (string) ($p['url'] ?? '');
                if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                    $url = '';
                }
                // Без логотипа показываем название текстом: пустой <img src="">
                // — это битая картинка, а имя партнёра было видно только во
                // всплывающей подсказке.
                $img = $logo !== ''
                    ? \App\Core\Media::picture($logo, $nameRaw, null, null, 'block-partners__logo', true, '180px')
                    : '<span class="block-partners__name">' . ($name !== '' ? $name : htmlspecialchars(t('Партнёр'), ENT_QUOTES)) . '</span>';
                ?>
                <?php if ($url !== ''): ?>
                    <a class="block-partners__item"<?= $carousel ? ' data-carousel-item' : '' ?> href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" rel="noopener" title="<?= $name ?>"><?= $img ?></a>
                <?php else: ?>
                    <span class="block-partners__item"<?= $carousel ? ' data-carousel-item' : '' ?> title="<?= $name ?>"><?= $img ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php

use App\Core\Icon;

/** @var array $data */
/** @var int $blockId */
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$variant = in_array($data['variant'] ?? 'carousel', ['carousel', 'grid'], true)
    ? (string) $data['variant']
    : 'carousel';
$columns = max(2, min(4, (int) ($data['columns'] ?? 3)));
$autoplay = max(0, min(30, (int) ($data['autoplay'] ?? 0)));
// Полосой отзывы едут только в варианте «карусель» и только когда их больше одного.
$carousel = $variant === 'carousel' && count($items) > 1;

$templateCss = '';
if ($variant === 'grid') {
    // Два отзыва в трёх колонках оставляли треть ряда пустой.
    $templateCss = \App\Core\GridBalance::css($blockId, '.block-testimonials__grid', '.testimonial', count($items), $columns);
}

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
    'tools' => $navHtml,
    'title_class' => 'block-testimonials__title',
]);
?>
<div class="block-testimonials block-testimonials--<?= htmlspecialchars($variant, ENT_QUOTES) ?>"<?= $carousel ? ' data-carousel' : '' ?><?= $carousel && $autoplay > 0 ? ' data-carousel-autoplay="' . $autoplay . '"' : '' ?>>
    <?= $head ?>
    <?php if ($variant === 'grid'): ?>
        <div class="block-testimonials__grid">
    <?php else: ?>
        <?php // Полоса прокручивается вбок: с клавиатуры до неё нужно добраться
              // и пролистать стрелками, поэтому область фокусируемая и подписана. ?>
        <div class="block-testimonials__track"<?= $carousel ? ' data-carousel-track' : '' ?> tabindex="0" role="group"
             aria-label="<?= htmlspecialchars(t('Отзывы — прокрутка вбок'), ENT_QUOTES) ?>">
    <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php $rating = max(0, min(5, (int) ($item['rating'] ?? 0))); ?>
            <figure class="testimonial"<?= $carousel ? ' data-carousel-item' : '' ?>>
                <?php if ($rating > 0): ?>
                    <?php // Ряд значков — одна картинка для экранного диктора: он читает подпись, а не пять звёзд подряд. ?>
                    <span class="testimonial__rating" role="img" aria-label="<?= htmlspecialchars(t('Оценка'), ENT_QUOTES) . ' ' . $rating . ' ' . htmlspecialchars(t('из'), ENT_QUOTES) ?> 5">
                        <?php for ($star = 1; $star <= 5; $star++): ?>
                            <span class="testimonial__star<?= $star <= $rating ? ' is-on' : '' ?>" aria-hidden="true"><?= Icon::render('star', 18) ?></span>
                        <?php endfor; ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($item['photo'])): ?>
                    <?= \App\Core\Media::picture((string) $item['photo'], (string) ($item['name'] ?? ''), null, null, 'testimonial__photo', true, '72px') ?>
                <?php endif; ?>
                <blockquote class="testimonial__quote"><?= htmlspecialchars($item['quote'] ?? '', ENT_QUOTES) ?></blockquote>
                <figcaption class="testimonial__author">
                    <span class="testimonial__name"><?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?></span>
                    <?php if (!empty($item['role'])): ?>
                        <span class="testimonial__role"><?= htmlspecialchars((string) $item['role'], ENT_QUOTES) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['company'])): ?>
                        <span class="testimonial__company"><?= htmlspecialchars($item['company'], ENT_QUOTES) ?></span>
                    <?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
        </div>
</div>

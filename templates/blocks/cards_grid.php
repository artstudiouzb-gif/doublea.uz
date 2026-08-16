<?php

use App\Core\Icon;
use App\Core\Media;
use App\Core\MediaPosition;

/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$variant = in_array($data['variant'] ?? 'icon', ['icon', 'compact', 'image', 'image_below'], true) ? (string) $data['variant'] : 'icon';
// «Текст под фото» — та же карточка, только подпись выносится из-под градиента
// на подложку: длинный анонс на снимке читается плохо, что бы ни делали с
// затемнением.
$imageBelow = $variant === 'image_below';
$columns = max(2, min(5, (int) ($data['columns'] ?? 5)));
$mediaClasses = MediaPosition::classes($data['image_position'] ?? null, $data['image_position_mobile'] ?? null);
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? '')) ? (string) $data['card_bg'] : '';
$textColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? (string) $data['text_color'] : '';
$visualStyleRaw = (string) ($data['_cards_style'] ?? 'old');
$visualStyle = in_array($visualStyleRaw, ['old', 'new'], true) ? $visualStyleRaw : 'old';
$iconSize = max(16, min(64, (int) ($data['_cards_icon_size'] ?? 22)));
$iconBackgroundRaw = (string) ($data['_cards_icon_bg'] ?? 'on');
$iconBackground = in_array($iconBackgroundRaw, ['on', 'off'], true) ? $iconBackgroundRaw : 'on';
$iconPositionRaw = (string) ($data['_cards_icon_position'] ?? 'top');
$iconPosition = in_array($iconPositionRaw, ['top', 'left', 'right', 'center'], true) ? $iconPositionRaw : 'top';
$textAlignRaw = (string) ($data['_cards_text_align'] ?? 'left');
$textAlign = in_array($textAlignRaw, ['left', 'center', 'right'], true) ? $textAlignRaw : 'left';
$iconBoxSize = max(42, $iconSize + 18);
$cardStyle = '--feature-card-icon-size:' . $iconSize . 'px;'
    . ($cardBg !== '' ? '--card-bg:' . $cardBg . ';' : '')
    . ($textColor !== '' ? '--cards-text:' . $textColor . ';' : '');
$scope = '#block-' . (int) $blockId;
$templateCss = $scope . ' .block-cards{--cards-cols:' . $columns . ';' . $cardStyle . '}';
$templateCss .= $scope . ' .feature-card__icon{width:' . $iconBoxSize . 'px;height:' . $iconBoxSize . 'px;}';
$cardClasses = ($cardBg !== '' ? ' block-cards--custom-bg' : '')
    . ($textColor !== '' ? ' block-cards--custom-text' : '')
    . ($iconBackground === 'off' ? ' block-cards--icons-no-bg' : '')
    . ' block-cards--icon-pos-' . $iconPosition
    . ' block-cards--text-align-' . $textAlign;

// Новый вид включается только у выбранного экземпляра cards_grid: базовые
// .feature-card и остальные карточки сайта остаются нетронутыми.
if ($variant === 'icon' && $visualStyle === 'new') {
    $cardClasses .= ' block-cards--style-new';
    $templateCss .= $scope . ' .block-cards--style-new .cards-grid{gap:clamp(20px,2.4vw,36px);}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card{background:var(--card-bg,transparent);border:0;border-top:1px solid rgba(23,58,99,.16);border-radius:0;box-shadow:none;min-height:0;padding:26px 0 30px;transform:none;overflow:visible;transition:border-color .18s ease,color .18s ease,background-color .18s ease;}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card::before{display:none;}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card:hover{background:var(--card-bg,transparent);border-top-color:var(--gov-accent,#17999b);box-shadow:none;transform:none;}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card__top{align-items:flex-start;margin-bottom:clamp(22px,2.3vw,34px);}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card__icon{width:' . $iconSize . 'px;height:' . $iconSize . 'px;border:0;border-radius:0;background:transparent;color:var(--gov-accent,#17999b);justify-content:flex-start;}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card__num{color:var(--gov-accent,#17999b);font-size:var(--font-size-meta,.75rem);font-weight:700;letter-spacing:var(--meta-letter-spacing,.12em);}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card__title{color:var(--cards-text,var(--gov-primary,#173a63));font-size:var(--font-size-h3,clamp(18px,1.35vw,21px));line-height:1.25;margin-bottom:10px;}';
    $templateCss .= $scope . ' .block-cards--style-new .feature-card__text{color:var(--cards-text,var(--gov-text,#334155));max-width:34ch;}';
}
?>
<?php if ($variant === 'image' || $imageBelow): ?>
    <?php $carousel = count($items) > 1; $desktopCarousel = count($items) > 4; ?>
    <div class="block-imgcards<?= $imageBelow ? ' block-imgcards--below' : '' ?>"<?= $carousel ? ' data-carousel' : '' ?>>
        <div class="section-head">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= \App\Core\TitleMarkup::html($title) ?></h2><?php endif; ?>
            <div class="section-head__tools">
                <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
                <?php if ($carousel): ?>
                    <span class="carousel-nav" data-carousel-nav hidden>
                        <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
                        <span class="carousel-nav__dots" data-carousel-dots role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>"></span>
                        <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($items === []): ?>
            <p class="block-imgcards__empty"><?= htmlspecialchars(t('Карточки ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="imgcards-grid<?= $desktopCarousel ? ' imgcards-grid--carousel' : ($carousel ? ' imgcards-grid--mobile-carousel' : '') ?>"<?= $carousel ? ' data-carousel-track tabindex="0" role="group" aria-label="' . htmlspecialchars(t('Карточки — прокрутка вбок'), ENT_QUOTES) . '"' : '' ?>>
                <?php foreach ($items as $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); $image = trim((string) ($item['image'] ?? '')); ?>
                    <?php if ($url !== ''): ?>
                    <a class="imgcard<?= $imageBelow ? ' imgcard--below' : '' ?>"<?= $carousel ? ' data-carousel-item' : '' ?> href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                    <?php else: ?>
                    <div class="imgcard<?= $imageBelow ? ' imgcard--below' : '' ?>"<?= $carousel ? ' data-carousel-item' : '' ?>>
                    <?php endif; ?>
                        <?php if ($image !== ''): ?>
                            <?= Media::picture($image, (string) ($item['title'] ?? ''), null, null, 'imgcard__media ' . $mediaClasses, true, '(max-width: 700px) 100vw, 25vw') ?>
                        <?php else: ?>
                            <span class="imgcard__media" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php if (!$imageBelow): ?><span class="imgcard__overlay"></span><?php endif; ?>
                        <span class="imgcard__body">
                            <span class="imgcard__title"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                            <?php if (!empty($item['text'])): ?><span class="imgcard__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                            <?php if ($url !== ''): ?><span class="imgcard__more"><?= htmlspecialchars(t('Подробнее'), ENT_QUOTES) ?> →</span><?php endif; ?>
                        </span>
                    <?php if ($url !== ''): ?></a><?php else: ?></div><?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($variant === 'compact'): ?>
    <div class="block-categories">
        <?php if ($title !== ''): ?><h2 class="block-categories__title"><?= \App\Core\TitleMarkup::html($title) ?></h2><?php endif; ?>
        <?php if ($items === []): ?>
            <p class="block-categories__empty"><?= htmlspecialchars(t('Категории ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="cat-grid">
                <?php foreach ($items as $index => $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); ?>
                    <?php if ($url !== ''): ?>
                    <a class="cat-tile<?= $index === 0 ? ' is-active' : '' ?>" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                    <?php else: ?>
                    <span class="cat-tile<?= $index === 0 ? ' is-active' : '' ?>">
                    <?php endif; ?>
                        <?php if (!empty($item['icon_svg'])): ?><span class="cat-tile__icon" aria-hidden="true"><?= Icon::render($item['icon_svg'], 28) ?></span><?php endif; ?>
                        <span class="cat-tile__label"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                    <?php if ($url !== ''): ?></a><?php else: ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="block-cards<?= $cardClasses ?>">
        <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
            <div class="section-head">
                <?php if ($title !== ''): ?><h2 class="section-head__title"><?= \App\Core\TitleMarkup::html($title) ?></h2><?php endif; ?>
                <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($items === []): ?>
            <p class="block-cards__empty"><?= htmlspecialchars(t('Пункты ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($items as $index => $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); $hasIcon = !empty($item['icon_svg']); ?>
                    <?php if ($url !== '' && $hasIcon): ?>
                    <a class="feature-card feature-card--has-icon" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                    <?php elseif ($url !== ''): ?>
                    <a class="feature-card" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                    <?php elseif ($hasIcon): ?>
                    <article class="feature-card feature-card--has-icon">
                    <?php else: ?>
                    <article class="feature-card">
                    <?php endif; ?>
                        <div class="feature-card__top">
                            <?php if ($hasIcon): ?>
                                <span class="feature-card__icon" aria-hidden="true"><?= Icon::render($item['icon_svg'], $iconSize) ?></span>
                            <?php else: ?>
                                <span class="feature-card__spacer"></span>
                            <?php endif; ?>
                            <span class="feature-card__num" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                        </div>
                        <div class="feature-card__content">
                            <h3 class="feature-card__title"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></h3>
                            <?php if (!empty($item['text'])): ?><p class="feature-card__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></p><?php endif; ?>
                        </div>
                    <?php if ($url !== ''): ?></a><?php else: ?></article><?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

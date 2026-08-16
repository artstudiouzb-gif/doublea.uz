<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? '')) ? $data['card_bg'] : '';
$textColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? $data['text_color'] : '';
$iconSize = max(16, min(64, (int) ($data['icon_size'] ?? 28)));
$iconBoxSize = max(42, $iconSize + 22);
$iconBackground = ($data['icon_bg'] ?? 'on') === 'off' ? 'off' : 'on';
$iconPositionRaw = (string) ($data['icon_position'] ?? 'left');
$iconPosition = in_array($iconPositionRaw, ['top', 'left', 'right', 'center'], true) ? $iconPositionRaw : 'left';
$textAlignRaw = (string) ($data['text_align'] ?? 'left');
$textAlign = in_array($textAlignRaw, ['left', 'center', 'right'], true) ? $textAlignRaw : 'left';
$variantRaw = (string) ($data['variant'] ?? 'row');
$variant = in_array($variantRaw, ['row', 'cards'], true) ? $variantRaw : 'row';
$valueSizeRaw = (string) ($data['value_size'] ?? 'normal');
$valueSize = in_array($valueSizeRaw, ['normal', 'large'], true) ? $valueSizeRaw : 'normal';
$cstyle = '--counter-icon-size:' . $iconSize . 'px;--counter-icon-box-size:' . $iconBoxSize . 'px;'
    . ($cardBg !== '' ? '--counters-bg:' . $cardBg . ';' : '')
    . ($textColor !== '' ? '--counters-text:' . $textColor . ';' : '');
$templateCss = '#block-' . $blockId . ' .block-counters{' . $cstyle . '}';
$blockClasses = ($iconBackground === 'off' ? ' block-counters--icons-no-bg' : '')
    . ' block-counters--icon-pos-' . $iconPosition
    . ' block-counters--text-align-' . $textAlign
    . ' block-counters--' . $variant
    . ' block-counters--size-' . $valueSize;
?>
<div class="block-counters<?= $blockClasses ?>">
    <?php if ($title !== ''): ?><h2 class="block-counters__title"><?= \App\Core\TitleMarkup::html($title) ?></h2><?php endif; ?>
    <div class="block-counters__grid">
        <?php foreach ($items as $item):
            $value = (string) ($item['value'] ?? '');
            // Отсчёт включается только для чистого числа: «24/7» и «№1»
            // анимировать нечем, а разряды в «1 200» скрипт бы потерял.
            $countable = preg_match('/^\d{1,9}$/', $value) === 1;
            $link = (string) ($item['link'] ?? '');
            $note = (string) ($item['note'] ?? '');
            $prefix = (string) ($item['prefix'] ?? '');
            $tag = $link !== '' ? 'a' : 'div';
        ?>
            <<?= $tag ?> class="counter<?= $link !== '' ? ' counter--link' : '' ?>"<?= $link !== '' ? ' href="' . htmlspecialchars($link, ENT_QUOTES) . '"' : '' ?>>
                <?php if (!empty($item['icon_svg'])): ?>
                    <span class="counter__icon" aria-hidden="true"><?= \App\Core\Icon::render($item['icon_svg'], $iconSize) ?></span>
                <?php endif; ?>
                <div class="counter__body">
                    <div class="counter__num">
                        <?php if ($prefix !== ''): ?><span class="counter__prefix"><?= htmlspecialchars($prefix, ENT_QUOTES) ?></span><?php endif; ?>
                        <span class="counter__value"<?= $countable ? ' data-counter-target="' . htmlspecialchars($value, ENT_QUOTES) . '"' : '' ?>><?= htmlspecialchars($value, ENT_QUOTES) ?></span>
                        <?php if (!empty($item['suffix'])): ?><span class="counter__suffix"><?= htmlspecialchars($item['suffix'], ENT_QUOTES) ?></span><?php endif; ?>
                    </div>
                    <div class="counter__label"><?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?></div>
                    <?php if ($note !== ''): ?><div class="counter__note"><?= htmlspecialchars($note, ENT_QUOTES) ?></div><?php endif; ?>
                </div>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
</div>

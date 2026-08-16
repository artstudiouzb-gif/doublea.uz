<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\HtmlSanitizer;
use App\Core\Icon;
use App\Core\TextProcessor;

final class AdvantagesBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        $items = [];
        foreach ((array) ($input['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemTitle = trim((string) ($item['title'] ?? ''));
            $itemText = trim((string) ($item['text'] ?? ''));
            if ($itemTitle === '' && $itemText === '') {
                continue;
            }

            $iconSvg = Icon::cleanName($item['icon_svg'] ?? '');

            $items[] = [
                'icon_svg' => $iconSvg,
                'title' => BlockDataInput::plain($item, 'title', $locale),
                'text' => BlockDataInput::plain($item, 'text', $locale),
                'url' => BlockDataInput::safeLink($item['url'] ?? ''),
            ];
        }

        $variant = (string) ($input['variant'] ?? 'grid');

        return [
            'variant' => in_array($variant, ['grid', 'indexed', 'inline', 'band'], true) ? $variant : 'grid',
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'description' => TextProcessor::process(
                HtmlSanitizer::sanitizeText((string) ($input['description'] ?? '')),
                $locale,
            ),
            'all_text' => BlockDataInput::trimmed($input, 'all_text'),
            'all_url' => BlockDataInput::safeLink($input['all_url'] ?? ''),
            // 0 — колонки подбираются по числу карточек (прежнее поведение).
            'columns' => BlockDataInput::int($input, 'columns', 0, 5, 0),
            'items' => $items,
        ];
    }
}

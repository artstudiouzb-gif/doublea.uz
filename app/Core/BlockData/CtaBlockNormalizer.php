<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Icon;
use App\Core\MediaPosition;

final class CtaBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        // Вариант разбираем отдельной переменной: раньше значение по умолчанию
        // подставлялось только в условии (`?? 'card'`), а в положительной ветке
        // читался `$input['variant']` напрямую. Если форма не присылала поле,
        // условие проходило по дефолту, а чтение бросало «Undefined array key»
        // и сохранение блока падало.
        $variant = (string) ($input['variant'] ?? 'card');
        if (!in_array($variant, ['card', 'band', 'media-dark', 'media-light'], true)) {
            $variant = 'card';
        }

        return [
            'variant' => $variant,
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'text' => BlockDataInput::plain($input, 'text', $locale),
            'icon_svg' => Icon::cleanName($input['icon_svg'] ?? ''),
            'image' => BlockDataInput::safeMedia($input['image'] ?? ''),
            'image_position' => MediaPosition::normalize($input['image_position'] ?? null),
            'image_position_mobile' => MediaPosition::normalize($input['image_position_mobile'] ?? null),
            'button_text' => BlockDataInput::trimmed($input, 'button_text'),
            'button_url' => BlockDataInput::safeLink($input['button_url'] ?? ''),
            'bg_color' => BlockDataInput::optionalColor($input, 'bg_color'),
            'text_color' => BlockDataInput::optionalColor($input, 'text_color'),
            'button_color' => BlockDataInput::optionalColor($input, 'button_color'),
        ];
    }
}

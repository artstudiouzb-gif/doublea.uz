<?php

declare(strict_types=1);

namespace App\Core\BlockData;

final class SubscribeBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        $variant = BlockDataInput::enum($input, 'variant', ['band', 'card', 'image'], 'band');
        $image = BlockDataInput::safeMedia($input['image'] ?? '');
        // Вариант «на фоне» без картинки читался бы белым по белому: без
        // изображения он равнозначен обычной полосе.
        if ($variant === 'image' && $image === '') {
            $variant = 'band';
        }

        return [
            'variant' => $variant,
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'text' => BlockDataInput::plain($input, 'text', $locale),
            'image' => $image,
            'placeholder' => BlockDataInput::trimmed($input, 'placeholder'),
            'note' => BlockDataInput::plain($input, 'note', $locale),
            'button_text' => BlockDataInput::trimmed($input, 'button_text'),
        ];
    }
}

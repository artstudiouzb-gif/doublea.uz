<?php

declare(strict_types=1);

use App\Core\BlockData\CtaBlockNormalizer;
use App\Core\BlockData\SubscribeBlockNormalizer;

test('CTA normalizer: сохраняет контракт, цвета и безопасную ссылку', function () {
    $data = CtaBlockNormalizer::normalize([
        'title_field' => '  Заголовок  ',
        'text' => '  Описание  ',
        'button_text' => ' Подробнее ',
        'button_url' => ' javascript:alert(1) ',
        'bg_color' => '#AABBCC',
        'text_color' => '#112233',
        'text_color_off' => '1',
        'button_color' => 'bad',
    ]);

    assert_same([
        'variant' => 'card',
        'title' => 'Заголовок',
        'text' => 'Описание',
        'icon_svg' => '',
        'image' => '',
        'image_position' => 'center-center',
        'image_position_mobile' => 'center-center',
        'button_text' => 'Подробнее',
        'button_url' => '',
        'bg_color' => '#aabbcc',
        'text_color' => '',
        'button_color' => '',
    ], $data);

    assert_same('/about', CtaBlockNormalizer::normalize(['button_url' => ' /about '])['button_url']);
});

test('CTA normalizer: сохраняет медиа-вариант, изображение и безопасную ссылку', function () {
    $data = CtaBlockNormalizer::normalize([
        'variant' => 'media-light',
        'title_field' => '  Баннер  ',
        'text' => '  Текст  ',
        'image' => ' /uploads/public/banner.jpg ',
        'image_position' => 'right-top',
        'image_position_mobile' => 'center-bottom',
        'button_text' => ' Открыть ',
        'button_url' => ' https://example.com/page ',
        'bg_color' => '#010203',
        'text_color' => '#A0B0C0',
        'button_color' => '#FFFFFF',
        'button_color_off' => '1',
    ]);

    assert_same([
        'variant' => 'media-light',
        'title' => 'Баннер',
        'text' => 'Текст',
        'icon_svg' => '',
        'image' => '/uploads/public/banner.jpg',
        'image_position' => 'right-top',
        'image_position_mobile' => 'center-bottom',
        'button_text' => 'Открыть',
        'button_url' => 'https://example.com/page',
        'bg_color' => '#010203',
        'text_color' => '#a0b0c0',
        'button_color' => '',
    ], $data);

    $invalid = CtaBlockNormalizer::normalize([
        'variant' => 'unknown',
        'button_url' => "https://example.com/\njavascript:alert(1)",
    ]);
    assert_same('card', $invalid['variant']);
    assert_same('', $invalid['button_url']);
});

test('Subscribe normalizer: сохраняет простой текстовый контракт', function () {
    assert_same([
        'variant' => 'band',
        'title' => 'Подписка',
        'text' => 'Получайте новости',
        'image' => '',
        'placeholder' => '',
        'note' => '',
        'button_text' => 'Подписаться',
    ], SubscribeBlockNormalizer::normalize([
        'title_field' => '  Подписка  ',
        'text' => '  Получайте новости  ',
        'button_text' => ' Подписаться ',
    ]));
});

test('Контроллер делегирует простые блоки отдельным нормализаторам', function () {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');

    assert_contains('CtaBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_contains('SubscribeBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_not_contains('BannerBlockNormalizer', $controller);
});

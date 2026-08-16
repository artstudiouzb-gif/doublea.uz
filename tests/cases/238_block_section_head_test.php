<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\BlockTypeRegistry;
use App\Core\ContactLink;
use App\Core\SectionHead;

// Общая шапка секции и кликабельные контакты: раньше каждый блок собирал
// разметку сам, и ссылка «Все …» пряталась вместе с заголовком, а телефоны в
// контактных карточках оставались простым текстом.

test('Шапка секции: ссылка «все» не зависит от заголовка', function () {
    $withoutTitle = SectionHead::render(['all_text' => 'Все новости', 'all_url' => '/news']);
    assert_contains('href="/news"', $withoutTitle, 'без заголовка ссылка обязана остаться');
    assert_contains('Все новости', $withoutTitle);
    assert_not_contains('section-head__title', $withoutTitle);

    $withTitle = SectionHead::render(['title' => 'Новости', 'all_text' => 'Все', 'all_url' => '/news']);
    assert_contains('<h2 class="section-head__title">Новости</h2>', $withTitle);
    assert_contains('section-head__copy', $withTitle);
});

test('Шапка секции: пустая шапка не оставляет разметки, опасный адрес отбрасывается', function () {
    assert_same('', SectionHead::render([]));
    assert_same('', SectionHead::render(['title' => '   ']));

    $unsafe = SectionHead::render(['title' => 'Раздел', 'all_text' => 'Все', 'all_url' => 'javascript:alert(1)']);
    assert_not_contains('javascript:', $unsafe);
    assert_not_contains('section-head__all', $unsafe);

    // Адрес есть, а подписи нет — ссылка не должна пропасть.
    $fallback = SectionHead::render(['all_url' => '/projects']);
    assert_contains('section-head__all', $fallback);
});

test('Шапка секции: заголовок и вводный текст экранируются', function () {
    $html = SectionHead::render([
        'title' => 'Отчёт <b>2026</b>',
        'description' => 'Текст с <script>alert(1)</script>',
    ]);
    assert_not_contains('<b>', $html);
    assert_not_contains('<script>', $html);
    assert_contains('&lt;b&gt;', $html);

    // Готовую разметку пропускаем только по явному флагу.
    $rich = SectionHead::render(['description' => '<p>Абзац</p>', 'description_html' => true]);
    assert_contains('<p>Абзац</p>', $rich);
    assert_contains('rich-content', $rich);
});

test('Контакты: телефон и почта становятся ссылками', function () {
    $phone = ContactLink::linkify('Приёмная: +998 71 200-00-00');
    assert_contains('href="tel:+998712000000"', $phone);
    assert_contains('+998 71 200-00-00', $phone);

    $mail = ContactLink::linkify('info@asr.uz');
    assert_contains('href="mailto:info@asr.uz"', $mail);

    // Короткий номер и диапазон годов ссылками не становятся.
    assert_not_contains('tel:', ContactLink::linkify('Кабинет 214'));
    assert_not_contains('tel:', ContactLink::linkify('2020–2026'));

    // Строка экранируется целиком.
    $unsafe = ContactLink::linkify('<script>alert(1)</script> +998 71 200-00-00');
    assert_not_contains('<script>', $unsafe);
});

test('Контакты: мини-иконка подбирается по содержимому строки', function () {
    assert_same('phone', ContactLink::iconFor('Приёмная: +998 71 200-00-00'));
    assert_same('mail', ContactLink::iconFor('info@asr.uz'));
    assert_same('clock', ContactLink::iconFor('Пн–Пт 9:00–18:00'));
    // Часы важнее телефона: «9:00–18:00» под шаблон номера тоже подходит.
    assert_same('clock', ContactLink::iconFor('Обед 13:00–14:00'));
    assert_same(null, ContactLink::iconFor('Ташкент, ул. Примерная, 1'));
});

test('Контакты: вариант в строку, размер иконки, подложка и своя картинка', function () {
    $items = [[
        'title' => 'Телефон',
        'icon_svg' => 'phone',
        'lines' => "Приёмная: +998 71 200-00-00\nПн–Пт 9:00–18:00",
    ]];

    $block = BlockRenderer::render([
        'id' => 830,
        'type' => 'contact_cards',
        'data' => json_encode([
            'variant' => 'inline',
            'icon_size' => 30,
            'icon_bg' => 'off',
            'line_icons' => true,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    $html = (string) $block['html'];

    assert_contains('block-contact-cards--inline', $html);
    assert_contains('block-contact-cards--icon-bg-off', $html);
    assert_contains('--feature-card-icon-size:30px', (string) $block['css']);
    // Без подложки плитка исчезает — остаётся только значок.
    assert_contains('background:none', (string) $block['css']);
    assert_not_contains('width:52px', (string) $block['css']);
    assert_contains('contact-card__line-icon', $html);
    assert_contains('contact-card__line--icon', $html);

    // Выключенные мини-иконки строк не рисуются.
    $noIcons = BlockRenderer::render([
        'id' => 831,
        'type' => 'contact_cards',
        'data' => json_encode(['line_icons' => false, 'items' => $items], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    assert_not_contains('contact-card__line-icon', (string) $noIcons['html']);

    // Своя картинка вытесняет иконку Tabler, небезопасный адрес отбрасывается.
    $custom = BlockRenderer::render([
        'id' => 832,
        'type' => 'contact_cards',
        'data' => json_encode([
            'items' => [['title' => 'Телефон', 'icon_svg' => 'phone', 'icon_image' => '/uploads/public/icon.svg', 'lines' => '+998 71 200-00-00']],
        ], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    assert_contains('contact-card__icon-img', (string) $custom['html']);
    assert_not_contains('contact-card__icon-svg', (string) $custom['html']);
});

test('Слайдер: пропорция, автопрокрутка и ссылка со слайда', function () {
    $rendered = BlockRenderer::render([
        'id' => 810,
        'type' => 'slider',
        'data' => json_encode([
            'title' => 'Галерея',
            'ratio' => '4-3',
            'autoplay' => 6,
            'slides' => [
                ['image' => '/uploads/public/a.jpg', 'caption' => 'Первый', 'url' => '/projects/one'],
                ['image' => '/uploads/public/b.jpg', 'caption' => 'Второй', 'url' => 'javascript:alert(1)'],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    $html = (string) $rendered['html'];

    assert_contains('block-slider--ratio-4-3', $html);
    assert_contains('data-autoplay="6"', $html);
    assert_contains('href="/projects/one"', $html);
    assert_not_contains('javascript:', $html, 'небезопасная ссылка слайда отбрасывается');
    assert_contains('section-head__title', $html, 'заголовок блока выводится общей шапкой');

    // Выключенная автопрокрутка атрибута не оставляет: скрипт стартует по нему.
    $still = BlockRenderer::render([
        'id' => 811,
        'type' => 'slider',
        'data' => json_encode(['slides' => [['image' => '/uploads/public/a.jpg']]], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    assert_not_contains('data-autoplay', (string) $still['html']);
});

test('Блоки новостей и проектов знают о ссылке «все» и колонках', function () {
    $news = BlockTypeRegistry::defaultsFor('news_latest');
    assert_true(array_key_exists('all_text', $news), 'подпись ссылки настраивается');
    assert_true(array_key_exists('all_url', $news), 'адрес ссылки настраивается');

    $projects = BlockTypeRegistry::defaultsFor('projects_list');
    assert_true(array_key_exists('all_url', $projects));
    assert_same(3, $projects['columns'], 'по умолчанию три колонки');
});

<?php

declare(strict_types=1);

use App\Core\FooterConfig;

test('FooterConfig: колонки валидируются, мусор-виджеты отброшены, лимит 4', function () {
    $cfg = FooterConfig::normalize([
        'style' => 'columns',
        'columns' => [
            ['heading' => 'A', 'widget' => 'about'],
            ['heading' => 'M', 'widget' => 'нечто'],   // неизвестный виджет — выброс
            ['heading' => 'C', 'widget' => 'contacts'],
            ['heading' => 'S', 'widget' => 'social'],
            ['heading' => 'T', 'widget' => 'text', 'text' => 'x'],
            ['heading' => 'X', 'widget' => 'menu'],     // 5-я валидная — за лимитом
        ],
        'bottom' => '',
    ]);

    $widgets = array_map(fn ($c) => $c['widget'], $cfg['columns']);
    assert_same(['about', 'contacts', 'social', 'text'], $widgets, 'мусор убран, лимит 4 колонки');
    assert_same('© {year} {site}', $cfg['bottom'], 'пустая нижняя строка → дефолт');
});

test('FooterConfig: текстовый виджет санируется, прочие не несут text', function () {
    $cfg = FooterConfig::normalize([
        'columns' => [
            ['heading' => 'T', 'widget' => 'text', 'text' => '<p>ok</p><script>alert(1)</script>'],
            ['heading' => 'M', 'widget' => 'menu', 'text' => '<b>лишнее</b>'],
        ],
    ]);
    assert_true(!str_contains($cfg['columns'][0]['text'], '<script'), 'скрипт вырезан');
    assert_contains('<p>ok</p>', $cfg['columns'][0]['text'], 'безопасный HTML сохранён');
    assert_same('', $cfg['columns'][1]['text'], 'не-текстовый виджет не хранит text');
});

test('FooterConfig::renderBottom разворачивает {year} и {site}', function () {
    $out = FooterConfig::renderBottom('© {year} {site} · Все права', 'Мой Сайт');
    assert_contains(date('Y'), $out);
    assert_contains('Мой Сайт', $out);
    assert_true(!str_contains($out, '{year}') && !str_contains($out, '{site}'), 'плейсхолдеры заменены');
});

test('FooterConfig: недопустимый стиль → columns', function () {
    assert_same('columns', FooterConfig::normalize(['style' => 'zzz'])['style']);
    assert_same('minimal', FooterConfig::normalize(['style' => 'minimal'])['style']);
});

test('FooterConfig: виджет subscribe валиден', function () {
    $cfg = FooterConfig::normalize(['columns' => [['heading' => 'Подписка', 'widget' => 'subscribe']]]);
    assert_same('subscribe', $cfg['columns'][0]['widget']);
});

test('Footer: нижняя строка использует общую ширину сайта', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/frontend.css');
    assert_contains('max-width: var(--container-max, 1440px);', $css);
    assert_contains('padding: 0 20px;', $css);
    assert_not_contains('max-width: var(--content-width, 1200px);', $css);
});

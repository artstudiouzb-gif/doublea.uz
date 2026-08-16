<?php

declare(strict_types=1);

use App\Core\BlockData\BlockPresentationNormalizer;
use App\Core\BlockRenderer;
use App\Core\Hero\HeroSlideData;
use App\Models\HeroSlide;
use App\Models\HeroSlideTranslation;

// Фоновая надпись — крупное слово за содержимым (у слайда обложки и у секции).
// Это декорация из текста, поэтому у неё три обязательства: перевод, молчание
// у диктора и прозрачность для мыши. Размер и место задаются числами: нужный
// кегль зависит от длины слова, пресетом его не угадать.

test('Фоновая надпись слайда: размер и смещение числами, с границами', function () {
    $d = HeroSlideData::normalize([
        'watermark' => '  aerion  ',
        'watermark_size' => '30',
        'watermark_x' => 'right',
        'watermark_y' => 'top',
        'watermark_dx' => '-40',
        'watermark_dy' => '15',
        'watermark_opacity' => '18',
    ]);
    assert_same('aerion', $d['watermark']);
    assert_same(30, $d['watermark_size']);
    assert_same('right', $d['watermark_x']);
    assert_same('top', $d['watermark_y']);
    assert_same(-40, $d['watermark_dx']);
    assert_same(15, $d['watermark_dy']);
    assert_same(18, $d['watermark_opacity']);

    // Пусто — умолчание, а не край диапазона и не ноль: иначе очистка поля
    // схлопывала бы надпись в точку.
    $empty = HeroSlideData::normalize(['watermark' => 'x', 'watermark_size' => '', 'watermark_opacity' => '']);
    assert_same(22, $empty['watermark_size']);
    assert_same(12, $empty['watermark_opacity']);

    // За границы не выпускаем: 300vw — это надпись шириной в три экрана.
    $huge = HeroSlideData::normalize(['watermark' => 'x', 'watermark_size' => '300', 'watermark_dx' => '999']);
    assert_same(60, $huge['watermark_size']);
    assert_same(100, $huge['watermark_dx']);

    // Чужие значения привязки откатываются к умолчанию, а не попадают в класс.
    $bad = HeroSlideData::normalize(['watermark' => 'x', 'watermark_x' => 'куда-нибудь']);
    assert_same('center', $bad['watermark_x']);
});

test('Свой CSS-класс слайда: только имена классов, всё остальное отбрасывается', function () {
    // Класс уходит в атрибут class — набор символов ограничен жёстко.
    $d = HeroSlideData::normalize(['css_class' => 'hero-fantasy 123bad --nope hero_2']);
    assert_same('hero-fantasy hero_2', $d['css_class'], 'класс обязан начинаться с буквы');

    // Кавычка закрыла бы атрибут — такое имя не класс.
    $inject = HeroSlideData::normalize(['css_class' => 'ok" onload="alert(1)']);
    assert_not_contains('"', $inject['css_class']);
    assert_not_contains('onload', $inject['css_class'], 'обработчик события классом не притворяется');

    // Дубли и лишнее количество: пять — потолок.
    $many = HeroSlideData::normalize(['css_class' => 'a b c d e f g a']);
    assert_true(count(explode(' ', $many['css_class'])) <= 5);

    $tags = HeroSlideData::normalize(['css_class' => '<script>']);
    assert_same('', $tags['css_class']);
});

test('Фоновая надпись секции появляется только вместе с текстом', function () {
    // Без текста ключей оформления в данных блока нет: пустая надпись не
    // должна ни включать обрезку секции, ни плодить переменные в CSS.
    $off = BlockPresentationNormalizer::normalize(['watermark' => '   ', 'watermark_size' => '40']);
    assert_true(!array_key_exists('_watermark', $off));
    assert_true(!array_key_exists('_watermark_size', $off));

    $on = BlockPresentationNormalizer::normalize([
        'watermark' => 'TOP 5',
        'watermark_size' => '20',
        'watermark_x' => 'right',
        'watermark_y' => 'top',
        'watermark_dy' => '-15',
        'watermark_opacity' => '9',
    ]);
    assert_same('TOP 5', $on['_watermark']);
    assert_same(20, $on['_watermark_size']);
    assert_same('right', $on['_watermark_x']);
    assert_same('top', $on['_watermark_y']);
    assert_same(-15, $on['_watermark_dy']);
    assert_same(9, $on['_watermark_opacity']);
});

test('Разметка надписи: молчит у диктора и не перехватывает клики', function () {
    $rendered = BlockRenderer::render([
        'id' => 42,
        'type' => 'text',
        'data' => json_encode([
            'content' => 'Текст секции',
            '_watermark' => 'TOP 5',
            '_watermark_size' => 20,
            '_watermark_x' => 'right',
            '_watermark_y' => 'top',
            '_watermark_opacity' => 9,
        ], JSON_UNESCAPED_UNICODE),
    ]);
    $html = (string) $rendered['html'];

    assert_contains('cms-block--has-watermark', $html);
    assert_contains('cms-block__watermark--x-right', $html);
    assert_contains('cms-block__watermark--y-top', $html);
    // Диктор не должен читать декорацию посреди содержимого секции.
    assert_contains('aria-hidden="true">TOP 5</span>', $html);

    // Прозрачность и размер — переменными в scoped CSS: инлайн-стили в блоках
    // запрещены, их стережёт отдельный тест.
    $css = (string) $rendered['css'];
    assert_contains('--block-watermark-opacity:0.09', $css);
    assert_contains('--block-watermark-size:20vw', $css);
    assert_not_contains('style="', $html);

    // Слово шире секции обязано обрезаться её краем, а не растягивать
    // страницу; содержимое лежит над надписью, иначе теряет контраст.
    $theme = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    assert_contains('.cms-block--has-watermark', $theme);
    assert_contains('overflow-x: clip', $theme);
    assert_contains('pointer-events: none', $theme);
    assert_contains('.cms-block--has-watermark > *:not(.cms-block__watermark)', $theme);

    $hero = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    assert_contains('.hero__watermark--x-center', $hero);
    assert_contains('pointer-events: none', $hero);
});

test('Фоновая надпись слайда переводится наравне с заголовком', function () {
    // Слайд один на все языки, переводится только текст (механизм А) —
    // значит надпись обязана быть и в списке полей перевода, и в наложении.
    assert_true(in_array('watermark', HeroSlideTranslation::FIELDS, true));

    $row = HeroSlide::applyTranslation(
        ['id' => 1, 'data' => ['watermark' => 'aerion', 'title' => 'Заголовок']],
        ['watermark' => 'agentlik']
    );
    assert_same('agentlik', $row['data']['watermark']);

    // Пустой перевод не затирает основной язык.
    $keep = HeroSlide::applyTranslation(
        ['id' => 1, 'data' => ['watermark' => 'aerion']],
        ['watermark' => '  ']
    );
    assert_same('aerion', $keep['data']['watermark']);
});

test('Размер надписи не берётся из шрифтовой шкалы', function () {
    // Шкала --step-* существует для читаемого текста; декорация во всю ширину
    // должна расти вместе с экраном, поэтому она в vw. Тест закрепляет выбор,
    // чтобы правка «привести к шкале» не сломала приём.
    $theme = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    assert_contains('font-size: var(--block-watermark-size, 22vw)', $theme);

    $hero = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    assert_contains('font-size: var(--hero-watermark-size, 22vw)', $hero);
});

<?php

declare(strict_types=1);

/**
 * Сторож дрейфа дизайн-системы.
 *
 * Проблема, ради которой он существует: в публичной теме накопились 73 разных
 * статичных размера шрифта, 163 тени и 606 !important. Значения вроде
 * 0.74/0.75/0.76/0.78rem — не решения, а следы ручной подгонки отдельных
 * компонентов. Когда общего словаря нет, единственный способ победить
 * соседнее правило — поднять приоритет, и каждая правка тянет за собой ещё
 * один !important.
 *
 * Тест НЕ требует привести всё в порядок разом. Он фиксирует потолок: числа
 * не должны расти. Тем же приёмом в проекте удержали шкалу брейкпоинтов
 * (тест 232) после схлопывания 33 значений в 8.
 *
 * Порог опускают ПО ФАКТУ чистки: перевёл компонент на токены из :root
 * (--step-*, --space-*, --shadow-*) — уменьшил число здесь. Поднимать
 * потолок, чтобы «прошло», нельзя: это ровно тот путь, которым 33
 * брейкпоинта когда-то и появились.
 */

const DESIGN_SCALE_CEILINGS = [
    // 73 -> 51 -> 12. Сначала схлопнуты соседние размеры, отличавшиеся меньше
    // чем на 2 %, затем вся типографика переведена на шкалу --step-*
    // (см. план, раздел 3.1). Оставшиеся 12 — значения в px и em: размеры
    // элементов интерфейса, а не текста. Потолок опущен по факту чистки —
    // так он и должен двигаться.
    // 13-е значение — 1.12em у .tx-mark в режиме «Рукописный шрифт»: рукописное
    // семейство оптически мельче наборного и выравнивается по РОДИТЕЛЬСКОМУ
    // заголовку, каким бы тот ни был. Ступень шкалы здесь не подходит — она
    // абсолютная и оторвала бы выделенное слово от строки, в которой стоит.
    'font-size' => 13,
    'box-shadow' => 161,
    'border-radius' => 36,
    // 610 -> 384. Из темы снято 194 приоритета: каждый снимали и сверяли
    // вычисленные стили всех элементов на 9 страницах в двух ширинах и двух
    // темах — возвращали те, без которых что-то менялось (см. план, 3.1).
    //
    // Сначала снято было 335, и CI поймал ошибку: печатная вёрстка. Снимок
    // берёт страницу в покое, в экранной медиасреде, без пользовательских
    // предпочтений и без классов, которые вешает JS, — всё, что живёт за
    // пределами этого состояния, он не проверяет вовсе. Поэтому приоритеты
    // возвращены целиком в @media print, prefers-reduced-motion,
    // prefers-contrast, forced-colors, (hover: none), (pointer: coarse) и у
    // селекторов состояний (.is-*, [aria-*], [data-a11y], .no-transition).
    //
    // Оставшиеся 384 — законные случаи: настройка из админки обязана перебить
    // тему, состояние обязано перебить покой, а сброс движения объявлен
    // селектором «*» и иначе проигрывает компонентам по специфичности.
    // Порог опускают по факту чистки; «поднять, чтобы прошло» — не повод.
    '!important' => 384,
];

/** Публичный CSS целиком: тема, её вынесенные части и слои поверх неё. */
function public_design_css(): string
{
    static $css = null;
    if ($css !== null) {
        return $css;
    }

    $files = [
        'frontend.css',
        'gov-theme.css',
        'public-layout-polish.css',
        'public-editorial-pages.css',
        'rich-content.css',
        'a11y.css',
    ];
    $paths = array_map(static fn (string $f): string => APP_ROOT . '/public/assets/css/' . $f, $files);

    // Части, вынесенные из темы (THEME_PART_MAP), — это та же дизайн-система,
    // просто в отдельных файлах: их считаем. А самостоятельные ассеты блоков
    // из CSS_MAP — нет: у такого блока свой визуальный язык, к шкалам темы он
    // отношения не имеет. Сейчас CSS_MAP пуст, но фильтр оставлен, чтобы
    // метрика не сломалась, когда туда что-то добавят.
    $collector = (string) file_get_contents(APP_ROOT . '/app/Core/AssetCollector.php');
    $standalone = [];
    if (preg_match('/const CSS_MAP = \[(.*?)\];/s', $collector, $m) === 1) {
        preg_match_all("#'(/assets/css/[^']+)'#", $m[1], $found);
        $standalone = array_map(static fn (string $p): string => basename($p), $found[1]);
    }

    foreach (glob(APP_ROOT . '/public/assets/css/blocks/*.css') ?: [] as $file) {
        if (str_ends_with($file, '.min.css') || in_array(basename($file), $standalone, true)) {
            continue;
        }
        $paths[] = $file;
    }

    $css = '';
    foreach ($paths as $path) {
        $css .= (string) @file_get_contents($path) . "\n";
    }

    return $css;
}

/** @return list<string> уникальные статичные значения свойства */
function unique_static_values(string $css, string $property): array
{
    preg_match_all('/' . preg_quote($property, '/') . ':\s*([^;}]+)/', $css, $m);
    $values = [];
    foreach ($m[1] as $value) {
        $value = trim($value);
        // var() и none считать бессмысленно: это уже ссылка на токен.
        if ($value === '' || $value === 'none' || str_starts_with($value, 'var(')) {
            continue;
        }
        // «.92rem» и «0.92rem» — одно значение, ведущий ноль в CSS не
        // обязателен. Считаем по нормализованной записи, иначе метрика
        // ловила бы разнобой в оформлении, а не реальный дрейф шкалы.
        $value = (string) preg_replace('/(?<![\w.])\.(?=\d)/', '0.', $value);
        $value = (string) preg_replace('/(?<![\w.])(0\.\d*?)0+(?=[a-z%\s,)]|$)/', '$1', $value);
        if ($property === 'font-size' && preg_match('/^-?\d*\.?\d+(rem|px|em)$/', $value) !== 1) {
            // clamp()/calc() — плавные размеры, к пересчёту на ступени не относятся.
            continue;
        }
        $values[$value] = true;
    }

    return array_keys($values);
}

test('Число разных размеров шрифта в публичной теме не растёт', function (): void {
    $values = unique_static_values(public_design_css(), 'font-size');
    assert_true(
        count($values) <= DESIGN_SCALE_CEILINGS['font-size'],
        sprintf(
            'статичных font-size стало %d при потолке %d. Новые размеры берут из шкалы '
            . '--step-* в :root (gov-theme.css), а не подбирают заново',
            count($values),
            DESIGN_SCALE_CEILINGS['font-size']
        )
    );
});

test('Число разных теней не растёт', function (): void {
    $values = unique_static_values(public_design_css(), 'box-shadow');
    assert_true(
        count($values) <= DESIGN_SCALE_CEILINGS['box-shadow'],
        sprintf(
            'разных box-shadow стало %d при потолке %d. Ступени тени — --shadow-1..4 в :root',
            count($values),
            DESIGN_SCALE_CEILINGS['box-shadow']
        )
    );
});

test('Число разных радиусов не растёт', function (): void {
    $values = unique_static_values(public_design_css(), 'border-radius');
    assert_true(
        count($values) <= DESIGN_SCALE_CEILINGS['border-radius'],
        sprintf(
            'разных border-radius стало %d при потолке %d. Скругление задаёт админка '
            . '(--radius, --btn-radius), для «таблетки» есть --radius-pill',
            count($values),
            DESIGN_SCALE_CEILINGS['border-radius']
        )
    );
});

test('Число !important в публичной теме не растёт', function (): void {
    $count = preg_match_all('/!important/', public_design_css());
    assert_true(
        $count <= DESIGN_SCALE_CEILINGS['!important'],
        sprintf(
            '!important стало %d при потолке %d. Если правило проигрывает соседу — '
            . 'разбираться с порядком и специфичностью, а не поднимать приоритет',
            $count,
            DESIGN_SCALE_CEILINGS['!important']
        )
    );
});

test('Шкалы объявлены и не конфликтуют с переменными админки', function (): void {
    $theme = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');

    foreach (['--step-0', '--step-1', '--space-s', '--space-l', '--shadow-1', '--shadow-3', '--radius-pill'] as $token) {
        assert_contains($token . ':', $theme, 'токен ' . $token . ' объявлен');
    }

    // Скругление, отступы секций и базовый кегль задаёт админка: их печатает
    // DesignSettings::rootCss() в сгенерированный файл, который подключается
    // ПОСЛЕ темы. В теме они остаются только как значения по умолчанию (сайт
    // должен выглядеть прилично и до первого сохранения настроек), поэтому
    // проверяем не отсутствие, а именно наличие в обоих местах.
    $design = (string) file_get_contents(APP_ROOT . '/app/Core/DesignSettings.php');
    foreach (['--radius', '--card-gap', '--section-pad', '--btn-radius', '--base-font-size'] as $adminToken) {
        assert_contains($adminToken . ':', $design, $adminToken . ' задаётся админкой');
    }

    // А вот шкалы админка не задаёт — иначе они разъехались бы с настройками.
    foreach (['--step-0', '--space-s', '--shadow-1'] as $scaleToken) {
        assert_not_contains($scaleToken . ':', $design, $scaleToken . ' — шкала темы, не настройка');
    }
});

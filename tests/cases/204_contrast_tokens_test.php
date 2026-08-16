<?php

declare(strict_types=1);

use App\Core\AccentContrast;

/**
 * Контраст текста, зависящего от бренд-акцента. Акцент настраивается в
 * админке, поэтому «подобрали цвет на глаз» здесь не работает: при другом
 * акценте подбор рассыпается. Цвета считаются от акцента и обязаны держать
 * норму WCAG AA при любом его значении.
 */

test('Текст на заливке акцентом держит 4.5:1 при любом бренд-цвете', function () {
    // Бирюзовый агентства, тёмно-синий, жёлтый, светло-салатовый, чёрный.
    foreach (['#00a0a6', '#155182', '#ffd400', '#c8f169', '#111111', '#8a8a8a'] as $accent) {
        $text = AccentContrast::onFill($accent);
        $ratio = AccentContrast::ratio($text, $accent);
        assert_true($ratio >= 4.5, "текст на заливке {$accent} даёт {$ratio}:1");
    }

    // Белый на бирюзовом — ровно тот случай, ради которого токен появился.
    assert_true(AccentContrast::ratio('#ffffff', '#00a0a6') < 4.5, 'белый на бирюзовом не проходит норму');
    assert_same('#0b1a30', AccentContrast::onFill('#00a0a6'));
});

test('Тема отдаёт --on-accent, а ссылки им пользуются', function () {
    $css = \App\Core\SiteThemeCss::build([], \App\Core\HeaderConfig::DEFAULTS, false);
    assert_contains('--on-accent:', $css);

    $theme = theme_css();
    assert_contains(".media-tabs__tab.is-active { \n    color: #fff !important;", $theme);
    assert_contains('border-radius: var(--radius-sm, 10px)', $theme);

    // Ссылки в тексте — от --gov-teal-text (посчитан с поправкой), а не от
    // сырого акцента: сырой давал 3.19:1 на белом.
    $rich = (string) file_get_contents(APP_ROOT . '/public/assets/css/rich-content.css');
    assert_contains('--rich-link: var(--gov-teal-text', $rich);
    assert_contains('color: var(--rich-link)', $rich);
});

test('Ряд заголовка секции переносится и не рвёт вёрстку на 320px', function () {
    $theme = theme_css();

    // Ссылка «Все …» и стрелки карусели в одну строку на 320px не влезают:
    // без переноса блок вылезал за окно на 19px и давал скролл вбок.
    assert_contains('.section-head__tools { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; max-width: 100%; }', $theme);
});

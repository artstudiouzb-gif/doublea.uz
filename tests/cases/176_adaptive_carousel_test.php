<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

test('Адаптивная карусель подключена только к подходящим повторяющимся блокам', function (): void {
    $stages = BlockRenderer::render([
        'id' => 17601,
        'type' => 'stages',
        'data' => json_encode([
            'title' => 'Этапы',
            'items' => array_fill(0, 6, [
                'stage' => 'Этап',
                'title' => 'Работа',
                'status' => 'planned',
            ]),
        ], JSON_UNESCAPED_UNICODE),
    ])['html'];
    assert_contains('data-carousel', $stages);
    assert_contains('stages--carousel', $stages);
    assert_contains('data-carousel-track', $stages);
    assert_contains('data-carousel-dots', $stages);

    $partners = BlockRenderer::render([
        'id' => 17602,
        'type' => 'partners',
        'data' => json_encode([
            'title' => 'Партнёры',
            'items' => array_fill(0, 7, ['name' => 'Организация']),
        ], JSON_UNESCAPED_UNICODE),
    ])['html'];
    assert_contains('block-partners__grid--carousel', $partners);
    assert_contains('data-carousel-item', $partners);

    $docs = BlockRenderer::render([
        'id' => 17603,
        'type' => 'docs_list',
        'data' => json_encode([
            'title' => 'Документы',
            'items' => array_fill(0, 8, ['title' => 'Документ']),
        ], JSON_UNESCAPED_UNICODE),
    ])['html'];
    assert_not_contains('data-carousel', $docs, 'важные списки не должны скрываться за каруселью');
});

test('Единый движок управляет переполнением, клавиатурой и reduced motion', function (): void {
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');
    $css = theme_css();

    assert_contains("querySelectorAll('[data-carousel]')", $js);
    assert_contains("event.key === 'ArrowLeft'", $js);
    assert_contains("event.key === 'Home'", $js);
    assert_contains("prefers-reduced-motion: reduce", $js);
    assert_contains("positions = pagePositions()", $js);
    assert_contains('MAX_PROGRESS_DOTS = 7', $js, 'индикаторы не должны переполнять мобильную шапку');
    assert_contains('scrollToPosition', $js, 'переключение карточек должно использовать единое плавное движение');
    assert_contains('duration = 620', $js, 'переход между карточками не должен быть резким');
    assert_contains('Math.cos(progress * Math.PI)', $js, 'движение должно мягко ускоряться и замедляться');
    assert_not_contains("track.querySelector('.imgcard')", $js, 'движок не должен зависеть от одного типа карточки');

    assert_contains('.carousel-nav__dot.is-active', $css);
    assert_contains('.stages[data-carousel-track]', $css);
    assert_contains('.imgcards-grid--mobile-carousel', $css);
    assert_contains('@media (prefers-reduced-motion: reduce)', $css);
});

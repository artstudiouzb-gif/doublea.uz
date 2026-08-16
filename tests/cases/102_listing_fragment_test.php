<?php

declare(strict_types=1);

use App\Core\Fragment;
use App\Core\View;

test('Fragment: режим включается только параметром _fragment=1', function () {
    $backup = $_GET;

    $_GET = [];
    assert_false(Fragment::wanted(), 'обычный запрос — полная страница');

    $_GET = ['_fragment' => '1'];
    assert_true(Fragment::wanted());

    $_GET = ['_fragment' => '0'];
    assert_false(Fragment::wanted());

    $_GET = ['_fragment' => 'yes'];
    assert_false(Fragment::wanted(), 'значение проверяется строго');

    $_GET = $backup;
});

test('Партиал списка новостей: карточки, пагинация и пустое состояние (БД)', function () {
    ensure_test_db(); // Locale::url() спрашивает у БД язык по умолчанию
    $items = [
        ['slug' => 'first', 'title' => 'Первая новость', 'excerpt' => 'Аннотация', 'published_at' => '2026-07-01 10:00', 'badge' => 'Важно', 'image' => ''],
        ['slug' => 'second', 'title' => 'Вторая новость', 'excerpt' => '', 'published_at' => '2026-07-02 10:00', 'badge' => '', 'image' => ''],
    ];

    // Внутри рубрики крупной новости нет — только сетка карточек.
    $html = View::renderPartial('site/_news_list', [
        'items' => $items,
        'page' => 1,
        'pages' => 2,
        'category' => 'ekonomika',
    ]);
    assert_contains('Первая новость', $html);
    assert_contains('Вторая новость', $html);
    assert_not_contains('newslist-lead', $html, 'в рубрике крупной новости быть не должно');
    assert_contains('listing-pager', $html, 'при двух страницах нужна пагинация');
    assert_contains('category=', $html, 'пагинация сохраняет выбранную рубрику');
    // Фрагмент — только результаты, без обвязки страницы.
    assert_not_contains('<html', $html);
    assert_not_contains('listing-filter', $html);

    // Первая страница общего списка — первая новость крупной.
    $home = View::renderPartial('site/_news_list', ['items' => $items, 'page' => 1, 'pages' => 1, 'category' => '']);
    assert_contains('newslist-lead', $home);
    assert_not_contains('listing-pager', $home, 'одна страница — пагинация не нужна');

    $empty = View::renderPartial('site/_news_list', ['items' => [], 'page' => 1, 'pages' => 1, 'category' => '']);
    assert_contains('listing__empty', $empty);
});

test('Страница новостей включает тот же партиал в области результатов', function () {
    $view = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/site/news_index.php');
    assert_contains('data-listing', $view, 'корень списка размечен для JS');
    assert_contains('data-listing-results', $view, 'область результатов размечена');
    assert_contains("renderPartial('site/_news_list'", $view, 'разметка списка не продублирована');

    $catalog = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/site/content_index.php');
    assert_contains('data-listing-results', $catalog);
    assert_contains("renderPartial('site/_catalog_list'", $catalog);
    assert_contains('data-listing-form', $catalog, 'форма поиска перехватывается JS');
});

test('Ссылки фильтров и пагинации остаются обычными ссылками (работа без JS)', function () {
    $view = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/site/news_index.php');
    // Фильтры — <a href>, а не кнопки: без JS страница обязана открываться.
    assert_contains('<a class="listing-filter__item', $view);
    assert_not_contains('_fragment', $view, 'служебный параметр не попадает в разметку');
});

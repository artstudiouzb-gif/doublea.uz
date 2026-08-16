<?php

declare(strict_types=1);

use App\Core\DemoSeeder;

test('DemoSeeder RESET полностью заменяет контент эталонными данными (БД)', function (): void {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();

    // 1. Создаём созданный руками мусорный элемент
    $pdo->exec("INSERT INTO news (title, slug, excerpt, content, status, published_at, created_at) VALUES ('Junk News', 'junk-news-slug-test', 'junk', 'junk', 'published', NOW(), NOW())");
    $hasJunkBefore = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'junk-news-slug-test'")->fetchColumn();
    assert_true($hasJunkBefore, 'Мусорная новость создана в БД');

    // 2. Запускаем resetAndRun
    $c = DemoSeeder::resetAndRun($pdo);

    // 3. Проверяем, что мусорная новость удалилась, а демо-новости созданы
    $hasJunkAfter = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'junk-news-slug-test'")->fetchColumn();
    assert_false($hasJunkAfter, 'Мусорная новость удалена после RESET + DEMO');

    $hasDemoNews = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'zasedanie-strategiya-2030'")->fetchColumn();
    assert_true($hasDemoNews, 'Флагманская демо-новость пересоздана');

    assert_true($c['news'] > 0, 'Счётчик новостей > 0');
    assert_true($c['pages'] > 0, 'Счётчик страниц > 0');
    assert_true($c['menu'] >= 40, 'Создано полное двухъязычное меню');
    assert_true($c['meropriyatiya'] >= 3, 'Созданы мероприятия');
    assert_same([], DemoSeeder::verify($pdo), 'Встроенная проверка не обнаружила конфликтов');

    $directorParent = $pdo->query(
        "SELECT parent.slug
         FROM pages child
         INNER JOIN pages parent ON parent.id = child.parent_id
         WHERE child.slug = 'direktor' AND child.lang = 'ru'
         LIMIT 1"
    )->fetchColumn();
    assert_same('rukovodstvo', (string) $directorParent, 'Демо-страницы имеют актуальную иерархию');

    $secondRun = DemoSeeder::run($pdo);
    assert_same(0, array_sum($secondRun), 'Повторный запуск полностью идемпотентен');

    $version = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'demo_data_version' LIMIT 1")->fetchColumn();
    // Версию сверяем с самим сидером: она поднимается при каждой правке
    // демо-контента, и держать её копию в тесте — лишний ручной шаг.
    $seeder = (string) file_get_contents(APP_ROOT . '/app/Core/DemoSeeder.php');
    preg_match("/DEMO_VERSION = '([^']+)'/", $seeder, $m);
    assert_same($m[1] ?? '', (string) $version, 'Версия демо-комплекта сохранена');
});

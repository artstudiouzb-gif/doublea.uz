<?php

declare(strict_types=1);

use App\Core\AdminUi;

/**
 * Иконки разделов админки.
 *
 * Icon::render отдаёт неизвестный ключ пустой строкой, а ключ раздела и имя
 * иконки Tabler совпадают далеко не всегда: пункт меню молча оставался вовсе
 * без иконки. Так было у девятнадцати разделов из тридцати — заметили это
 * только по «Обложкам» и «Категориям новостей».
 */

/** @return list<string> ключи разделов из шапки админки */
function admin_nav_keys(): array
{
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/admin/layout/header.php');

    // Пункты объявлены как 'ключ' => ['/admin/…', t('Название')].
    preg_match_all("/'([a-z_]+)'\s*=>\s*\['\/admin/", $header, $m);
    $keys = array_values(array_unique($m[1]));
    // Дашборд объявлен отдельной строкой, не массивом.
    $keys[] = 'dashboard';

    return $keys;
}

test('У каждого раздела админки есть иконка', function () {
    $keys = admin_nav_keys();
    assert_true(count($keys) >= 25, 'ключи разделов вычитаны из шапки (найдено ' . count($keys) . ')');

    $without = [];
    foreach ($keys as $key) {
        if (trim(AdminUi::navigationIcon($key)) === '') {
            $without[] = $key;
        }
    }

    assert_same([], $without, 'разделы без иконки: ' . implode(', ', $without));
});

test('Разделы «Обложки» и «Категории новостей» получают свои иконки', function () {
    // Именно они и обнаружились пустыми у владельца.
    foreach (['heroes', 'news_categories'] as $key) {
        assert_true(
            str_contains(AdminUi::navigationIcon($key), '<svg'),
            'у раздела ' . $key . ' рисуется иконка'
        );
    }
});

<?php

declare(strict_types=1);

test('DB Doctor защищён CLI guard и проверяет схему, миграции и данные', function () {
    $doctor = (string) file_get_contents(APP_ROOT . '/database/doctor.php');

    assert_contains('Cli::assertCli()', $doctor);
    assert_contains('information_schema.TABLES', $doctor);
    assert_contains('information_schema.COLUMNS', $doctor);
    assert_contains('information_schema.STATISTICS', $doctor);
    assert_contains('information_schema.KEY_COLUMN_USAGE', $doctor);
    assert_contains('SELECT filename FROM migrations', $doctor);
    assert_contains('translation_group_id', $doctor);
    assert_contains('FOREIGN_KEY_CHECKS', $doctor);
});

test('Схема и миграция защищают очередь Web Push и авторов ревизий', function () {
    $schema = (string) file_get_contents(APP_ROOT . '/database/schema.sql');
    $migration = (string) file_get_contents(
        APP_ROOT . '/database/migrations/2026_07_30_database_integrity.sql'
    );

    foreach ([$schema, $migration] as $sql) {
        assert_contains('fk_webpush_queue_news', $sql);
        assert_contains('fk_block_revisions_user', $sql);
        assert_contains('ON DELETE CASCADE', $sql);
        assert_contains('ON DELETE SET NULL', $sql);
    }
});

test('Языковые батч-запросы используют реальные колонки базовых таблиц', function () {
    $album = (string) file_get_contents(APP_ROOT . '/app/Models/PhotoAlbum.php');
    $team = (string) file_get_contents(APP_ROOT . '/app/Models/TeamMember.php');
    $video = (string) file_get_contents(APP_ROOT . '/app/Models/Video.php');

    assert_not_contains('SELECT id, lang, title FROM photo_albums', $album);
    assert_not_contains('deleted_at IS NULL', $album);
    assert_contains('SELECT id, title FROM photo_albums', $album);

    assert_not_contains('SELECT id, lang, name FROM team_members', $team);
    assert_contains('SELECT id, name FROM team_members', $team);

    assert_not_contains('SELECT id, lang, title FROM videos', $video);
    assert_contains('SELECT id, title FROM videos', $video);
});

<?php

declare(strict_types=1);

test('release automation is fail-fast and includes backup, migrations and smoke check', function (): void {
    $root = dirname(__DIR__, 2);
    $release = (string) file_get_contents($root . '/scripts/release.php');
    $check = (string) file_get_contents($root . '/scripts/release_check.php');

    assert_contains('backup_worker.php', $release);
    assert_contains('database/migrate.php', $release);
    assert_contains('scripts/smoke.php', $release);
    assert_contains('exit($code)', $release);
    assert_contains("hash_file('sha256'", $release);
    assert_contains('RecursiveDirectoryIterator', $release);
    assert_contains("extension_loaded", $check);
    assert_contains("SELECT filename FROM migrations", $check);
    assert_contains("APP_DEBUG включён", $check);
});

test('release archive excludes repository-only security documentation', function (): void {
    $attributes = (string) file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

    assert_contains('/README.md export-ignore', $attributes);
    assert_contains('/SECURITY.md export-ignore', $attributes);
});

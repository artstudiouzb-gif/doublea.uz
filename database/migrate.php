<?php

declare(strict_types=1);

/*
 * CLI-система миграций ArtStudio CMS.
 *
 *   php database/migrate.php            — накатить все новые миграции
 *   php database/migrate.php status     — показать статус (применённые/новые)
 *
 * Сканирует database/migrations/*.sql в алфавитном порядке имён (используйте
 * префикс с датой: 2026_07_05_*.sql) и накатывает те, что ещё не записаны в
 * таблице migrations. DDL MySQL делает неявный COMMIT, поэтому каждая миграция
 * должна быть безопасна для повторного запуска после частичного сбоя. Имя
 * фиксируется в таблице только после успешного выполнения всего SQL-файла.
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/Database.php';

use App\Core\Config;
use App\Core\Database;

$config = require __DIR__ . '/../config/config.php';
Config::set($config);
Database::init($config['db']);
$pdo = Database::pdo();

$command = $argv[1] ?? 'migrate';

if ($command === 'status') {
    try {
        $applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    } catch (\Throwable) {
        $applied = [];
    }
    $applied = array_flip($applied);
    $files = glob(__DIR__ . '/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);

    fwrite(STDOUT, "Миграции:\n");
    foreach ($files as $file) {
        $name = basename($file);
        $mark = isset($applied[$name]) ? '[x] применена' : '[ ] новая';
        fwrite(STDOUT, "  {$mark}  {$name}\n");
    }
    exit(0);
}
if ($command !== 'migrate') {
    fwrite(STDERR, "Неизвестная команда. Используйте migrate или status.\n");
    exit(2);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migrations_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

$pending = array_filter($files, static fn ($f) => !isset($applied[basename($f)]));

if (empty($pending)) {
    fwrite(STDOUT, "Нет новых миграций.\n");
    exit(0);
}

$record = $pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (:filename, NOW())');

$lockName = 'asdr_cms_migrations_' . substr(hash('sha256', (string) ($config['db']['database'] ?? '')), 0, 24);
$lockStmt = $pdo->prepare('SELECT GET_LOCK(:name, 30)');
$lockStmt->execute([':name' => $lockName]);
if ((int) $lockStmt->fetchColumn() !== 1) {
    fwrite(STDERR, "Не удалось получить блокировку миграций. Другой процесс ещё работает.\n");
    exit(1);
}

$exitCode = 0;
try {
    foreach ($pending as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException("Файл миграции {$name} пуст или недоступен.");
        }

        fwrite(STDOUT, "Применяю {$name} ... ");
        // Многооператорный SQL выполняется целиком (миграции — доверенные файлы).
        $pdo->exec($sql);
        $record->execute([':filename' => $name]);
        fwrite(STDOUT, "ok\n");
    }
} catch (\Throwable $e) {
    fwrite(STDOUT, "ОШИБКА\n");
    fwrite(STDERR, 'Миграции остановлены: ' . $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
    $releaseStmt->execute([':name' => $lockName]);
}

if ($exitCode === 0) {
    fwrite(STDOUT, "Готово.\n");
}
exit($exitCode);

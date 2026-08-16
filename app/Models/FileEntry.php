<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Database;
use App\Core\MediaMetadataSchema;

final class FileEntry
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM files ORDER BY created_at DESC');

        return $stmt->fetchAll();
    }

    public static function filtered(array $params, bool $includeProtected = true): array
    {
        $q = trim((string) ($params['q'] ?? ''));
        $type = trim((string) ($params['type'] ?? ''));
        $sort = trim((string) ($params['sort'] ?? 'date_desc'));
        $date = trim((string) ($params['date'] ?? ''));

        $sql = 'SELECT * FROM files WHERE 1=1';
        $bind = [];

        if (!$includeProtected) {
            $sql .= " AND access_type = 'public'";
        }

        if ($q !== '') {
            $sql .= ' AND original_name LIKE :q';
            $bind[':q'] = '%' . $q . '%';
        }

        if ($type === 'image') {
            $sql .= " AND mime_type LIKE 'image/%'";
        } elseif ($type === 'video') {
            $sql .= " AND mime_type LIKE 'video/%'";
        } elseif ($type === 'document') {
            $sql .= " AND mime_type NOT LIKE 'image/%' AND mime_type NOT LIKE 'video/%'";
        }

        if ($date !== '' && preg_match('/^\d{4}-\d{2}$/', $date)) {
            $sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = :date";
            $bind[':date'] = $date;
        }

        $orderBy = match ($sort) {
            'date_asc' => 'created_at ASC',
            'size_desc' => 'size DESC',
            'size_asc' => 'size ASC',
            'name_asc' => 'original_name ASC',
            'name_desc' => 'original_name DESC',
            default => 'created_at DESC',
        };

        $sql .= ' ORDER BY ' . $orderBy;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);

        return $stmt->fetchAll();
    }

    public static function availableDates(): array
    {
        $stmt = Database::pdo()->query("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS date_val FROM files ORDER BY date_val DESC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Находит публичный файл по каноническому URL медиабиблиотеки.
     * Внешние URL и произвольные пути намеренно не сопоставляются.
     */
    public static function findPublicByUrl(string $url): ?array
    {
        $baseUrl = rtrim((string) Config::get('paths.public_uploads_url'), '/');
        $path = (string) (parse_url(trim($url), PHP_URL_PATH) ?? '');
        if ($baseUrl === '' || !str_starts_with($path, $baseUrl . '/')) {
            return null;
        }

        $relative = ltrim(substr($path, strlen($baseUrl)), '/');
        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, '/')) {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM files WHERE stored_name = :stored AND access_type = 'public' LIMIT 1"
        );
        $stmt->execute([':stored' => $relative]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO files (original_name, stored_name, mime_type, size, access_type, access_token, uploaded_by, created_at)
             VALUES (:original_name, :stored_name, :mime_type, :size, :access_type, :access_token, :uploaded_by, NOW())'
        );
        $stmt->execute([
            ':original_name' => $data['original_name'],
            ':stored_name' => $data['stored_name'],
            ':mime_type' => $data['mime_type'],
            ':size' => $data['size'],
            ':access_type' => $data['access_type'],
            ':access_token' => $data['access_token'],
            ':uploaded_by' => $data['uploaded_by'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Обновляет редакционные метаданные уже загруженного файла.
     *
     * @param array{alt_text?:?string,caption?:?string,description?:?string,credit?:?string,focal_x?:?int,focal_y?:?int} $metadata
     */
    public static function updateMetadata(int $id, array $metadata): ?array
    {
        MediaMetadataSchema::ensure();

        $file = self::findById($id);
        if ($file === null) {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE files
             SET alt_text = :alt_text,
                 caption = :caption,
                 description = :description,
                 credit = :credit,
                 focal_x = :focal_x,
                 focal_y = :focal_y,
                 metadata_updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':alt_text' => $metadata['alt_text'] ?? null,
            ':caption' => $metadata['caption'] ?? null,
            ':description' => $metadata['description'] ?? null,
            ':credit' => $metadata['credit'] ?? null,
            ':focal_x' => $metadata['focal_x'] ?? null,
            ':focal_y' => $metadata['focal_y'] ?? null,
            ':id' => $id,
        ]);

        return self::findById($id);
    }

    public static function regenerateToken(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = Database::pdo()->prepare('UPDATE files SET access_token = :token WHERE id = :id');
        $stmt->execute([':token' => $token, ':id' => $id]);

        return $token;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM files WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function publicUrl(array $file): string
    {
        return rtrim((string) Config::get('paths.public_uploads_url'), '/') . '/' . $file['stored_name'];
    }

    public static function protectedUrl(array $file): string
    {
        if (($file['access_type'] ?? '') !== 'protected' || empty($file['access_token'])) {
            throw new \InvalidArgumentException('Для файла не создан защищённый URL.');
        }

        return '/download.php?file_id=' . (int) $file['id']
            . '&token=' . rawurlencode((string) $file['access_token']);
    }
}

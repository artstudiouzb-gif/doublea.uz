<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Slug;

final class TeamMember
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM team_members ORDER BY sort_order ASC, id ASC');

        return $stmt->fetchAll();
    }

    public static function published(?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        if ($lang === Language::defaultCode()) {
            $stmt = Database::pdo()->query(
                "SELECT * FROM team_members WHERE status = 'published' ORDER BY sort_order ASC, id ASC"
            );

            return $stmt->fetchAll();
        }

        $stmt = Database::pdo()->prepare(
            "SELECT tm.* FROM team_members tm
             INNER JOIN team_member_translations tmt
                ON tmt.member_id = tm.id AND tmt.lang = :lang
               AND (
                    TRIM(COALESCE(tmt.name, '')) <> ''
                    OR TRIM(COALESCE(tmt.position, '')) <> ''
               )
             WHERE tm.status = 'published'
             ORDER BY tm.sort_order ASC, tm.id ASC"
        );
        $stmt->execute([':lang' => $lang]);
        $rows = $stmt->fetchAll();

        return self::localizeRows($rows, $lang);
    }

    /**
     * Накладывает перевод указанного языка на базовую строку. Пустые поля
     * перевода откатываются к значению основного языка (graceful fallback).
     */
    public static function localize(array $row, string $lang): array
    {
        return self::applyTranslation($row, TeamMemberTranslation::find((int) $row['id'], $lang));
    }

    /** @param array<int, array<string, mixed>> $rows @return array<int, array<string, mixed>> */
    private static function localizeRows(array $rows, string $lang): array
    {
        $translations = TeamMemberTranslation::forMemberIds(
            array_map(static fn (array $row): int => (int) $row['id'], $rows),
            $lang
        );
        return array_map(
            static fn (array $row): array => self::applyTranslation($row, $translations[(int) $row['id']] ?? null),
            $rows
        );
    }

    /**
     * Якорь отдела для ссылок из схемы оргструктуры. Считается от названия на
     * основном языке, поэтому одна и та же ссылка работает на всех языках.
     */
    public static function departmentSlug(array $row): string
    {
        $base = trim((string) ($row['department_base'] ?? $row['department'] ?? ''));

        return $base !== '' ? Slug::make($base) : '';
    }

    /**
     * Отделы опубликованных сотрудников в порядке сортировки команды.
     *
     * @return list<array{name: string, slug: string, count: int}>
     */
    public static function departments(?string $lang = null): array
    {
        $groups = [];
        foreach (self::published($lang) as $row) {
            $name = trim((string) ($row['department'] ?? ''));
            $slug = self::departmentSlug($row);
            if ($name === '' || $slug === '') {
                continue;
            }
            if (!isset($groups[$slug])) {
                $groups[$slug] = ['name' => $name, 'slug' => $slug, 'count' => 0];
            }
            $groups[$slug]['count']++;
        }

        return array_values($groups);
    }

    /**
     * Раскладывает сотрудников по секторам, а внутри сектора — по отделам и
     * группам. Сотрудники без сектора уходят в последнюю группу с пустым
     * названием — их не должно «потерять».
     *
     * @param array<int, array<string, mixed>> $rows
     * @return list<array{name: string, slug: string, members: list<array<string, mixed>>, units: list<array{name: string, members: list<array<string, mixed>>}>}>
     */
    public static function groupByDepartment(array $rows): array
    {
        $groups = [];
        $rest = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['department'] ?? ''));
            $slug = self::departmentSlug($row);
            if ($name === '' || $slug === '') {
                $rest[] = $row;
                continue;
            }
            if (!isset($groups[$slug])) {
                $groups[$slug] = ['name' => $name, 'slug' => $slug, 'members' => [], 'units' => []];
            }

            $unit = trim((string) ($row['unit'] ?? ''));
            if ($unit === '') {
                $groups[$slug]['members'][] = $row;
                continue;
            }
            if (!isset($groups[$slug]['units'][$unit])) {
                $groups[$slug]['units'][$unit] = ['name' => $unit, 'members' => []];
            }
            $groups[$slug]['units'][$unit]['members'][] = $row;
        }

        $result = [];
        foreach ($groups as $group) {
            $group['units'] = array_values($group['units']);
            $result[] = $group;
        }
        if ($rest !== []) {
            $result[] = ['name' => '', 'slug' => '', 'members' => $rest, 'units' => []];
        }

        return $result;
    }

    private static function applyTranslation(array $row, ?array $translation): array
    {
        // Базовое название отдела сохраняем до наложения перевода: якорь
        // ссылки не должен меняться вместе с языком страницы.
        $row['department_base'] = (string) ($row['department'] ?? '');
        if ($translation === null) {
            return $row;
        }
        foreach (['name', 'position', 'department', 'unit'] as $field) {
            if (isset($translation[$field]) && trim((string) $translation[$field]) !== '') {
                $row[$field] = $translation[$field];
            }
        }

        return $row;
    }

    /**
     * Языки контента для набора сотрудников одним запросом (без N+1).
     * Контент на языке = непустой перевод имени или должности.
     *
     * @param array<int|string> $ids
     * @return array<int, array<int, string>>
     */
    public static function availableLangsForIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = [];
        }
        if ($ids === []) {
            return $map;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $default = Language::defaultCode();

        try {
            $stmtBase = Database::pdo()->prepare(
                "SELECT id, name FROM team_members WHERE id IN ($in)"
            );
            $stmtBase->execute($ids);
            foreach ($stmtBase->fetchAll() as $row) {
                $id = (int) $row['id'];
                if (trim((string) ($row['name'] ?? '')) !== '' && isset($map[$id])) {
                    $map[$id][] = $default;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('TeamMember::availableLangsForIds: не удалось прочитать базовые записи', $e);
        }

        try {
            $stmtTrans = Database::pdo()->prepare(
                "SELECT member_id, lang FROM team_member_translations
                 WHERE member_id IN ($in)
                   AND (TRIM(COALESCE(name, '')) <> '' OR TRIM(COALESCE(position, '')) <> '')"
            );
            $stmtTrans->execute($ids);
            foreach ($stmtTrans->fetchAll() as $row) {
                $id = (int) $row['member_id'];
                $lang = (string) $row['lang'];
                if (isset($map[$id]) && !in_array($lang, $map[$id], true)) {
                    $map[$id][] = $lang;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('TeamMember::availableLangsForIds: не удалось прочитать team_member_translations', $e);
        }

        foreach ($ids as $id) {
            if (empty($map[$id])) {
                $map[$id] = [$default];
            }
        }

        return $map;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM team_members WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO team_members (name, position, department, unit, photo, email, phone, socials_json, status, sort_order, created_at)
             VALUES (:name, :position, :department, :unit, :photo, :email, :phone, :socials_json, :status, :sort_order, NOW())'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':position' => $data['position'],
            ':department' => $data['department'] ?? null,
            ':unit' => $data['unit'] ?? null,
            ':photo' => $data['photo'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':socials_json' => json_encode($data['socials'] ?? [], JSON_UNESCAPED_UNICODE),
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);

        // id читаем до сброса кэша: bustPageCache() делает запрос к settings,
        // который обнуляет lastInsertId() (см. Project::create).
        $id = (int) Database::pdo()->lastInsertId();
        self::bustPageCache();

        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE team_members SET name = :name, position = :position, department = :department, unit = :unit,
             photo = :photo, email = :email, phone = :phone, socials_json = :socials_json, status = :status,
             sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':position' => $data['position'],
            ':department' => $data['department'] ?? null,
            ':unit' => $data['unit'] ?? null,
            ':photo' => $data['photo'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':socials_json' => json_encode($data['socials'] ?? [], JSON_UNESCAPED_UNICODE),
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ]);
        self::bustPageCache();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM team_members WHERE id = :id');
        $stmt->execute([':id' => $id]);
        self::bustPageCache();
    }

    private static function bustPageCache(): void
    {
        \App\Core\Cache::forgetPrefix('page:');
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;

/**
 * Единая точка доступа к языковым версиям записи.
 *
 * В CMS уживаются два механизма перевода, и это источник целого класса ошибок:
 * код, написанный под один из них, второй просто не замечает — тихо, без
 * исключений. Так двуязычная новость уходила в Telegram двумя постами, а
 * hreflang вёл на адрес, отвечающий редиректом.
 *
 *  A. Поля в таблице `{сущность}_translations`: базовая запись одна, перевод
 *     накладывается сверху. Есть у всех текстовых сущностей.
 *  B. Отдельная связанная запись (`lang` + `translation_group_id`): у каждого
 *     языка своя строка со своим slug. Есть только у новостей, страниц и
 *     проектов — там, где у языковой версии бывает собственный адрес.
 *
 * Этот класс отвечает на вопрос «какая запись отвечает за язык X» с учётом
 * обоих механизмов сразу. Новый код должен спрашивать здесь, а не собирать
 * свою логику по месту.
 */
final class Translations
{
    /**
     * Таблица переводов и её ключ на владельца. Ключ массива — таблица самой
     * сущности, она же используется в адресах админки.
     *
     * @var array<string, array{0:string|null, 1:string|null}>
     */
    private const TRANSLATION_TABLES = [
        'news' => ['news_translations', 'news_id'],
        'pages' => ['page_translations', 'page_id'],
        // У проектов своей таблицы переводов нет: языковая версия — отдельная
        // запись pages того же типа.
        'projects' => [null, null],
        'photo_albums' => ['photo_album_translations', 'album_id'],
        'videos' => ['video_translations', 'video_id'],
        'team_members' => ['team_member_translations', 'member_id'],
        'content_entries' => ['content_entry_translations', 'entry_id'],
    ];

    /** Сущности, у которых языковая версия бывает отдельной записью. */
    private const LINKED_RECORDS = ['news', 'pages', 'projects'];

    /** Поля перевода, которые не переносятся в запись: они служебные. */
    private const SERVICE_FIELDS = ['id', 'lang', 'created_at', 'updated_at'];

    public static function supportsLinkedRecords(string $table): bool
    {
        return in_array($table, self::LINKED_RECORDS, true);
    }

    /**
     * Записи по языкам: код языка → строка, которая отвечает за этот язык.
     * Связанная запись имеет приоритет над полями перевода: у неё свой адрес,
     * и именно её видит посетитель.
     *
     * @param bool $publishedOnly учитывать только опубликованные версии
     * @return array<string, array<string,mixed>>
     */
    public static function rows(string $table, int $id, bool $publishedOnly = true): array
    {
        $base = self::baseRow($table, $id);
        if ($base === null) {
            return [];
        }

        $rows = [];
        $ownLang = trim((string) ($base['lang'] ?? Language::defaultCode()));
        if ($ownLang === '') {
            $ownLang = Language::defaultCode();
        }

        // Механизм Б: связанные записи группы.
        if (self::supportsLinkedRecords($table)) {
            foreach (self::linkedRows($table, $id) as $code => $row) {
                if (!$publishedOnly || self::isPublished($row)) {
                    $rows[$code] = $row;
                }
            }
        }
        if (!isset($rows[$ownLang]) && (!$publishedOnly || self::isPublished($base))) {
            $rows[$ownLang] = $base;
        }

        // Механизм А: поля перевода поверх базовой записи. Не затираем
        // связанную запись — она полноценнее.
        foreach (self::translationRows($table, $id) as $code => $translation) {
            if (isset($rows[$code]) || $code === $ownLang) {
                continue;
            }
            if ($publishedOnly && !self::isPublished($base)) {
                continue;
            }
            $rows[$code] = self::overlay($base, $translation);
        }

        return $rows;
    }

    /**
     * Языки, на которых запись реально существует.
     *
     * @return list<string>
     */
    public static function langs(string $table, int $id, bool $publishedOnly = true): array
    {
        return array_keys(self::rows($table, $id, $publishedOnly));
    }

    /**
     * Пути опубликованных языковых версий: код языка → путь без языкового
     * префикса. У связанной записи свой slug, и общий путь под чужим
     * префиксом отвечал бы редиректом.
     *
     * @return array<string,string>
     */
    public static function paths(string $table, int $id, string $prefix = ''): array
    {
        $paths = [];
        foreach (self::rows($table, $id) as $code => $row) {
            if (!empty($row['is_home'])) {
                $paths[$code] = '/';
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $paths[$code] = $prefix . $slug;
            }
        }

        return $paths;
    }

    /**
     * Запись, от имени которой действует группа: строка основного языка.
     * Публикация, счётчики и очереди должны адресоваться ей, иначе одна
     * новость учитывается дважды.
     */
    public static function primaryId(string $table, int $id): int
    {
        if (!self::supportsLinkedRecords($table)) {
            return $id;
        }
        $rows = self::rows($table, $id, false);
        $default = Language::defaultCode();

        return isset($rows[$default]['id']) ? (int) $rows[$default]['id'] : $id;
    }

    /**
     * Поля перевода поверх базовой записи. Пустое значение перевода не
     * затирает исходное: у частично заполненного перевода остальное должно
     * читаться на основном языке, а не исчезать.
     *
     * @param array<string,mixed> $base
     * @param array<string,mixed> $translation
     * @return array<string,mixed>
     */
    private static function overlay(array $base, array $translation): array
    {
        $row = $base;
        foreach ($translation as $field => $value) {
            if (in_array($field, self::SERVICE_FIELDS, true) || !array_key_exists($field, $base)) {
                continue;
            }
            if ($value !== null && trim((string) $value) !== '') {
                $row[$field] = $value;
            }
        }

        return $row;
    }

    /**
     * Физическая таблица и условие подтипа: проект — строка pages с
     * entity_type='project'.
     *
     * @return array{0:string,1:string}
     */
    private static function source(string $table): array
    {
        return match ($table) {
            'projects' => ['pages', " AND entity_type = 'project'"],
            'pages' => ['pages', " AND entity_type = 'page'"],
            default => [$table, ''],
        };
    }

    /** @return array<string,mixed>|null */
    private static function baseRow(string $table, int $id): ?array
    {
        if ($id <= 0 || !isset(self::TRANSLATION_TABLES[$table]) || !Database::isConnected()) {
            return null;
        }
        [$source, $typeWhere] = self::source($table);
        $stmt = Database::pdo()->prepare("SELECT * FROM {$source} WHERE id = :id{$typeWhere} LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Записи группы переводов по языкам. Первая запись языка выигрывает:
     * дубли в группе — редкость, но порядок должен быть предсказуемым.
     *
     * @return array<string, array<string,mixed>>
     */
    private static function linkedRows(string $table, int $id): array
    {
        $pdo = Database::pdo();
        [$source, $typeWhere] = self::source($table);
        $stmt = $pdo->prepare(
            "SELECT COALESCE(NULLIF(translation_group_id, 0), id) FROM {$source} WHERE id = :id{$typeWhere} LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $groupId = (int) ($stmt->fetchColumn() ?: $id);

        $stmtGroup = $pdo->prepare(
            "SELECT * FROM {$source}
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND deleted_at IS NULL{$typeWhere}
             ORDER BY id"
        );
        $stmtGroup->execute([':group_id' => $groupId]);

        $rows = [];
        foreach ($stmtGroup->fetchAll() as $row) {
            $lang = trim((string) ($row['lang'] ?? ''));
            if ($lang !== '' && !isset($rows[$lang])) {
                $rows[$lang] = $row;
            }
        }

        return $rows;
    }

    /**
     * Строки таблицы переводов по языкам. Перевод без заголовка не считается
     * переводом: иначе пустая строка выдавала бы язык за существующий.
     *
     * @return array<string, array<string,mixed>>
     */
    private static function translationRows(string $table, int $id): array
    {
        [$translationTable, $ownerKey] = self::TRANSLATION_TABLES[$table];
        if ($translationTable === null || $ownerKey === null) {
            return [];
        }

        try {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM {$translationTable} WHERE {$ownerKey} = :id"
            );
            $stmt->execute([':id' => $id]);
            $found = $stmt->fetchAll();
        } catch (\Throwable $e) {
            Logger::swallowed('Translations: не удалось прочитать ' . $translationTable, $e);

            return [];
        }

        $rows = [];
        foreach ($found as $row) {
            $lang = trim((string) ($row['lang'] ?? ''));
            // У команды переводится имя, у остальных — заголовок.
            $headline = trim((string) ($row['title'] ?? $row['name'] ?? ''));
            if ($lang !== '' && $headline !== '') {
                $rows[$lang] = $row;
            }
        }

        return $rows;
    }

    /** @param array<string,mixed> $row */
    private static function isPublished(array $row): bool
    {
        if (!empty($row['deleted_at'])) {
            return false;
        }
        // Сущности без статуса (альбомы, видео) считаются опубликованными:
        // видимость у них определяется другими признаками.
        if (!array_key_exists('status', $row)) {
            return true;
        }

        return (string) $row['status'] === 'published';
    }
}

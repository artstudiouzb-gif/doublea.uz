<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Database;

/**
 * Перевод текста слайда (механизм А: база в `hero_slides.data`, языковые
 * варианты здесь).
 *
 * Отдельной обложки на язык нет намеренно: медиа, цвета и раскладка у слайда
 * одни и те же, и разводить их по языкам значило бы чинить композицию дважды.
 * Переводится только то, что читают, — надзаголовок, заголовок, описание,
 * подписи кнопок и описание картинки.
 */
final class HeroSlideTranslation
{
    /** @var list<string> */
    public const FIELDS = ['eyebrow', 'title', 'subtitle', 'cta_text', 'cta2_text', 'art_alt', 'watermark'];

    /**
     * Все переводы одного слайда, по коду языка (для формы).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function forSlide(int $slideId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM hero_slide_translations WHERE slide_id = :id');
        $stmt->execute([':id' => $slideId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['lang']] = $row;
        }

        return $result;
    }

    /**
     * Один язык для набора слайдов — обложка рендерится на каждой странице,
     * поэтому N+1 здесь недопустим.
     *
     * @param list<int> $slideIds
     * @return array<int, array<string, mixed>>
     */
    public static function forSlides(array $slideIds, string $lang): array
    {
        $slideIds = array_values(array_unique(array_filter(
            array_map('intval', $slideIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($slideIds === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($slideIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM hero_slide_translations WHERE slide_id IN ({$in}) AND lang = ?"
        );
        $stmt->execute([...$slideIds, $lang]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['slide_id']] = $row;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function upsert(int $slideId, string $lang, array $values): void
    {
        $clean = [];
        foreach (self::FIELDS as $field) {
            $value = trim((string) ($values[$field] ?? ''));
            $clean[$field] = $value === ''
                ? null
                : mb_substr($value, 0, $field === 'subtitle' ? 2000 : 255);
        }

        // Полностью пустой перевод не храним: иначе колонка «Языки» показывала
        // бы язык, на котором на самом деле ничего не переведено.
        if (array_filter($clean, static fn (?string $v): bool => $v !== null) === []) {
            self::delete($slideId, $lang);

            return;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO hero_slide_translations (slide_id, lang, eyebrow, title, subtitle, cta_text, cta2_text, art_alt, watermark)
             VALUES (:id, :lang, :eyebrow, :title, :subtitle, :cta_text, :cta2_text, :art_alt, :watermark)
             ON DUPLICATE KEY UPDATE eyebrow = VALUES(eyebrow), title = VALUES(title),
                 subtitle = VALUES(subtitle), cta_text = VALUES(cta_text),
                 cta2_text = VALUES(cta2_text), art_alt = VALUES(art_alt),
                 watermark = VALUES(watermark)'
        );
        $stmt->execute([
            ':id' => $slideId,
            ':lang' => $lang,
            ':eyebrow' => $clean['eyebrow'],
            ':title' => $clean['title'],
            ':subtitle' => $clean['subtitle'],
            ':cta_text' => $clean['cta_text'],
            ':cta2_text' => $clean['cta2_text'],
            ':art_alt' => $clean['art_alt'],
            ':watermark' => $clean['watermark'],
        ]);
        Cache::forgetPrefix('page:');
    }

    public static function delete(int $slideId, string $lang): void
    {
        Database::pdo()
            ->prepare('DELETE FROM hero_slide_translations WHERE slide_id = :id AND lang = :lang')
            ->execute([':id' => $slideId, ':lang' => $lang]);
        Cache::forgetPrefix('page:');
    }

    /** Переводы вместе с копией слайда: дубль без них пришлось бы переводить заново. */
    public static function copy(int $fromSlideId, int $toSlideId): void
    {
        Database::pdo()->prepare(
            'INSERT INTO hero_slide_translations (slide_id, lang, eyebrow, title, subtitle, cta_text, cta2_text, art_alt, watermark)
             SELECT :to, lang, eyebrow, title, subtitle, cta_text, cta2_text, art_alt, watermark
             FROM hero_slide_translations WHERE slide_id = :from
             ON DUPLICATE KEY UPDATE title = VALUES(title)'
        )->execute([':to' => $toSlideId, ':from' => $fromSlideId]);
    }

    /**
     * Языки, на которых у слайдов есть хоть какой-то текст (колонка «Языки»).
     *
     * @param array<int|string> $ids
     * @return array<int, list<string>>
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

        $base = Database::pdo()->prepare("SELECT id, title FROM hero_slides WHERE id IN ({$in})");
        $base->execute($ids);
        foreach ($base->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (isset($map[$id]) && trim((string) ($row['title'] ?? '')) !== '') {
                $map[$id][] = $default;
            }
        }

        $trans = Database::pdo()->prepare(
            "SELECT slide_id, lang FROM hero_slide_translations
             WHERE slide_id IN ({$in})
               AND (TRIM(COALESCE(title, '')) <> '' OR TRIM(COALESCE(subtitle, '')) <> '')"
        );
        $trans->execute($ids);
        foreach ($trans->fetchAll() as $row) {
            $id = (int) $row['slide_id'];
            $lang = (string) $row['lang'];
            if (isset($map[$id]) && !in_array($lang, $map[$id], true)) {
                $map[$id][] = $lang;
            }
        }

        return $map;
    }
}

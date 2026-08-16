<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Telegram-уведомления о новых заявках форм через собственного бота
 * (TelegramBot, тот же токен, что и для кодов входа). Получатели — список
 * chat_id в настройке telegram_notify_chat_ids (через запятую; отрицательные
 * id — групповые чаты).
 * Поля заявок размечаются стильным HTML с интерактивными тегами <code>...</code>
 * для мгновенного копирования номеров, почты, кодов по клику в Telegram.
 */
final class FormNotifier
{
    /** Telegram ограничивает сообщение 4096 символами — режем с запасом. */
    private const MAX_TEXT = 3800;

    /** Длинные значения полей укорачиваются до этого размера. */
    private const MAX_FIELD = 500;

    public static function isEnabled(): bool
    {
        return TelegramBot::isConfigured() && self::chatIds() !== [];
    }

    /** @return array<int, int> */
    public static function chatIds(): array
    {
        return self::parseChatIds(Setting::get('telegram_notify_chat_ids', ''));
    }

    /**
     * Разбирает список chat_id из настройки: разделители — запятая, точка с
     * запятой, пробелы, переводы строк; мусор пропускается (чистая функция).
     *
     * @return array<int, int>
     */
    public static function parseChatIds(string $raw): array
    {
        $ids = [];
        foreach (preg_split('/[\s,;]+/', trim($raw)) ?: [] as $part) {
            if ($part !== '' && preg_match('/^-?\d{1,19}$/', $part) === 1) {
                $id = filter_var($part, FILTER_VALIDATE_INT);
                if ($id !== false && $id !== 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Текст уведомления о заявке (чистая функция): название формы + поля.
     * Поля вроде телефона, email, кодов, IP оборачиваются в <code>...</code>
     * для автоматического копирования по клику в клиенте Telegram.
     *
     * @param array<string, string> $fields
     */
    public static function formatSubmission(string $formName, array $fields): string
    {
        $lines = ["<b>Новая заявка: " . htmlspecialchars($formName, ENT_QUOTES) . "</b>\n"];
        foreach ($fields as $key => $value) {
            $value = trim((string) $value);
            if (mb_strlen($value) > self::MAX_FIELD) {
                $value = mb_substr($value, 0, self::MAX_FIELD) . '…';
            }
            $safeKey = htmlspecialchars((string) $key, ENT_QUOTES);
            $safeVal = htmlspecialchars($value, ENT_QUOTES);

            $keyLower = mb_strtolower((string) $key);
            // Если поле содержит контакты, коды, логины или IP-адреса — оборачиваем в <code> для копирования по клику
            if (
                str_contains($keyLower, 'телефон') || str_contains($keyLower, 'phone') || str_contains($keyLower, 'tel') ||
                str_contains($keyLower, 'email') || str_contains($keyLower, 'почта') || str_contains($keyLower, 'код') ||
                str_contains($keyLower, 'code') || str_contains($keyLower, 'id') || str_contains($keyLower, 'номер') ||
                str_contains($keyLower, 'ip') || preg_match('/^\+?\d[\d\s\-()]{7,}$/', $value)
            ) {
                $lines[] = "• <b>{$safeKey}:</b> <code>{$safeVal}</code>";
            } elseif (mb_strlen($value) > 60 || str_contains($value, "\n")) {
                $lines[] = "• <b>{$safeKey}:</b>\n<blockquote>{$safeVal}</blockquote>";
            } else {
                $lines[] = "• <b>{$safeKey}:</b> {$safeVal}";
            }
        }

        $text = implode("\n", $lines);
        if (mb_strlen($text) > self::MAX_TEXT) {
            $text = mb_substr($text, 0, self::MAX_TEXT) . '…';
        }

        return $text;
    }

    /**
     * Шлёт уведомление о новой заявке всем получателям.
     * Возвращает число успешных доставок (0 — если канал не настроен).
     *
     * @param array<string, string> $fields
     */
    public static function notifySubmission(string $formName, array $fields): int
    {
        return self::broadcast(self::formatSubmission($formName, $fields));
    }

    /**
     * Произвольное служебное уведомление тем же получателям (сбой автобэкапа
     * и т.п.). Возвращает число успешных доставок.
     */
    public static function broadcast(string $text): int
    {
        if (!self::isEnabled()) {
            return 0;
        }

        $sent = 0;
        foreach (self::chatIds() as $chatId) {
            if (TelegramBot::sendMessage($chatId, $text)) {
                $sent++;
            }
        }

        return $sent;
    }
}

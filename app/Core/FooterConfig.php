<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Конфигурация конструктора подвала: набор колонок с микро-виджетами и нижняя
 * строка (копирайт). Хранится JSON-строкой в settings['footer_config'].
 * Любые прочитанные данные сливаются с дефолтами — старые/неполные JSON
 * не приводят к ошибкам.
 */
final class FooterConfig
{
    /** Доступные виджеты колонки подвала: value => подпись для админки. */
    public const WIDGETS = [
        'about' => 'О сайте (логотип + контакты)',
        'menu' => 'Меню сайта',
        'contacts' => 'Контакты (адрес, телефон, email)',
        'social' => 'Соцсети',
        'subscribe' => 'Подписка на новости (форма)',
        'text' => 'Текст / HTML (сниппет)',
    ];

    public const STYLES = ['columns', 'minimal'];

    /** Максимум колонок в подвале. */
    public const MAX_COLUMNS = 4;

    public const DEFAULTS = [
        'style' => 'columns',                 // columns | minimal
        'columns' => [
            ['heading' => '', 'widget' => 'about', 'text' => ''],
            ['heading' => 'Разделы', 'widget' => 'menu', 'text' => ''],
            ['heading' => 'Связь', 'widget' => 'contacts', 'text' => ''],
        ],
        // Плейсхолдеры: {year} — текущий год, {site} — название сайта.
        'bottom' => '© {year} {site}',
        // Фон подвала: те же режимы, что и у секции страницы (цвет, градиент,
        // фотография, узор). Пустой режим — как было, из темы.
        'background' => ['_bg_mode' => 'preset'],
    ];

    public static function get(): array
    {
        $raw = Setting::get('footer_config', '');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return self::mergeDefaults($decoded);
    }

    public static function save(array $config): void
    {
        Setting::set('footer_config', json_encode(self::mergeDefaults($config), JSON_UNESCAPED_UNICODE));
    }

    public static function normalize(array $config): array
    {
        return self::mergeDefaults($config);
    }

    /**
     * Разворачивает плейсхолдеры нижней строки ({year}, {site}) и очищает от
     * разметки (простой текст). Чистая функция для рендера и тестов.
     */
    public static function renderBottom(string $template, string $siteName): string
    {
        $out = strtr($template, [
            '{year}' => (string) date('Y'),
            '{site}' => $siteName,
        ]);

        return trim(strip_tags($out));
    }

    /**
     * Хранимые ключи фона (`_bg_*`) обратно в вид формы: нормализатор работает
     * с тем, что прислал редактор, и второй его копии заводить не за чем.
     *
     * @param array<string, mixed> $background
     * @return array<string, mixed>
     */
    private static function backgroundInput(array $background): array
    {
        $input = [];
        foreach ($background as $key => $value) {
            $input[ltrim((string) $key, '_')] = $value;
        }

        return $input;
    }

    private static function mergeDefaults(array $config): array
    {
        $result = self::DEFAULTS;

        $result['style'] = in_array($config['style'] ?? '', self::STYLES, true)
            ? $config['style'] : self::DEFAULTS['style'];
        // Фон приходит уже нормализованным (ключи с `_`), но конфиг правят и
        // руками — прогоняем через тот же нормализатор, что и у блоков.
        $background = is_array($config['background'] ?? null) ? $config['background'] : [];
        $result['background'] = \App\Core\BlockData\BlockPresentationNormalizer::background(
            self::backgroundInput($background)
        );

        if (isset($config['columns']) && is_array($config['columns'])) {
            $columns = [];
            foreach ($config['columns'] as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $widget = (string) ($col['widget'] ?? '');
                if (!isset(self::WIDGETS[$widget])) {
                    continue;
                }
                $text = (string) ($col['text'] ?? '');
                // Текстовый виджет — безопасный HTML (тот же санитайзер, что для
                // контента редактора); прочие виджеты текст не используют.
                $text = ($widget === 'text' && trim($text) !== '')
                    ? HtmlSanitizer::sanitize($text)
                    : '';
                $columns[] = [
                    'heading' => mb_substr(trim((string) ($col['heading'] ?? '')), 0, 100),
                    'widget' => $widget,
                    'text' => $text,
                ];
                if (count($columns) >= self::MAX_COLUMNS) {
                    break;
                }
            }
            $result['columns'] = $columns;
        } else {
            $result['columns'] = self::DEFAULTS['columns'];
        }

        $result['bottom'] = mb_substr(trim((string) ($config['bottom'] ?? self::DEFAULTS['bottom'])), 0, 300);
        if ($result['bottom'] === '') {
            $result['bottom'] = self::DEFAULTS['bottom'];
        }

        return $result;
    }
}

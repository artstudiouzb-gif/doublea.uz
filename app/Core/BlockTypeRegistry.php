<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Единый реестр типов блоков.
 *
 * Здесь собраны ключи типов, их дефолтные данные и названия для редактора.
 * Шаблоны обычных блоков следуют соглашению templates/blocks/{type}.php;
 * columns рендерится программно, потому отдельного шаблона у него нет.
 */
final class BlockTypeRegistry
{
    /** @var array<string, array<string, mixed>> */
    public const DEFAULTS = [
        'text' => [
            'variant' => 'default',
            'title' => '',
            'content' => '',
            'aside_title' => '',
            'items' => [],
            'quote' => '',
            'media_type' => 'none',
            'media_image' => '',
            'media_video' => '',
            'media_youtube' => '',
            'media_alt' => '',
            'media_caption' => '',
            'image_position' => 'center-center',
            'image_position_mobile' => 'center-center',
        ],
        'html' => ['html' => ''],
        'cta' => ['variant' => 'card', 'title' => '', 'text' => '', 'icon_svg' => '', 'image' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'button_text' => '', 'button_url' => '', 'bg_color' => '', 'text_color' => '', 'button_color' => ''],
        'advantages' => ['variant' => 'grid', 'title' => '', 'description' => '', 'all_text' => '', 'all_url' => '', 'columns' => 0, 'items' => []],
        'slider' => ['title' => '', 'autoplay' => 0, 'ratio' => '16-9', 'slides' => []],
        'form' => ['form_id' => null],
        'columns' => ['columns' => 2, 'gap' => 'medium', 'ratio' => ''],
        // Вкладки — такой же контейнер, как columns: содержимое вкладки это
        // вложенные блоки любого типа (column_index = номер вкладки), а сам
        // блок хранит только подписи вкладок и оформление.
        'tabs' => ['variant' => 'segmented', 'title' => '', 'description' => '', 'align' => 'left', 'items' => []],
        'testimonials' => ['variant' => 'carousel', 'title' => '', 'description' => '', 'columns' => 3, 'autoplay' => 0, 'items' => []],
        'counters' => ['title' => '', 'card_bg' => '', 'text_color' => '', 'icon_size' => 28, 'icon_bg' => 'on', 'icon_position' => 'left', 'text_align' => 'left', 'variant' => 'row', 'value_size' => 'normal', 'items' => []],
        'team_list' => ['title' => '', 'limit' => 0, 'department' => '', 'group_by_department' => false],
        'projects_list' => ['variant' => 'grid', 'title' => '', 'description' => '', 'all_text' => '', 'all_url' => '', 'columns' => 3, 'limit' => 3, 'autoplay' => 0],
        'news_latest' => ['title' => 'Последние новости', 'all_text' => 'Все новости', 'all_url' => '', 'limit' => 3, 'category' => 0],
        'partners' => ['title' => 'Партнёры', 'description' => '', 'all_text' => '', 'all_url' => '', 'columns' => 6, 'logo_size' => 'medium', 'grayscale' => true, 'autoplay' => 0, 'items' => []],
        'subscribe' => ['variant' => 'band', 'title' => 'Подписка на новости', 'text' => 'Получайте дайджест новостей на почту раз в неделю.', 'image' => '', 'placeholder' => '', 'note' => '', 'button_text' => 'Подписаться'],
        'faq' => ['title' => '', 'search_enabled' => true, 'single_open' => false, 'items' => []],
        'contact_cards' => ['variant' => 'cards', 'title' => '', 'line_icons' => true, 'icon_size' => 22, 'icon_bg' => 'on', 'items' => []],
        // hero_id — ссылка на обложку (тип контента «Обложки»). Когда он задан,
        // блок только размещает обложку: содержимое и настройки берутся из неё,
        // а собственные поля блока не используются. Ноль — старая обложка,
        // собранная прямо в блоке; такие страницы продолжают работать.
        'hero' => ['hero_id' => 0, 'title' => '', 'eyebrow' => '', 'subtitle' => '', 'bg_type' => 'none', 'image' => '', 'image_mobile' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'video_url' => '', 'video_mobile' => 'poster', 'youtube_url' => '', 'bg_color' => '', 'width' => 'full', 'height' => 'regular', 'custom_height' => '720px', 'height_mobile' => '', 'custom_height_mobile' => '', 'overlay_enabled' => false, 'overlay_mode' => 'gradient', 'overlay_direction' => 'auto', 'overlay_color' => '#0b1a30', 'overlay_opacity' => 35, 'text_position' => 'left', 'text_align_y' => 'center', 'text_width' => '', 'text_color' => '', 'art_image' => '', 'art_alt' => '', 'art_position' => 'above', 'art_size' => 'medium', 'button_color' => '', 'panel_enabled' => false, 'panel_color' => '#0b1a30', 'panel_opacity' => 0, 'button_text' => '', 'button_url' => '', 'button_icon' => '', 'button_icon_image' => '', 'button2_text' => '', 'button2_url' => '', 'button2_icon' => '', 'button2_icon_image' => '', 'video_button_text' => '', 'video_button_url' => '', 'slides' => [], 'autoplay' => 0],
        'cards_grid' => ['variant' => 'icon', 'title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 5, 'card_bg' => '', 'text_color' => '', 'source' => 'manual', 'limit' => 6, 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'items' => []],
        'media_gallery' => ['title' => '', 'description' => '', 'all_text' => '', 'all_url' => '', 'source' => 'manual', 'limit' => 8, 'columns' => 4, 'ratio' => '16-9', 'items' => []],
        'news_feature' => ['variant' => 'cards', 'title' => 'Новости и аналитика', 'all_text' => 'Все новости', 'all_url' => '', 'limit' => 5, 'category' => 0],
        'person_cards' => ['title' => '', 'description' => '', 'all_text' => '', 'all_url' => '', 'columns' => 4, 'items' => []],
        'timeline' => ['title' => '', 'description' => '', 'items' => [], 'button_text' => '', 'button_url' => '', 'cta_title' => '', 'cta_text' => '', 'cta_button_text' => '', 'cta_button_url' => '', 'cta_image' => ''],
        'news_docs' => ['news_title' => 'Актуальные новости', 'news_all_text' => 'Все новости', 'news_all_url' => '', 'limit' => 3, 'category' => 0, 'docs_title' => 'Документы', 'docs_all_text' => 'Все документы', 'docs_all_url' => '', 'docs' => []],
        'person_profile' => ['photo' => '', 'photo_side' => 'left', 'name' => '', 'position' => '', 'text' => '', 'phone' => '', 'phone_label' => 'Приёмная:', 'email' => '', 'email_label' => 'E-mail:', 'button_text' => '', 'button_url' => '', 'button2_text' => '', 'button2_url' => '', 'telegram' => '', 'facebook' => '', 'linkedin' => '', 'x' => '', 'instagram' => ''],
        'bio_education' => ['bio_title' => 'Биография', 'bio_text' => '', 'career_title' => '', 'career' => [], 'edu_title' => 'Образование', 'edu_items' => [], 'extra_title' => '', 'extra_text' => '', 'widgets_before' => [], 'widgets_after' => [], 'quote_text' => '', 'quote_author' => ''],
        'anchor_nav' => ['items' => [], 'auto' => false, 'sticky' => false],
        'stages' => ['variant' => 'default', 'title' => '', 'description' => '', 'all_text' => '', 'all_url' => '', 'columns' => 0, 'autoplay' => 0, 'items' => []],
        'text_image' => ['title' => '', 'text' => '', 'image' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'image_side' => 'right', 'image_ratio' => 'auto', 'image_width' => 50, 'button_text' => '', 'button_url' => '', 'items' => []],
        'docs_list' => ['variant' => 'grid', 'title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 4, 'search_enabled' => true, 'items' => []],
        'map_point' => ['title' => '', 'image' => '', 'embed_url' => '', 'load_mode' => 'click', 'card_title' => '', 'address' => '', 'copy_enabled' => true, 'button_text' => '', 'button_url' => ''],
        'org_structure' => ['title' => '', 'layout' => 'tree', 'columns' => 4, 'council' => '', 'head_title' => 'Директор', 'head_name' => '', 'head_url' => '', 'side_items' => '', 'branches' => [], 'collapsible' => false, 'search' => false, 'notes' => '', 'footnote' => ''],
        'leader_card' => ['photo' => '', 'name' => '', 'name_tag' => 'p', 'position' => '', 'phone' => '', 'email' => '', 'hours' => '',
            'facebook' => '', 'x' => '', 'linkedin' => '', 'instagram' => '', 'telegram' => '',
            'facts_title' => 'Основная информация', 'facts_icon' => '', 'items' => [],
            'bio_title' => 'Биография', 'bio_icon' => '', 'bio' => '',
            'duties_title' => 'Функции', 'duties_icon' => '', 'duties' => '',
            'mobile_icons_only' => false],
        // icon_position по умолчанию пуст намеренно: у блоков, сохранённых до
        // появления поля, позицию иконки задавало выравнивание, и подстановка
        // «слева» дефолтом сдвинула бы иконку на всех таких страницах.
        'icon_text' => ['variant' => 'cards', 'title' => '', 'description' => '', 'icon_position' => '', 'align' => 'left', 'rows_layout' => 'stacked', 'columns' => 3, 'items' => []],
    ];

    /** Короткие русские названия для сообщений редактору. */
    public const TYPE_LABELS = [
        'text' => 'Текст', 'html' => 'Произвольный HTML', 'cta' => 'Призыв к действию',
        'advantages' => 'Преимущества', 'slider' => 'Слайдер',
        'form' => 'Форма', 'columns' => 'Колонки', 'tabs' => 'Вкладки', 'testimonials' => 'Отзывы',
        'counters' => 'Счётчики', 'team_list' => 'Команда', 'projects_list' => 'Проекты',
        'news_latest' => 'Последние новости', 'partners' => 'Партнёры',
        'subscribe' => 'Подписка', 'faq' => 'Вопросы и ответы', 'contact_cards' => 'Контакты',
        'hero' => 'Обложка',
        'cards_grid' => 'Карточки', 'media_gallery' => 'Медиагалерея',
        'news_feature' => 'Новости и аналитика', 'person_cards' => 'Карточки персон', 'timeline' => 'Хронология',
        'news_docs' => 'Новости и документы', 'person_profile' => 'Профиль персоны',
        'bio_education' => 'Биография и образование',
        'anchor_nav' => 'Якорная навигация', 'stages' => 'Этапы', 'text_image' => 'Текст с фото',
        'docs_list' => 'Список документов', 'map_point' => 'Карта', 'org_structure' => 'Оргструктура',
        'leader_card' => 'Карточка руководителя',
        'icon_text' => 'Иконка и текст',
    ];

    /**
     * Более подробные подписи только там, где список добавления блока требует
     * пояснения. Остальные берутся из TYPE_LABELS.
     */
    private const EDITOR_LABEL_OVERRIDES = [
        'cta' => 'Призыв к действию (CTA)',
        'tabs' => 'Вкладки (любые блоки внутри)',
        'team_list' => 'Список команды',
        'projects_list' => 'Список проектов',
        'partners' => 'Партнёры (логотипы)',
        'subscribe' => 'Подписка на дайджест',
        'faq' => 'FAQ / аккордеон',
        'contact_cards' => 'Контактные карточки',
        'hero' => 'Герой (титул + фото/видео)',
        'cards_grid' => 'Карточки (иконки / фото / категории)',
        'media_gallery' => 'Медиа-галерея (видео/фото)',
        'news_feature' => 'Новости и аналитика (крупная + список)',
        'person_cards' => 'Руководство (карточки персон)',
        'timeline' => 'История (таймлайн + CTA-карточка)',
        'news_docs' => 'Новости + документы (2 колонки)',
        'person_profile' => 'Профиль руководителя',
        'bio_education' => 'Биография + образование',
        'anchor_nav' => 'Якорная навигация (вкладки)',
        'stages' => 'Этапы реализации (таймлайн)',
        'text_image' => 'Текст + фото (о проекте)',
        'docs_list' => 'Документы (сетка)',
        'map_point' => 'Карта с меткой',
        'org_structure' => 'Структура организации (оргсхема)',
        'leader_card' => 'Карточка руководителя (с вкладками)',
        'icon_text' => 'Иконка и текст (контакты, телефоны)',
    ];

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::DEFAULTS);
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::DEFAULTS);
    }

    /** @return array<string, mixed> */
    public static function defaultsFor(string $type): array
    {
        return self::DEFAULTS[$type] ?? [];
    }

    /** @return array<string, string> */
    public static function editorLabels(): array
    {
        return array_replace(self::TYPE_LABELS, self::EDITOR_LABEL_OVERRIDES);
    }

    /**
     * Контейнеры: содержимое — вложенные блоки, а не поля формы. Шаблона у них
     * нет (рендер программный, с рекурсией), и вкладывать контейнер в контейнер
     * нельзя — иначе редактор получает дерево, которое некому показать.
     *
     * @var list<string>
     */
    public const CONTAINER_TYPES = ['columns', 'tabs'];

    public static function isContainer(string $type): bool
    {
        return in_array($type, self::CONTAINER_TYPES, true);
    }

    public static function templateFile(string $type): ?string
    {
        if (!self::has($type) || self::isContainer($type)) {
            return null;
        }

        return dirname(__DIR__, 2) . '/templates/blocks/' . $type . '.php';
    }
}

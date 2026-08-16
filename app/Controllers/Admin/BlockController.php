<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\BlockData\AdvantagesBlockNormalizer;
use App\Core\BlockData\BlockPresentationNormalizer;
use App\Core\BlockData\ContactCardsBlockNormalizer;
use App\Core\BlockData\CountersBlockNormalizer;
use App\Core\BlockData\CtaBlockNormalizer;
use App\Core\BlockData\FaqBlockNormalizer;
use App\Core\BlockData\HeroBlockNormalizer;
use App\Core\BlockData\SubscribeBlockNormalizer;
use App\Core\BlockData\TestimonialsBlockNormalizer;
use App\Core\BlockTypeRegistry;
use App\Core\BlockVersioning;
use App\Core\ConcurrencyException;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Core\TextProcessor;
use App\Models\Block;
use App\Models\BlockRevision;
use App\Models\FormDef;
use App\Models\Language;
use App\Models\Page;
use App\Models\Widget;

final class BlockController
{
    public function store(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $pageId = (int) $params['id'];
        $page = Page::findById($pageId);
        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $type = (string) ($_POST['type'] ?? '');
        $lang = (string) ($_POST['block_lang'] ?? Language::defaultCode());
        if (!Language::isActive($lang)) {
            $lang = Language::defaultCode();
        }

        if (!BlockTypeRegistry::has($type)) {
            Flash::error('Неизвестный тип блока.');
            header('Location: ' . self::ownerEditUrl($pageId, $lang));
            exit;
        }

        // Блок сырого HTML может создавать только супер-администратор.
        if ($type === 'html' && !Auth::isSuperAdmin()) {
            Flash::error('Блок «HTML-код» доступен только супер-администратору.');
            header('Location: ' . self::ownerEditUrl($pageId, $lang));
            exit;
        }

        // Вложенность в контейнер (колонки, вкладки): блок добавляется внутрь,
        // если пришли parent_block_id + column_index (номер колонки/вкладки).
        $parentBlockId = null;
        $columnIndex = 0;
        $redirectTo = self::ownerEditUrl($pageId, $lang);
        if (!empty($_POST['parent_block_id'])) {
            $parent = Block::findById((int) $_POST['parent_block_id']);
            if (!$parent || (int) $parent['page_id'] !== $pageId
                || !BlockTypeRegistry::isContainer((string) $parent['type'])
            ) {
                Flash::error('Некорректный родительский блок.');
                header('Location: ' . $redirectTo);
                exit;
            }
            // Запрет контейнера в контейнере.
            if (BlockTypeRegistry::isContainer($type)) {
                Flash::error('Блок «' . (BlockTypeRegistry::TYPE_LABELS[$type] ?? $type)
                    . '» нельзя вкладывать в колонки или вкладки.');
                header('Location: ' . $redirectTo);
                exit;
            }
            $parentBlockId = (int) $parent['id'];
            $columnIndex = max(0, (int) ($_POST['column_index'] ?? 0));
        }

        // Новый блок приходит с образцом наполнения: пустой блок не объясняет,
        // из чего он состоит, и редактор видит на странице пустое место.
        $title = trim((string) ($_POST['title'] ?? ''));
        $sample = \App\Core\BlockSamples::for($type, $lang);
        // Блок формы без выбранной формы показывает заглушку. Если на сайте
        // формы уже есть, подставляем первую — блок сразу рабочий.
        if ($type === 'form') {
            $firstForm = FormDef::all()[0] ?? null;
            if ($firstForm !== null) {
                $sample['form_id'] = (int) $firstForm['id'];
            }
        }
        $blockId = Block::create(
            $pageId,
            $lang,
            $type,
            $title !== '' ? $title : null,
            array_merge(BlockTypeRegistry::defaultsFor($type), $sample),
            '',
            $parentBlockId,
            $columnIndex
        );
        \App\Core\Cache::clearPageCache($pageId);

        Flash::success($sample !== []
            ? 'Блок добавлен с примером наполнения — замените тексты своими.'
            : 'Блок добавлен. Заполните его содержимое.');
        header('Location: /admin/blocks/' . $blockId . '/edit');
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $data = json_decode((string) $block['data'], true) ?: [];

        View::render('admin/pages/block_form', [
            'block' => $block,
            'data' => $data,
            'forms' => $block['type'] === 'form' ? FormDef::all() : [],
            'departments' => self::departmentsFor((string) $block['type']),
            'widgets' => $block['type'] === 'bio_education' ? Widget::all() : [],
        ]);
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        // Кастомный CSS может менять только супер-администратор; для редактора
        // сохраняем прежнее значение независимо от присланных данных.
        $customCss = Auth::isSuperAdmin()
            ? (string) ($_POST['custom_css'] ?? '')
            : (string) ($block['custom_css'] ?? '');
        $locale = ((string) $block['lang'] === 'en') ? 'en' : 'ru';
        $data = $this->collectData($block['type'], $locale);
        // Создавать блок «HTML-код» может только супер-администратор — значит и
        // переписывать разметку уже созданного тоже: иначе запрет обходится
        // редактированием блока, добавленного кем-то другим.
        if ((string) $block['type'] === 'html' && !Auth::isSuperAdmin()) {
            $stored = json_decode((string) $block['data'], true);
            $data['html'] = (string) (is_array($stored) ? ($stored['html'] ?? '') : '');
        }
        $data = array_merge($data, BlockPresentationNormalizer::normalize($_POST));

        // Перевёрнутое окно молча не чиним: блок и правда не покажется никогда —
        // честнее предупредить, чем угадывать намерение редактора.
        if (BlockPresentationNormalizer::hasInvalidVisibilityWindow($data)) {
            Flash::error('Условия показа: дата окончания не позже даты начала — блок не будет показан. Проверьте даты.');
        }

        $expectedVersion = (int) ($_POST['expected_lock_version'] ?? 0);
        try {
            BlockVersioning::updateWithSnapshot(
                $block,
                $title !== '' ? $title : null,
                $data,
                $customCss,
                Auth::id(),
                $expectedVersion
            );
        } catch (ConcurrencyException) {
            $block = Block::findById((int) $block['id']) ?? $block;
            View::render('admin/pages/block_form', [
                'block' => $block,
                'data' => json_decode((string) $block['data'], true) ?: [],
                'forms' => $block['type'] === 'form' ? FormDef::all() : [],
                'departments' => self::departmentsFor((string) $block['type']),
                'widgets' => $block['type'] === 'bio_education' ? Widget::all() : [],
                'error' => 'Блок уже был изменён в другой вкладке или другим пользователем. Текущие данные перезагружены; восстановите локальный черновик и проверьте изменения.',
            ]);
            return;
        }
        \App\Core\Cache::clearPageCache((int) $block['page_id']);

        Flash::success('Блок сохранён.');

        // Заполненное, но нерабочее — говорим сразу, а не оставляем редактора
        // выяснять это, открыв сайт и не найдя своего текста.
        foreach (\App\Core\BlockHints::forBlock((string) $block['type'], $data) as $hint) {
            Flash::error($hint);
        }
        if (\App\Core\BlockHints::rendersEmpty([
            'id' => (int) $block['id'],
            'type' => (string) $block['type'],
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'custom_css' => '',
        ])) {
            Flash::error('Блок пока пуст и на сайте не показывается — заполните его поля.');
        }
        header('Location: ' . $this->pageEditUrl($block) . '&draft_saved=block%3A' . (int) $block['id']);
        exit;
    }

    /** История версий блока (группа 5.1). */
    public function revisions(array $params): void
    {
        Auth::requireLogin();
        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('admin/blocks/revisions', [
            'block' => $block,
            'revisions' => BlockRevision::forBlock((int) $block['id']),
            'backUrl' => $this->pageEditUrl($block),
        ]);
    }

    /** Восстановление блока из ревизии (создаёт новую ревизию, группа 5.1). */
    public function restoreRevision(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $rev = BlockRevision::findById((int) ($_POST['revision_id'] ?? 0));
        if (!$rev || (int) $rev['block_id'] !== (int) $block['id']) {
            Flash::error('Ревизия не найдена.');
            header('Location: /admin/blocks/' . (int) $block['id'] . '/revisions');
            exit;
        }

        // custom_css трогает только супер-админ; редактору оставляем текущий.
        $customCss = Auth::isSuperAdmin()
            ? ($rev['custom_css'] !== null ? (string) $rev['custom_css'] : '')
            : (string) ($block['custom_css'] ?? '');

        $revData = json_decode((string) $rev['data'], true) ?: [];
        // Разметку блока «HTML-код» правит только супер-админ — откат версии
        // не должен становиться обходным путём.
        if ((string) $block['type'] === 'html' && !Auth::isSuperAdmin()) {
            $stored = json_decode((string) $block['data'], true);
            $revData['html'] = (string) (is_array($stored) ? ($stored['html'] ?? '') : '');
        }

        $expectedVersion = (int) ($_POST['expected_lock_version'] ?? ($block['lock_version'] ?? 1));
        try {
            BlockVersioning::updateWithSnapshot(
                $block,
                $rev['title'] !== null ? (string) $rev['title'] : null,
                $revData,
                $customCss,
                Auth::id(),
                $expectedVersion
            );
        } catch (ConcurrencyException) {
            Flash::error('Блок уже изменился после открытия истории. Проверьте свежую версию и повторите восстановление.');
            header('Location: /admin/blocks/' . (int) $block['id'] . '/revisions');
            exit;
        }
        \App\Core\Cache::clearPageCache((int) $block['page_id']);

        Flash::success('Блок восстановлен из выбранной версии.');
        header('Location: ' . $this->pageEditUrl($block));
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        Block::delete((int) $block['id']);
        \App\Core\Cache::clearPageCache((int) $block['page_id']);
        Flash::success('Блок удалён.');
        header('Location: ' . $this->pageEditUrl($block));
        exit;
    }

    /** AJAX-сохранение нового порядка блоков (drag-and-drop, задача 134). */
    public function reorder(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=UTF-8');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'CSRF']);
            return;
        }

        $pageId = (int) ($_POST['page_id'] ?? 0);
        $lang = (string) ($_POST['block_lang'] ?? Language::defaultCode());
        if (!Language::isActive($lang)) {
            $lang = Language::defaultCode();
        }
        $order = array_map('intval', (array) ($_POST['order'] ?? []));

        if ($pageId <= 0 || $order === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'bad params']);
            return;
        }

        Block::reorder($pageId, $lang, $order);
        \App\Core\Cache::clearPageCache($pageId);

        echo json_encode(['ok' => true]);
    }

    public function move(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = (string) $block['lang'];
        $direction = $_POST['direction'] ?? '';
        if ($direction === 'up') {
            Block::moveUp((int) $block['id'], (int) $block['page_id'], $lang);
        } elseif ($direction === 'down') {
            Block::moveDown((int) $block['id'], (int) $block['page_id'], $lang);
        }
        \App\Core\Cache::clearPageCache((int) $block['page_id']);

        header('Location: ' . $this->pageEditUrl($block));
        exit;
    }

    /** Включение/отключение вывода блока на сайте (без удаления). */
    public function toggle(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $block = Block::findById((int) $params['id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $newState = (int) ($block['is_active'] ?? 1) !== 1;
        Block::setActive((int) $block['id'], $newState);
        \App\Core\Cache::clearPageCache((int) $block['page_id']);

        Flash::success($newState ? 'Блок включён и снова выводится на сайте.' : 'Блок отключён — он скрыт на сайте, но сохранён.');
        header('Location: ' . $this->pageEditUrl($block));
        exit;
    }

    private function pageEditUrl(array $block): string
    {
        return self::ownerEditUrl((int) $block['page_id'], (string) $block['lang']);
    }

    /**
     * Куда возвращаться после действия над блоком.
     *
     * Проект — страница с подтипом, и конструктор у него встроен в свою форму:
     * возвращать редактора в раздел «Страницы» после правки блока проекта
     * значило бы уводить его из того раздела, где он работает.
     */
    public static function ownerEditUrl(int $pageId, string $lang): string
    {
        $page = Page::findById($pageId);
        $section = ($page !== null && (string) ($page['entity_type'] ?? 'page') === 'project')
            ? '/admin/projects/'
            : '/admin/pages/';

        return $section . $pageId . '/edit?block_lang=' . urlencode($lang);
    }

    /** Валидный #RRGGBB в нижнем регистре или пустая строка (значение по умолчанию). */
    private static function hexOrEmpty(mixed $v): string
    {
        $v = trim((string) $v);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : '';
    }

    /** Цвет из поля $field: '' если включена галочка «$field_off» (по умолчанию). */
    private static function color(string $field): string
    {
        return empty($_POST[$field . '_off']) ? self::hexOrEmpty($_POST[$field] ?? '') : '';
    }

    private function collectData(string $type, string $locale = 'ru'): array
    {
        switch ($type) {
            case 'text':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $itemTitle = trim((string) ($item['title'] ?? ''));
                    if ($itemTitle === '') {
                        continue;
                    }
                    $items[] = [
                        'icon_svg' => \App\Core\Icon::cleanName($item['icon_svg'] ?? ''),
                        'title' => TextProcessor::typographPlain($itemTitle, $locale),
                    ];
                }
                $textVariant = (string) ($_POST['variant'] ?? 'default');
                $mediaType = (string) ($_POST['media_type'] ?? 'none');
                $mediaType = in_array($mediaType, ['none', 'image', 'video', 'youtube'], true)
                    ? $mediaType
                    : 'none';
                $mediaImage = \App\Core\BlockData\BlockDataInput::safeMedia($_POST['media_image'] ?? '');
                $mediaVideo = \App\Core\BlockData\BlockDataInput::safeMedia($_POST['media_video'] ?? '');
                $mediaYoutube = trim((string) ($_POST['media_youtube'] ?? ''));
                if ($mediaType === 'none') {
                    if (\App\Core\Video::youtubeId($mediaYoutube) !== null) {
                        $mediaType = 'youtube';
                    } elseif ($mediaVideo !== '') {
                        $mediaType = 'video';
                    } elseif ($mediaImage !== '') {
                        $mediaType = 'image';
                    }
                }
                return [
                    'variant' => in_array($textVariant, ['default', 'section', 'intro', 'system', 'spotlight'], true) ? $textVariant : 'default',
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'content' => TextProcessor::process(
                        \App\Core\HtmlSanitizer::sanitizeText((string) ($_POST['content'] ?? '')),
                        $locale
                    ),
                    'aside_title' => TextProcessor::typographPlain(trim((string) ($_POST['aside_title'] ?? '')), $locale),
                    'items' => $items,
                    'quote' => TextProcessor::typographPlain(trim((string) ($_POST['quote'] ?? '')), $locale),
                    'media_type' => $mediaType,
                    'media_image' => $mediaImage,
                    'media_video' => $mediaVideo,
                    'media_youtube' => \App\Core\Video::youtubeId($mediaYoutube) !== null ? $mediaYoutube : '',
                    'media_alt' => TextProcessor::typographPlain(trim((string) ($_POST['media_alt'] ?? '')), $locale),
                    'media_caption' => TextProcessor::typographPlain(trim((string) ($_POST['media_caption'] ?? '')), $locale),
                    'image_position' => \App\Core\MediaPosition::normalize($_POST['image_position'] ?? null),
                    'image_position_mobile' => \App\Core\MediaPosition::normalize($_POST['image_position_mobile'] ?? null),
                ];
            case 'html':
                // Даже супер-администратор сохраняет только безопасную
                // разметку: скрипты, inline-стили, on* и опасные URI запрещены.
                $rawHtml = (string) ($_POST['html'] ?? '');
                return [
                    'html' => \App\Core\HtmlSanitizer::sanitize($rawHtml),
                ];
            case 'cta':
                return CtaBlockNormalizer::normalize($_POST, $locale);
            case 'advantages':
                return AdvantagesBlockNormalizer::normalize($_POST, $locale);
            case 'slider':
                $slides = [];
                foreach ((array) ($_POST['slides'] ?? []) as $slide) {
                    $image = trim((string) ($slide['image'] ?? ''));
                    if ($image === '') {
                        continue;
                    }
                    $slideUrl = trim((string) ($slide['url'] ?? ''));
                    $slides[] = [
                        'image' => $image,
                        'alt' => trim((string) ($slide['alt'] ?? '')),
                        'caption' => trim((string) ($slide['caption'] ?? '')),
                        'url' => ($slideUrl !== '' && \App\Core\UrlGuard::isSafeLink($slideUrl)) ? $slideUrl : '',
                    ];
                }
                $sliderRatio = (string) ($_POST['ratio'] ?? '16-9');
                if (!in_array($sliderRatio, ['16-9', '4-3', '21-9', 'auto'], true)) {
                    $sliderRatio = '16-9';
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    // 0 — автопрокрутка выключена; верхний предел бережёт от
                    // «слайд раз в час», который читается как поломка.
                    'autoplay' => max(0, min(30, (int) ($_POST['autoplay'] ?? 0))),
                    'ratio' => $sliderRatio,
                    'slides' => $slides,
                ];
            case 'form':
                $formId = (int) ($_POST['form_id'] ?? 0);
                $layout = in_array($_POST['layout'] ?? '1col', ['1col', '2col'], true) ? (string) $_POST['layout'] : '1col';
                return [
                    'form_id' => $formId > 0 ? $formId : null,
                    'layout' => $layout,
                ];
            case 'columns':
                $cols = (int) ($_POST['columns'] ?? 2);
                if ($cols < 2 || $cols > 4) {
                    $cols = 2;
                }
                $gap = in_array($_POST['gap'] ?? 'medium', ['small', 'medium', 'large'], true)
                    ? (string) $_POST['gap'] : 'medium';
                $ratio = \App\Core\ColumnRatio::normalize((string) ($_POST['ratio'] ?? ''), $cols);
                return ['columns' => $cols, 'gap' => $gap, 'ratio' => $ratio];
            case 'tabs':
                // Содержимое вкладок — вложенные блоки, здесь только подписи.
                $tabItems = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $tabTitle = \App\Core\BlockData\BlockDataInput::plain($item, 'title', $locale);
                    if ($tabTitle === '') {
                        continue;
                    }
                    $tabItems[] = ['title' => $tabTitle, 'icon' => \App\Core\Icon::cleanName($item['icon'] ?? '')];
                }
                return [
                    'variant' => \App\Core\BlockData\BlockDataInput::enum(
                        $_POST,
                        'variant',
                        ['segmented', 'underline', 'vertical'],
                        'segmented'
                    ),
                    'align' => \App\Core\BlockData\BlockDataInput::enum(
                        $_POST,
                        'align',
                        ['left', 'center', 'stretch'],
                        'left'
                    ),
                    'title' => \App\Core\BlockData\BlockDataInput::plain($_POST, 'title_field', $locale),
                    'description' => TextProcessor::process(
                        \App\Core\HtmlSanitizer::sanitizeText((string) ($_POST['description'] ?? '')),
                        $locale,
                    ),
                    'items' => array_slice($tabItems, 0, 10),
                ];
            case 'testimonials':
                return TestimonialsBlockNormalizer::normalize($_POST, $locale);
            case 'counters':
                return CountersBlockNormalizer::normalize($_POST, $locale);
            case 'team_list':
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'limit' => max(0, (int) ($_POST['limit'] ?? 0)),
                    'department' => trim((string) ($_POST['department'] ?? '')),
                    'group_by_department' => !empty($_POST['group_by_department']),
                ];
            case 'projects_list':
                return [
                    'variant' => \App\Core\BlockData\BlockDataInput::enum(
                        $_POST,
                        'variant',
                        ['grid', 'list', 'carousel'],
                        'grid'
                    ),
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::typographPlain(trim((string) ($_POST['description'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    'columns' => max(2, min(4, (int) ($_POST['columns'] ?? 3))),
                    'limit' => max(0, (int) ($_POST['limit'] ?? 0)),
                    // 0 — без автопрокрутки; верхняя граница та же, что у
                    // остальных каруселей.
                    'autoplay' => \App\Core\BlockData\BlockDataInput::int($_POST, 'autoplay', 0, 30, 0),
                ];
            case 'news_latest':
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    'limit' => max(0, (int) ($_POST['limit'] ?? 0)),
                    'category' => max(0, (int) ($_POST['category'] ?? 0)),
                ];
            case 'partners':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $logo = trim((string) ($item['logo'] ?? ''));
                    if ($logo === '') {
                        continue;
                    }
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $items[] = [
                        'logo' => $logo,
                        'name' => trim((string) ($item['name'] ?? '')),
                        'url' => $url,
                    ];
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::typographPlain(trim((string) ($_POST['description'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    'columns' => \App\Core\BlockData\BlockDataInput::int($_POST, 'columns', 3, 8, 6),
                    'logo_size' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'logo_size', ['small', 'medium', 'large'], 'medium'),
                    'grayscale' => !empty($_POST['grayscale']),
                    'autoplay' => \App\Core\BlockData\BlockDataInput::int($_POST, 'autoplay', 0, 30, 0),
                    'items' => $items,
                ];
            case 'subscribe':
                return SubscribeBlockNormalizer::normalize($_POST, $locale);
            case 'faq':
                return FaqBlockNormalizer::normalize($_POST, $locale);
            case 'contact_cards':
                return ContactCardsBlockNormalizer::normalize($_POST, $locale);
            case 'hero':
                return HeroBlockNormalizer::normalize($_POST, $locale);
            case 'cards_grid':
            case 'media_gallery':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $label = trim((string) ($item['title'] ?? $item['label'] ?? ''));
                    $image = trim((string) ($item['image'] ?? ''));
                    if ($image !== '' && !\App\Core\UrlGuard::isSafeMedia($image)) {
                        $image = '';
                    }
                    if ($label === '' && ($type !== 'media_gallery' || $image === '')) {
                        continue;
                    }
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $iconSvg = \App\Core\Icon::cleanName($item['icon_svg'] ?? '');
                    $items[] = [
                        'icon_svg' => $iconSvg,
                        'image' => $image,
                        'title' => TextProcessor::typographPlain($label, $locale),
                        'text' => TextProcessor::typographPlain(trim((string) ($item['text'] ?? '')), $locale),
                        'meta' => TextProcessor::typographPlain(trim((string) ($item['meta'] ?? '')), $locale),
                        'kind' => ($item['kind'] ?? '') === 'photo' ? 'photo' : 'video',
                        'url' => $url,
                    ];
                }
                // Медиатека держит ритм четырёх колонок, карточки — пяти.
                $cols = (int) ($_POST['columns'] ?? ($type === 'media_gallery' ? 4 : 5));
                // Источник данных: «Проекты» (cards_grid с фото) или «Фотоальбомы»
                // (media_gallery) собирают карточки из отмеченных «на главной»
                // записей автоматически; иначе — ручной список items.
                $source = 'manual';
                if ($type === 'cards_grid' && ($_POST['source'] ?? '') === 'projects') {
                    $source = 'projects';
                } elseif ($type === 'media_gallery' && in_array($_POST['source'] ?? '', ['albums', 'videos', 'media'], true)) {
                    $source = (string) $_POST['source'];
                }
                $variant = $type === 'cards_grid'
                    && in_array($_POST['variant'] ?? 'icon', ['icon', 'compact', 'image', 'image_below'], true)
                    ? (string) $_POST['variant']
                    : 'icon';
                // Проекты собираются с обложками, поэтому вариант без фото им не
                // подходит; но выбор между текстом на фото и текстом под ним
                // остаётся за редактором.
                if ($source === 'projects' && !in_array($variant, ['image', 'image_below'], true)) {
                    $variant = 'image';
                }
                $collected = [
                    'variant' => $variant,
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => (trim((string) ($_POST['all_url'] ?? '')) !== '' && \App\Core\UrlGuard::isSafeLink(trim((string) ($_POST['all_url'] ?? '')))) ? trim((string) ($_POST['all_url'] ?? '')) : '',
                    'columns' => max(2, min(5, $cols)),
                    'card_bg' => self::color('card_bg'),
                    'text_color' => self::color('text_color'),
                    'source' => $source,
                    'limit' => max(2, min(24, (int) ($_POST['limit'] ?? 6))),
                    'image_position' => \App\Core\MediaPosition::normalize($_POST['image_position'] ?? null),
                    'image_position_mobile' => \App\Core\MediaPosition::normalize($_POST['image_position_mobile'] ?? null),
                    'items' => $items,
                ];
                if ($type === 'media_gallery') {
                    // Медиагалерея делит поля с карточками, но у неё свои
                    // вводный текст и пропорция плитки.
                    $collected['description'] = TextProcessor::typographPlain(trim((string) ($_POST['description'] ?? '')), $locale);
                    $collected['ratio'] = \App\Core\BlockData\BlockDataInput::enum($_POST, 'ratio', ['16-9', '4-3', '1-1'], '16-9');
                }
                return $collected;
            case 'news_feature':
                return [
                    'variant' => ($_POST['variant'] ?? 'cards') === 'mosaic' ? 'mosaic' : 'cards',
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => (trim((string) ($_POST['all_url'] ?? '')) !== '' && \App\Core\UrlGuard::isSafeLink(trim((string) ($_POST['all_url'] ?? '')))) ? trim((string) ($_POST['all_url'] ?? '')) : '',
                    'limit' => max(2, min(12, (int) ($_POST['limit'] ?? 6))),
                    'category' => max(0, (int) ($_POST['category'] ?? 0)),
                ];
            case 'person_cards':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $name = trim((string) ($item['name'] ?? ''));
                    $role = trim((string) ($item['role'] ?? ''));
                    if ($name === '' && $role === '') {
                        continue;
                    }
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $items[] = [
                        'photo' => trim((string) ($item['photo'] ?? '')),
                        'name' => TextProcessor::typographPlain($name, $locale),
                        'role' => TextProcessor::typographPlain($role, $locale),
                        'phone' => trim((string) ($item['phone'] ?? '')),
                        'email' => trim((string) ($item['email'] ?? '')),
                        'url' => $url,
                    ];
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::typographPlain(trim((string) ($_POST['description'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    'columns' => \App\Core\BlockData\BlockDataInput::int($_POST, 'columns', 2, 5, 4),
                    'items' => $items,
                ];
            case 'timeline':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $year = trim((string) ($item['year'] ?? ''));
                    $text = trim((string) ($item['text'] ?? ''));
                    if ($year === '' && $text === '') {
                        continue;
                    }
                    $items[] = [
                        'year' => $year,
                        'text' => TextProcessor::typographPlain($text, $locale),
                        'status' => in_array($item['status'] ?? '', ['done', 'active', 'planned'], true)
                            ? $item['status']
                            : 'planned',
                    ];
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::process(
                        \App\Core\HtmlSanitizer::sanitizeText((string) ($_POST['description'] ?? '')),
                        $locale
                    ),
                    'items' => $items,
                    'button_text' => trim((string) ($_POST['button_text'] ?? '')),
                    'button_url' => $this->safeUrlField('button_url'),
                    'cta_title' => TextProcessor::typographPlain(trim((string) ($_POST['cta_title'] ?? '')), $locale),
                    'cta_text' => TextProcessor::typographPlain(trim((string) ($_POST['cta_text'] ?? '')), $locale),
                    'cta_button_text' => trim((string) ($_POST['cta_button_text'] ?? '')),
                    'cta_button_url' => $this->safeUrlField('cta_button_url'),
                    'cta_image' => trim((string) ($_POST['cta_image'] ?? '')),
                ];
            case 'news_docs':
                $docs = [];
                foreach ((array) ($_POST['docs'] ?? []) as $doc) {
                    $docTitle = trim((string) ($doc['title'] ?? ''));
                    if ($docTitle === '') {
                        continue;
                    }
                    $url = trim((string) ($doc['url'] ?? ''));
                    if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $docs[] = [
                        'title' => TextProcessor::typographPlain($docTitle, $locale),
                        'meta' => trim((string) ($doc['meta'] ?? '')),
                        'url' => $url,
                    ];
                }
                return [
                    'news_title' => TextProcessor::typographPlain(trim((string) ($_POST['news_title'] ?? '')), $locale),
                    'news_all_text' => trim((string) ($_POST['news_all_text'] ?? '')),
                    'news_all_url' => $this->safeUrlField('news_all_url'),
                    'limit' => max(1, min(6, (int) ($_POST['limit'] ?? 3))),
                    'category' => max(0, (int) ($_POST['category'] ?? 0)),
                    'docs_title' => TextProcessor::typographPlain(trim((string) ($_POST['docs_title'] ?? '')), $locale),
                    'docs_all_text' => trim((string) ($_POST['docs_all_text'] ?? '')),
                    'docs_all_url' => $this->safeUrlField('docs_all_url'),
                    'docs' => $docs,
                ];
            case 'icon_text':
                $iconRows = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $rows = trim((string) ($item['rows'] ?? ''));
                    $icon = \App\Core\Icon::cleanName($item['icon_svg'] ?? '');
                    if ($rows === '' && $icon === '') {
                        continue;
                    }
                    $iconRows[] = [
                        'icon_svg' => $icon,
                        // Пустой цвет = оттенок акцента сайта. Мусор в поле не
                        // должен попасть в разметку, поэтому нормализуем тем же
                        // помощником, что и цвет метки новости.
                        'icon_color' => \App\Core\NewsBadge::normalizeColor($item['icon_color'] ?? ''),
                        'rows' => TextProcessor::typographPlain($rows, $locale),
                    ];
                }

                return [
                    'variant' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'variant', ['cards', 'plain', 'inline'], 'cards'),
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::typographPlain(trim((string) ($_POST['description'] ?? '')), $locale),
                    'icon_position' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'icon_position', ['left', 'top', 'right'], 'left'),
                    'rows_layout' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'rows_layout', ['stacked', 'inline'], 'stacked'),
                    'align' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'align', ['left', 'center'], 'left'),
                    'columns' => max(1, min(4, (int) ($_POST['columns'] ?? 3))),
                    'items' => $iconRows,
                ];
            case 'leader_card':
                $facts = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $label = trim((string) ($item['label'] ?? ''));
                    $value = trim((string) ($item['value'] ?? ''));
                    if ($label === '' && $value === '') {
                        continue;
                    }
                    $facts[] = [
                        'icon_svg' => \App\Core\Icon::cleanName($item['icon_svg'] ?? ''),
                        'label' => TextProcessor::typographPlain($label, $locale),
                        'value' => TextProcessor::typographPlain($value, $locale),
                    ];
                }

                return [
                    'photo' => trim((string) ($_POST['photo'] ?? '')),
                    'name' => TextProcessor::typographPlain(trim((string) ($_POST['name'] ?? '')), $locale),
                    // Уровень заголовка выбирает редактор: карточка стоит на
                    // разных страницах, и что для одной h2, для другой h3.
                    'name_tag' => in_array($_POST['name_tag'] ?? 'p', ['p', 'h2', 'h3'], true) ? (string) $_POST['name_tag'] : 'p',
                    'position' => TextProcessor::typographPlain(trim((string) ($_POST['position'] ?? '')), $locale),
                    'phone' => trim((string) ($_POST['phone'] ?? '')),
                    'email' => trim((string) ($_POST['email'] ?? '')),
                    'hours' => TextProcessor::typographPlain(trim((string) ($_POST['hours'] ?? '')), $locale),
                    // Соцсети — только безопасные ссылки: адрес приходит из формы
                    // и попадает в href, javascript: там быть не должно.
                    'facebook' => $this->safeUrlField('facebook'),
                    'x' => $this->safeUrlField('x'),
                    'linkedin' => $this->safeUrlField('linkedin'),
                    'instagram' => $this->safeUrlField('instagram'),
                    'telegram' => $this->safeUrlField('telegram'),
                    'facts_title' => TextProcessor::typographPlain(trim((string) ($_POST['facts_title'] ?? '')), $locale),
                    'facts_icon' => \App\Core\Icon::cleanName($_POST['facts_icon'] ?? ''),
                    'items' => $facts,
                    'bio_title' => TextProcessor::typographPlain(trim((string) ($_POST['bio_title'] ?? '')), $locale),
                    'bio_icon' => \App\Core\Icon::cleanName($_POST['bio_icon'] ?? ''),
                    'bio' => TextProcessor::process((string) ($_POST['bio'] ?? ''), $locale),
                    'duties_title' => TextProcessor::typographPlain(trim((string) ($_POST['duties_title'] ?? '')), $locale),
                    'duties_icon' => \App\Core\Icon::cleanName($_POST['duties_icon'] ?? ''),
                    'duties' => TextProcessor::process((string) ($_POST['duties'] ?? ''), $locale),
                    'mobile_icons_only' => !empty($_POST['mobile_icons_only']),
                ];
            case 'person_profile':
                return [
                    'photo' => trim((string) ($_POST['photo'] ?? '')),
                    'photo_side' => ($_POST['photo_side'] ?? 'left') === 'right' ? 'right' : 'left',
                    'name' => TextProcessor::typographPlain(trim((string) ($_POST['name'] ?? '')), $locale),
                    'position' => TextProcessor::typographPlain(trim((string) ($_POST['position'] ?? '')), $locale),
                    'text' => TextProcessor::typographPlain(trim((string) ($_POST['text'] ?? '')), $locale),
                    'phone' => trim((string) ($_POST['phone'] ?? '')),
                    'phone_label' => trim((string) ($_POST['phone_label'] ?? 'Приёмная:')),
                    'email' => trim((string) ($_POST['email'] ?? '')),
                    'email_label' => trim((string) ($_POST['email_label'] ?? 'E-mail:')),
                    'button_text' => trim((string) ($_POST['button_text'] ?? '')),
                    'button_url' => $this->safeUrlField('button_url'),
                    'button2_text' => trim((string) ($_POST['button2_text'] ?? '')),
                    'button2_url' => $this->safeUrlField('button2_url'),
                    'telegram' => $this->safeUrlField('telegram'),
                    'facebook' => $this->safeUrlField('facebook'),
                    'linkedin' => $this->safeUrlField('linkedin'),
                    'x' => $this->safeUrlField('x'),
                    'instagram' => $this->safeUrlField('instagram'),
                ];
            case 'bio_education':
                $collect = static function (string $key, array $fields) use ($locale): array {
                    $rows = [];
                    foreach ((array) ($_POST[$key] ?? []) as $row) {
                        $vals = [];
                        $empty = true;
                        foreach ($fields as $f) {
                            $v = trim((string) ($row[$f] ?? ''));
                            if ($v !== '') {
                                $empty = false;
                            }
                            $vals[$f] = $f === 'years' ? $v : TextProcessor::typographPlain($v, $locale);
                        }
                        if (!$empty) {
                            $rows[] = $vals;
                        }
                    }
                    return $rows;
                };
                $collectWidgetIds = static function (string $key): array {
                    $ids = [];
                    foreach ((array) ($_POST[$key] ?? []) as $rawId) {
                        if (!is_scalar($rawId)) {
                            continue;
                        }
                        $id = (int) $rawId;
                        if ($id > 0 && !in_array($id, $ids, true)) {
                            $ids[] = $id;
                        }
                        if (count($ids) >= 12) {
                            break;
                        }
                    }
                    return $ids;
                };
                $widgetsBefore = $collectWidgetIds('widgets_before');
                $widgetsAfter = array_values(array_diff(
                    $collectWidgetIds('widgets_after'),
                    $widgetsBefore
                ));
                return [
                    'bio_title' => TextProcessor::typographPlain(trim((string) ($_POST['bio_title'] ?? 'Биография')), $locale),
                    'bio_text' => TextProcessor::typographPlain(trim((string) ($_POST['bio_text'] ?? '')), $locale),
                    'career_title' => TextProcessor::typographPlain(trim((string) ($_POST['career_title'] ?? '')), $locale),
                    'career' => $collect('career', ['years', 'text']),
                    'edu_title' => TextProcessor::typographPlain(trim((string) ($_POST['edu_title'] ?? 'Образование')), $locale),
                    'edu_items' => $collect('edu_items', ['years', 'title', 'org']),
                    'extra_title' => TextProcessor::typographPlain(trim((string) ($_POST['extra_title'] ?? '')), $locale),
                    'extra_text' => TextProcessor::typographPlain(trim((string) ($_POST['extra_text'] ?? '')), $locale),
                    'widgets_before' => $widgetsBefore,
                    'widgets_after' => $widgetsAfter,
                    'quote_text' => TextProcessor::typographPlain(trim((string) ($_POST['quote_text'] ?? '')), $locale),
                    'quote_author' => TextProcessor::typographPlain(trim((string) ($_POST['quote_author'] ?? '')), $locale),
                ];
            case 'anchor_nav':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $label = trim((string) ($item['label'] ?? ''));
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    // Разрешаем якоря #... и обычные безопасные ссылки.
                    if ($url !== '' && $url[0] !== '#' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $items[] = ['label' => TextProcessor::typographPlain($label, $locale), 'url' => $url !== '' ? $url : '#'];
                }
                return [
                    'items' => $items,
                    'auto' => !empty($_POST['auto']),
                    'sticky' => !empty($_POST['sticky']),
                ];
            case 'stages':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $year = trim((string) ($item['year'] ?? ''));
                    $itemTitle = trim((string) ($item['title'] ?? ''));
                    if ($year === '' && $itemTitle === '') {
                        continue;
                    }
                    $items[] = [
                        'year' => $year,
                        'stage' => trim((string) ($item['stage'] ?? '')),
                        'title' => TextProcessor::typographPlain($itemTitle, $locale),
                        'text' => TextProcessor::typographPlain(trim((string) ($item['text'] ?? '')), $locale),
                        'status' => in_array($item['status'] ?? '', ['done', 'active', 'planned'], true) ? $item['status'] : 'planned',
                        'status_text' => trim((string) ($item['status_text'] ?? '')),
                        'url' => \App\Core\BlockData\BlockDataInput::safeLink($item['url'] ?? ''),
                    ];
                }
                return [
                    'variant' => ($_POST['variant'] ?? 'default') === 'history' ? 'history' : 'default',
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'description' => TextProcessor::process(
                        \App\Core\HtmlSanitizer::sanitizeText((string) ($_POST['description'] ?? '')),
                        $locale
                    ),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    // 0 — колонок ровно по числу этапов (прежнее поведение).
                    'columns' => \App\Core\BlockData\BlockDataInput::int($_POST, 'columns', 0, 5, 0),
                    'autoplay' => \App\Core\BlockData\BlockDataInput::int($_POST, 'autoplay', 0, 30, 0),
                    'items' => $items,
                ];
            case 'text_image':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $label = trim((string) ($item['label'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    $iconSvg = \App\Core\Icon::cleanName($item['icon_svg'] ?? '');
                    $items[] = ['icon_svg' => $iconSvg, 'label' => TextProcessor::typographPlain($label, $locale)];
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'text' => TextProcessor::process(
                        \App\Core\HtmlSanitizer::sanitizeText((string) ($_POST['text'] ?? '')),
                        $locale
                    ),
                    'image' => \App\Core\UrlGuard::isSafeMedia(trim((string) ($_POST['image'] ?? '')))
                        ? trim((string) ($_POST['image'] ?? ''))
                        : '',
                    'image_position' => \App\Core\MediaPosition::normalize($_POST['image_position'] ?? null),
                    'image_position_mobile' => \App\Core\MediaPosition::normalize($_POST['image_position_mobile'] ?? null),
                    'image_side' => ($_POST['image_side'] ?? 'right') === 'left' ? 'left' : 'right',
                    'image_ratio' => \App\Core\BlockData\BlockDataInput::enum($_POST, 'image_ratio', ['auto', '16-9', '4-3', '1-1'], 'auto'),
                    // Доля ширины под кадр: остальное занимает текстовая колонка.
                    'image_width' => \App\Core\BlockData\BlockDataInput::int($_POST, 'image_width', 30, 60, 50),
                    'button_text' => trim((string) ($_POST['button_text'] ?? '')),
                    'button_url' => $this->safeUrlField('button_url'),
                    'items' => $items,
                ];
            case 'docs_list':
                $items = [];
                foreach ((array) ($_POST['items'] ?? []) as $item) {
                    $itemTitle = trim((string) ($item['title'] ?? ''));
                    if ($itemTitle === '') {
                        continue;
                    }
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                        $url = '';
                    }
                    $items[] = [
                        'title' => TextProcessor::typographPlain($itemTitle, $locale),
                        'meta' => trim((string) ($item['meta'] ?? '')),
                        'url' => $url,
                        // Реквизиты акта: показываются только в варианте
                        // «Правовые акты», в остальных лежат про запас.
                        'number' => trim((string) ($item['number'] ?? '')),
                        'date' => trim((string) ($item['date'] ?? '')),
                    ];
                }
                return [
                    'variant' => in_array($_POST['variant'] ?? 'grid', ['grid', 'links', 'acts', 'acts-editorial'], true) ? (string) $_POST['variant'] : 'grid',
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'all_text' => trim((string) ($_POST['all_text'] ?? '')),
                    'all_url' => $this->safeUrlField('all_url'),
                    'columns' => max(1, min(5, (int) ($_POST['columns'] ?? 4))),
                    'search_enabled' => !empty($_POST['search_enabled']),
                    'items' => $items,
                ];
            case 'map_point':
                $embed = \App\Core\MapEmbedUrl::normalize($_POST['embed_url'] ?? '');
                $mapImage = trim((string) ($_POST['image'] ?? ''));
                if ($mapImage !== '' && !\App\Core\UrlGuard::isSafeMedia($mapImage)) {
                    $mapImage = '';
                }
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'image' => $mapImage,
                    'embed_url' => $embed,
                    'load_mode' => ($_POST['load_mode'] ?? 'click') === 'immediate' ? 'immediate' : 'click',
                    'card_title' => TextProcessor::typographPlain(trim((string) ($_POST['card_title'] ?? '')), $locale),
                    'address' => trim((string) ($_POST['address'] ?? '')),
                    'copy_enabled' => !empty($_POST['copy_enabled']),
                    'button_text' => trim((string) ($_POST['button_text'] ?? '')),
                    'button_url' => $this->safeUrlField('button_url'),
                ];

            case 'org_structure':
                $branches = [];
                foreach ((array) ($_POST['branches'] ?? []) as $branch) {
                    $bTitle = trim((string) ($branch['title'] ?? ''));
                    $bName = trim((string) ($branch['name'] ?? ''));
                    $bUnits = trim((string) ($branch['units'] ?? ''));
                    if ($bTitle === '' && $bName === '' && $bUnits === '') {
                        continue;
                    }
                    $branchUrl = trim((string) ($branch['url'] ?? ''));
                    if ($branchUrl !== '' && !\App\Core\UrlGuard::isSafeLink($branchUrl)) {
                        $branchUrl = '';
                    }
                    $branches[] = [
                        'title' => TextProcessor::typographPlain($bTitle, $locale),
                        'name' => trim($bName),
                        'url' => $branchUrl,
                        'units' => $bUnits,
                    ];
                }
                $orgColumns = (int) ($_POST['columns'] ?? 4);

                // Построчные поля сохраняются как есть: типографика съела бы
                // служебные маркеры разметки («- группа», «* проектный офис»).
                return [
                    'title' => TextProcessor::typographPlain(trim((string) ($_POST['title_field'] ?? '')), $locale),
                    'layout' => ($_POST['layout'] ?? 'tree') === 'spine' ? 'spine' : 'tree',
                    'columns' => max(2, min(4, $orgColumns)),
                    'council' => trim((string) ($_POST['council'] ?? '')),
                    'head_title' => TextProcessor::typographPlain(trim((string) ($_POST['head_title'] ?? '')), $locale),
                    'head_name' => trim((string) ($_POST['head_name'] ?? '')),
                    'head_url' => $this->safeUrlField('head_url'),
                    'side_items' => trim((string) ($_POST['side_items'] ?? '')),
                    'branches' => $branches,
                    'collapsible' => !empty($_POST['collapsible']),
                    'search' => !empty($_POST['org_search']),
                    'notes' => trim((string) ($_POST['notes'] ?? '')),
                    'footnote' => TextProcessor::typographPlain(trim((string) ($_POST['footnote'] ?? '')), $locale),
                ];
            default:
                return [];
        }
    }

    /**
     * Секторы команды нужны формам блоков «Команда» (фильтр) и «Оргструктура»
     * (готовые якори для ссылок). Для остальных типов запрос не делаем.
     *
     * @return list<array{name: string, slug: string, count: int}>
     */
    private static function departmentsFor(string $type): array
    {
        return in_array($type, ['team_list', 'org_structure'], true)
            ? \App\Models\TeamMember::departments()
            : [];
    }

    /** Читает URL-поле из POST и отбрасывает небезопасные схемы (javascript: и т.п.). */
    private function safeUrlField(string $field): string
    {
        $url = trim((string) ($_POST[$field] ?? ''));

        return ($url !== '' && \App\Core\UrlGuard::isSafeLink($url)) ? $url : '';
    }
}

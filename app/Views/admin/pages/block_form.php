<?php

use App\Core\Csrf;

$pageTitle = 'Редактирование блока';
$activeNav = 'pages';
require __DIR__ . '/../layout/header.php';

/** @var array $block */
/** @var array $data */
/** @var array $forms */
/** @var array $widgets */
/** @var string|null $error */

$type = $block['type'];
$error = $error ?? null;
$widgets = $widgets ?? [];
$backUrl = '/admin/pages/' . (int) $block['page_id'] . '/edit?block_lang=' . urlencode((string) ($block['lang'] ?? ''));
?>
<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<a href="<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>" class="btn btn--small u-inline-79a1c5a5db">&larr; Назад к странице</a>
<a href="/admin/blocks/<?= (int) $block['id'] ?>/revisions" class="btn btn--small u-inline-79a1c5a5db">История изменений</a>

<div class="form-card">
    <form method="post" action="/admin/blocks/<?= (int) $block['id'] ?>/edit" class="form-grid" data-content-draft="block:<?= (int) $block['id'] ?>" data-record-updated="<?= htmlspecialchars((string) ($block['updated_at'] ?? ''), ENT_QUOTES) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="expected_lock_version" value="<?= (int) ($block['lock_version'] ?? 1) ?>">

        <div class="form-field">
            <label for="title">Внутреннее название блока</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($block['title'] ?? '', ENT_QUOTES) ?>">
        </div>

        <?php if (in_array($type, ['text', 'cta', 'advantages', 'slider', 'testimonials', 'counters', 'team_list', 'projects_list', 'news_latest', 'partners', 'faq', 'subscribe', 'contact_cards', 'hero', 'cards_grid', 'media_gallery', 'news_feature', 'person_cards', 'timeline', 'stages', 'text_image', 'docs_list', 'map_point', 'org_structure', 'icon_text', 'tabs'], true)): ?>
            <div class="form-field">
                <label for="title_field"><?= in_array($type, ['advantages', 'timeline', 'stages'], true) ? 'Заголовок раздела' : 'Заголовок, показываемый на сайте' ?></label>
                <input type="text" id="title_field" name="title_field" value="<?= htmlspecialchars($data['title'] ?? '', ENT_QUOTES) ?>">
            </div>
        <?php endif; ?>

        <?php if (in_array($type, ['advantages', 'timeline', 'stages'], true)): ?>
            <div class="form-field">
                <label for="section_description">Описание раздела</label>
                <textarea id="section_description" name="description" rows="4" data-wysiwyg><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
                <span class="form-hint">Заголовок и описание выводятся над содержимым блока — отдельный текстовый блок не нужен.</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'text'): ?>
            <div class="form-field">
                <label for="text_variant">Вариант отображения</label>
                <select id="text_variant" name="variant">
                    <?php foreach (['default' => 'Обычный текст', 'section' => 'Вступление к разделу', 'intro' => 'Вводный блок с принципами', 'system' => 'Текст + системный список', 'spotlight' => 'Текст + акцентная цитата'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['variant'] ?? 'default') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Специальные варианты остаются обычными системными блоками и адаптируются автоматически.</span>
            </div>
            <div class="form-field">
                <label for="content">Текст</label>
                <textarea class="u-inline-9bef318bc9" id="content" name="content" data-wysiwyg><?= htmlspecialchars($data['content'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <?php
            $textMediaType = (string) ($data['media_type'] ?? 'none');
            $textMediaType = in_array($textMediaType, ['none', 'image', 'video', 'youtube'], true) ? $textMediaType : 'none';
            ?>
            <div class="form-field">
                <label for="text_media_type">Медиа справа во вводном блоке</label>
                <select id="text_media_type" name="media_type">
                    <option value="none" <?= $textMediaType === 'none' ? 'selected' : '' ?>>Фирменная заглушка</option>
                    <option value="image" <?= $textMediaType === 'image' ? 'selected' : '' ?>>Фотография</option>
                    <option value="video" <?= $textMediaType === 'video' ? 'selected' : '' ?>>Видео из медиабиблиотеки</option>
                    <option value="youtube" <?= $textMediaType === 'youtube' ? 'selected' : '' ?>>Видео с YouTube</option>
                </select>
                <span class="form-hint">Показывается у варианта «Вводный блок с принципами». Если файл не выбран, справа остаётся аккуратная фирменная композиция.</span>
            </div>
            <?= \App\Core\AdminUi::imageField('media_image', (string) ($data['media_image'] ?? ''), [
                'label' => 'Фотография / постер видео',
                'hint' => 'Выберите изображение из медиабиблиотеки. Для видео оно используется как заставка.',
            ]) ?>
            <?= \App\Core\AdminUi::mediaPositionFields($data['image_position'] ?? 'center-center', $data['image_position_mobile'] ?? 'center-center') ?>
            <div class="form-field">
                <label for="text_media_video">Видео из медиабиблиотеки (mp4)</label>
                <div class="image-field__controls">
                    <input type="text" id="text_media_video" name="media_video" value="<?= htmlspecialchars($data['media_video'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/public/about.mp4">
                    <button type="button" class="btn btn--small" data-media-pick data-media-target="#text_media_video" data-media-type="video">Медиабиблиотека</button>
                </div>
            </div>
            <div class="form-field">
                <label for="text_media_youtube">Ссылка на YouTube</label>
                <input type="text" id="text_media_youtube" name="media_youtube" value="<?= htmlspecialchars($data['media_youtube'] ?? '', ENT_QUOTES) ?>" placeholder="https://www.youtube.com/watch?v=…">
            </div>
            <div class="form-field">
                <label for="text_media_alt">Описание изображения</label>
                <input type="text" id="text_media_alt" name="media_alt" value="<?= htmlspecialchars($data['media_alt'] ?? '', ENT_QUOTES) ?>" placeholder="Что изображено на фотографии">
                <span class="form-hint">Нужно для доступности. Для декоративной фотографии можно оставить пустым.</span>
            </div>
            <div class="form-field">
                <label for="text_media_caption">Подпись под медиа</label>
                <input type="text" id="text_media_caption" name="media_caption" value="<?= htmlspecialchars($data['media_caption'] ?? '', ENT_QUOTES) ?>" placeholder="Необязательная подпись или источник">
            </div>
            <div class="form-field">
                <label for="aside_title">Заголовок структурированного списка</label>
                <input type="text" id="aside_title" name="aside_title" value="<?= htmlspecialchars($data['aside_title'] ?? '', ENT_QUOTES) ?>">
                <span class="form-hint">Используется вариантом «Текст + системный список».</span>
            </div>
            <div>
                <label>Структурированные пункты</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field"><label>Подпись</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field"><label>Подпись</label><input type="text" name="items[__INDEX__][title]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить пункт</button></div>
            </div>
            <div class="form-field">
                <label for="quote">Акцентная цитата</label>
                <textarea id="quote" name="quote" rows="4"><?= htmlspecialchars($data['quote'] ?? '', ENT_QUOTES) ?></textarea>
                <span class="form-hint">Используется вариантом «Текст + акцентная цитата».</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'html'): ?>
            <div class="form-field">
                <label for="html">HTML-код блока</label>
                <textarea class="u-inline-6650b8308c" id="html" name="html"<?= \App\Core\Auth::isSuperAdmin() ? '' : ' readonly' ?>><?= htmlspecialchars($data['html'] ?? '', ENT_QUOTES) ?></textarea>
                <span class="form-hint">Разрешена безопасная разметка без script, style, обработчиков on* и iframe. Для карт и видео используйте специальные блоки.</span>
                <?php if (!\App\Core\Auth::isSuperAdmin()): ?>
                    <span class="form-hint">Разметку этого блока меняет только супер-администратор — остальные поля блока доступны как обычно.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($type === 'cta'): ?>
            <div class="form-field">
                <label for="cta_variant">Вариант блока</label>
                <select id="cta_variant" name="variant">
                    <?php foreach (['card' => 'Карточка призыва', 'band' => 'Компактная полоса', 'media-dark' => 'Фото с затемнением', 'media-light' => 'Светлый сплит с фото'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['variant'] ?? 'card') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Выберите карточку, компактную полосу или один из двух макетов с фотографией.</span>
            </div>
            <div class="form-field">
                <label for="text">Текст</label>
                <textarea id="text" name="text"><?= htmlspecialchars($data['text'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <?= \App\Core\AdminUi::iconField('icon_svg', $data['icon_svg'] ?? '', ['id' => 'icon_svg', 'label' => 'Иконка для варианта «Полоса»']) ?>
            <?= \App\Core\AdminUi::imageField('image', $data['image'] ?? '', ['label' => 'Изображение для вариантов с фото']) ?>
            <?= \App\Core\AdminUi::mediaPositionFields($data['image_position'] ?? 'center-center', $data['image_position_mobile'] ?? 'center-center') ?>
            <div class="form-field">
                <label for="button_text">Текст кнопки</label>
                <input type="text" id="button_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>">
            </div>
            <div class="form-field">
                <label for="button_url">Ссылка кнопки</label>
                <input type="text" id="button_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>">
            </div>
            <div class="colorfield-row">
                <?= \App\Core\AdminUi::colorField('bg_color', $data['bg_color'] ?? '', 'Цвет фона', '#eef2f7') ?>
                <?= \App\Core\AdminUi::colorField('text_color', $data['text_color'] ?? '', 'Цвет текста', '#173a63') ?>
                <?= \App\Core\AdminUi::colorField('button_color', $data['button_color'] ?? '', 'Цвет фона кнопки', '#17999b') ?>
            </div>
        <?php endif; ?>

        <?php if ($type === 'advantages'): ?>
            <div class="form-field">
                <label for="advantages_variant">Вариант отображения</label>
                <select id="advantages_variant" name="variant">
                    <option value="grid" <?= ($data['variant'] ?? 'grid') === 'grid' ? 'selected' : '' ?>>Карточки</option>
                    <option value="indexed" <?= ($data['variant'] ?? 'grid') === 'indexed' ? 'selected' : '' ?>>Карточки с нумерацией</option>
                    <option value="inline" <?= ($data['variant'] ?? 'grid') === 'inline' ? 'selected' : '' ?>>Иконка и заголовок в одну строку</option>
                    <option value="band" <?= ($data['variant'] ?? 'grid') === 'band' ? 'selected' : '' ?>>Компактная полоса</option>
                </select>
                <span class="form-hint">В варианте «в одну строку» иконка стоит рядом с заголовком, а не над ним — карточка получается ниже, и в ряд их влезает больше.</span>
            </div>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все направления"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field">
                <label for="adv_columns">Колонок в сетке</label>
                <select id="adv_columns" name="columns">
                    <option value="0" <?= (int) ($data['columns'] ?? 0) === 0 ? 'selected' : '' ?>>Автоматически по числу карточек</option>
                    <?php foreach ([2, 3, 4, 5] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 0) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Автоматический режим не оставляет в последнем ряду одинокую карточку. Не действует в варианте «Компактная полоса».</span>
            </div>
            <div>
                <label>Пункты преимуществ</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field">
                                <label>Заголовок</label>
                                <input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="form-field">
                                <label>Текст</label>
                                <textarea name="items[<?= $i ?>][text]"><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></textarea>
                            </div>
                            <div class="form-field">
                                <label>Ссылка (необязательно — карточка станет кликабельной)</label>
                                <input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить пункт</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field">
                        <label>Заголовок</label>
                        <input type="text" name="items[__INDEX__][title]">
                    </div>
                    <div class="form-field">
                        <label>Текст</label>
                        <textarea name="items[__INDEX__][text]"></textarea>
                    </div>
                    <div class="form-field">
                        <label>Ссылка (необязательно — карточка станет кликабельной)</label>
                        <input type="text" name="items[__INDEX__][url]">
                    </div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить пункт</button>
                </template>
                <div class="repeater-actions">
                    <button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить пункт</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'slider'): ?>
            <div class="form-field">
                <label for="slider_ratio">Пропорция кадра</label>
                <select id="slider_ratio" name="ratio">
                    <?php foreach (['16-9' => '16:9 — широкий кадр', '4-3' => '4:3 — классический', '21-9' => '21:9 — панорама', 'auto' => 'Как у изображения'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['ratio'] ?? '16-9') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Общая пропорция удерживает высоту слайдера: при варианте «как у изображения» страница подпрыгивает на каждом переключении, если снимки разного размера.</span>
            </div>
            <div class="form-field">
                <label for="slider_autoplay">Автопрокрутка, секунд (0 — выключена)</label>
                <input type="number" id="slider_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
                <span class="form-hint">Прокрутка останавливается при наведении, фокусе внутри слайдера и у посетителей, которые просили меньше движения в системе.</span>
            </div>
            <div>
                <label>Слайды</label>
                <div data-repeater="slides">
                    <?php foreach (($data['slides'] ?? []) as $i => $slide): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::imageField('slides[' . $i . '][image]', (string) ($slide['image'] ?? ''), ['label' => 'Изображение слайда']) ?>
                            <div class="form-field">
                                <label>Alt-текст</label>
                                <input type="text" name="slides[<?= $i ?>][alt]" value="<?= htmlspecialchars($slide['alt'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="form-field">
                                <label>Подпись</label>
                                <input type="text" name="slides[<?= $i ?>][caption]" value="<?= htmlspecialchars($slide['caption'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="form-field">
                                <label>Ссылка со слайда (необязательно)</label>
                                <input type="text" name="slides[<?= $i ?>][url]" value="<?= htmlspecialchars($slide['url'] ?? '', ENT_QUOTES) ?>" placeholder="/projects/example">
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить слайд</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="slides">
                    <?= \App\Core\AdminUi::imageField('slides[__INDEX__][image]', '', ['label' => 'Изображение слайда']) ?>
                    <div class="form-field">
                        <label>Alt-текст</label>
                        <input type="text" name="slides[__INDEX__][alt]">
                    </div>
                    <div class="form-field">
                        <label>Подпись</label>
                        <input type="text" name="slides[__INDEX__][caption]">
                    </div>
                    <div class="form-field">
                        <label>Ссылка со слайда (необязательно)</label>
                        <input type="text" name="slides[__INDEX__][url]" placeholder="/projects/example">
                    </div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить слайд</button>
                </template>
                <div class="repeater-actions">
                    <button type="button" class="btn btn--small" data-repeater-add="slides"><?= \App\Core\AdminUi::icon('plus') ?>Добавить слайд</button>
                </div>
                <span class="form-hint">Изображения загружаются заранее в разделе «Файлы» (публичный доступ), ссылка копируется оттуда.</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'form'): ?>
            <div class="form-field">
                <label for="form_id">Форма обратной связи</label>
                <select id="form_id" name="form_id">
                    <option value="">— выберите форму —</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?= (int) $form['id'] ?>" <?= (int) ($data['form_id'] ?? 0) === (int) $form['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($form['name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($forms)): ?>
                    <span class="form-hint">Сначала создайте форму в разделе «Формы».</span>
                <?php endif; ?>
            </div>
            <div class="form-field">
                <label for="form_layout">Макет формы</label>
                <select id="form_layout" name="layout">
                    <option value="1col" <?= ($data['layout'] ?? '1col') === '1col' ? 'selected' : '' ?>>В одну колонку</option>
                    <option value="2col" <?= ($data['layout'] ?? '1col') === '2col' ? 'selected' : '' ?>>В две колонки (сетка)</option>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($type === 'columns'): ?>
            <div class="form-field">
                <label for="columns">Количество колонок</label>
                <select id="columns" name="columns">
                    <?php foreach ([2, 3, 4] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 2) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <?php $colRatio = (string) ($data['ratio'] ?? ''); ?>
                <label for="ratio">Ширина колонок</label>
                <select id="ratio" name="ratio">
                    <option value="">Одинаковые</option>
                    <?php foreach (\App\Core\ColumnRatio::OPTIONS as $ratioCols => $ratioList): ?>
                        <optgroup label="Для <?= (int) $ratioCols ?> колонок">
                            <?php foreach ($ratioList as $ratioValue): ?>
                                <option value="<?= htmlspecialchars($ratioValue, ENT_QUOTES) ?>" <?= $colRatio === $ratioValue ? 'selected' : '' ?>><?= htmlspecialchars(\App\Core\ColumnRatio::label($ratioValue), ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Доли ширины: «2 : 1» — первая колонка вдвое шире второй. Число долей должно совпадать с числом колонок, иначе колонки останутся равными. На телефоне колонки в любом случае идут одна под другой.</span>
            </div>
            <div class="form-field">
                <label for="gap">Промежуток между колонками</label>
                <select id="gap" name="gap">
                    <?php foreach (['small' => 'Малый', 'medium' => 'Средний', 'large' => 'Большой'] as $gv => $gl): ?>
                        <option value="<?= $gv ?>" <?= (string) ($data['gap'] ?? 'medium') === $gv ? 'selected' : '' ?>><?= $gl ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Наполнение колонок настраивается на странице: кнопка «+ блок» в каждой колонке.</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'tabs'): ?>
            <?php $tabsVariant = (string) ($data['variant'] ?? 'segmented'); ?>
            <div class="form-field">
                <label for="tabs_variant">Вариант отображения</label>
                <select id="tabs_variant" name="variant">
                    <?php foreach ([
                        'segmented' => 'Переключатель — вкладки в общей дорожке',
                        'underline' => 'Подчёркивание — активная вкладка с чертой',
                        'vertical' => 'Список слева, содержимое справа',
                    ] as $tv => $tl): ?>
                        <option value="<?= $tv ?>" <?= $tabsVariant === $tv ? 'selected' : '' ?>><?= $tl ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">На телефоне вертикальный вариант тоже показывает вкладки сверху — сбоку для них нет места.</span>
            </div>
            <div class="form-field">
                <label for="tabs_align">Положение полосы вкладок</label>
                <select id="tabs_align" name="align">
                    <?php foreach (['left' => 'Слева', 'center' => 'По центру', 'stretch' => 'На всю ширину'] as $av => $al): ?>
                        <option value="<?= $av ?>" <?= (string) ($data['align'] ?? 'left') === $av ? 'selected' : '' ?>><?= $al ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="tabs_description">Описание раздела</label>
                <textarea id="tabs_description" name="description" rows="3" data-wysiwyg><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div>
                <label>Вкладки</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Название вкладки</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon]", $item['icon'] ?? '', ['label' => 'Иконка (необязательно)']) ?>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить вкладку</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Название вкладки</label><input type="text" name="items[__INDEX__][title]"></div>
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon]', '', ['label' => 'Иконка (необязательно)']) ?>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить вкладку</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить вкладку</button></div>
                <span class="form-hint">Содержимое вкладок наполняется на странице: под блоком появится колонка на каждую вкладку с кнопкой «+ блок». Внутрь можно положить любой блок — текст, галерею, документы, форму. Вкладка без названия не выводится; порядок вкладок здесь задаёт и порядок колонок наполнения.</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'testimonials'): ?>
            <?php $tVariant = ($data['variant'] ?? 'carousel') === 'grid' ? 'grid' : 'carousel'; ?>
            <div class="form-field">
                <label for="t_variant">Вариант отображения</label>
                <select id="t_variant" name="variant">
                    <option value="carousel" <?= $tVariant === 'carousel' ? 'selected' : '' ?>>Карусель — отзывы едут полосой</option>
                    <option value="grid" <?= $tVariant === 'grid' ? 'selected' : '' ?>>Сетка — отзывы стоят рядами</option>
                </select>
            </div>
            <div class="form-field">
                <label for="t_description">Описание раздела</label>
                <textarea id="t_description" name="description" rows="2"><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field">
                <label for="t_columns">Колонок в сетке</label>
                <select id="t_columns" name="columns">
                    <?php foreach ([2, 3, 4] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 3) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Работает в варианте «Сетка». Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает.</span>
            </div>
            <div class="form-field">
                <label for="t_autoplay">Автопрокрутка, секунд (0 — выключена)</label>
                <input type="number" id="t_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
                <span class="form-hint">Работает в варианте «Карусель». Останавливается под курсором, при фокусе внутри и у посетителей, которые просили меньше движения.</span>
            </div>
            <div>
                <label>Отзывы</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Цитата</label><textarea name="items[<?= $i ?>][quote]"><?= htmlspecialchars($item['quote'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <div class="form-field"><label>Имя</label><input type="text" name="items[<?= $i ?>][name]" value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Должность</label><input type="text" name="items[<?= $i ?>][role]" value="<?= htmlspecialchars($item['role'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Компания</label><input type="text" name="items[<?= $i ?>][company]" value="<?= htmlspecialchars($item['company'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Оценка (0 — не показывать)</label><input type="number" name="items[<?= $i ?>][rating]" min="0" max="5" value="<?= (int) ($item['rating'] ?? 0) ?>"></div>
                            <?= \App\Core\AdminUi::imageField('items[' . $i . '][photo]', (string) ($item['photo'] ?? ''), ['label' => 'Фото']) ?>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить отзыв</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Цитата</label><textarea name="items[__INDEX__][quote]"></textarea></div>
                    <div class="form-field"><label>Имя</label><input type="text" name="items[__INDEX__][name]"></div>
                    <div class="form-field"><label>Должность</label><input type="text" name="items[__INDEX__][role]"></div>
                    <div class="form-field"><label>Компания</label><input type="text" name="items[__INDEX__][company]"></div>
                    <div class="form-field"><label>Оценка (0 — не показывать)</label><input type="number" name="items[__INDEX__][rating]" min="0" max="5" value="0"></div>
                    <?= \App\Core\AdminUi::imageField('items[__INDEX__][photo]', '', ['label' => 'Фото']) ?>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить отзыв</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить отзыв</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'counters'): ?>
            <div class="colorfield-row">
                <?= \App\Core\AdminUi::colorField('card_bg', $data['card_bg'] ?? '', 'Цвет карточки (фон)', '#ffffff', 'По умолчанию (белая)') ?>
                <?= \App\Core\AdminUi::colorField('text_color', $data['text_color'] ?? '', 'Цвет текста и цифр', '#173a63', 'По умолчанию (тёмно-синий)') ?>
            </div>
            <span class="form-hint u-inline-1e51bacc25">Оставьте «по умолчанию», чтобы карточка была белой с тёмным текстом. Для тёмной карточки выберите тёмный фон и светлый текст.</span>
            <div class="colorfield-row">
                <div class="form-field">
                    <label for="counter_icon_size">Размер иконок, px</label>
                    <input type="number" id="counter_icon_size" name="icon_size" min="16" max="64" step="1" value="<?= max(16, min(64, (int) ($data['icon_size'] ?? 28))) ?>">
                </div>
                <div class="form-field">
                    <label for="counter_icon_bg">Фон иконок</label>
                    <select id="counter_icon_bg" name="icon_bg">
                        <option value="on" <?= (string) ($data['icon_bg'] ?? 'on') === 'on' ? 'selected' : '' ?>>С подложкой</option>
                        <option value="off" <?= (string) ($data['icon_bg'] ?? 'on') === 'off' ? 'selected' : '' ?>>Без подложки</option>
                    </select>
                </div>
            </div>
            <div class="colorfield-row">
                <div class="form-field">
                    <label for="counter_icon_position">Положение иконки</label>
                    <select id="counter_icon_position" name="icon_position">
                        <?php foreach (['left' => 'Слева от текста', 'right' => 'Справа от текста', 'top' => 'Сверху', 'center' => 'Сверху по центру'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= (string) ($data['icon_position'] ?? 'left') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="counter_text_align">Выравнивание содержимого</label>
                    <select id="counter_text_align" name="text_align">
                        <?php foreach (['left' => 'Слева', 'center' => 'По центру', 'right' => 'Справа'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= (string) ($data['text_align'] ?? 'left') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <span class="form-hint">Размер, фон и расположение применяются только к иконкам этого блока; выравнивание — к числу и подписи.</span>
            <div class="form-grid form-grid--2">
                <div class="form-field">
                    <label for="counters_variant">Вид блока</label>
                    <select id="counters_variant" name="variant">
                        <?php foreach (['row' => 'Полоса с разделителями', 'cards' => 'Отдельные карточки'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= (string) ($data['variant'] ?? 'row') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="counters_value_size">Размер чисел</label>
                    <select id="counters_value_size" name="value_size">
                        <?php foreach (['normal' => 'Обычный', 'large' => 'Крупный'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= (string) ($data['value_size'] ?? 'normal') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label>Счётчики</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field"><label>Приставка (напр. более, до)</label><input type="text" name="items[<?= $i ?>][prefix]" maxlength="12" value="<?= htmlspecialchars($item['prefix'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Значение</label><input type="text" name="items[<?= $i ?>][value]" maxlength="24" value="<?= htmlspecialchars((string) ($item['value'] ?? ''), ENT_QUOTES) ?>" placeholder="34"><span class="form-hint">Можно «1 200», «24/7», «№1». Отсчёт при появлении работает только для чистого числа.</span></div>
                            <div class="form-field"><label>Суффикс (напр. + или %)</label><input type="text" name="items[<?= $i ?>][suffix]" value="<?= htmlspecialchars($item['suffix'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Подпись</label><input type="text" name="items[<?= $i ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Примечание</label><input type="text" name="items[<?= $i ?>][note]" maxlength="120" value="<?= htmlspecialchars($item['note'] ?? '', ENT_QUOTES) ?>" placeholder="по данным на 2026 год"></div>
                            <div class="form-field"><label>Ссылка</label><input type="text" name="items[<?= $i ?>][link]" value="<?= htmlspecialchars($item['link'] ?? '', ENT_QUOTES) ?>" placeholder="/page"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field"><label>Приставка (напр. более, до)</label><input type="text" name="items[__INDEX__][prefix]" maxlength="12"></div>
                    <div class="form-field"><label>Значение</label><input type="text" name="items[__INDEX__][value]" maxlength="24" placeholder="34"></div>
                    <div class="form-field"><label>Суффикс (напр. + или %)</label><input type="text" name="items[__INDEX__][suffix]"></div>
                    <div class="form-field"><label>Подпись</label><input type="text" name="items[__INDEX__][label]"></div>
                    <div class="form-field"><label>Примечание</label><input type="text" name="items[__INDEX__][note]" maxlength="120"></div>
                    <div class="form-field"><label>Ссылка</label><input type="text" name="items[__INDEX__][link]" placeholder="/page"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить счётчик</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'team_list' || $type === 'projects_list' || $type === 'news_latest'): ?>
            <div class="form-field">
                <label for="limit">Сколько записей показывать<?= $type === 'news_latest' ? ' (0 — 3 по умолчанию)' : ' (0 — все)' ?></label>
                <input type="number" id="limit" name="limit" min="0" value="<?= (int) ($data['limit'] ?? 0) ?>">
                <span class="form-hint">
                    <?php if ($type === 'news_latest'): ?>
                        Блок выводит последние опубликованные новости (лента для главной страницы).
                    <?php else: ?>
                        Блок выводит опубликованные записи раздела «<?= $type === 'team_list' ? 'Команда' : 'Проекты' ?>» по порядку сортировки.
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'projects_list' || $type === 'news_latest'): ?>
            <?php $allPlaceholder = $type === 'news_latest' ? 'Все новости' : 'Все проекты'; ?>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="<?= $allPlaceholder ?>"></div>
            <div class="form-field">
                <label for="all_url">Ссылка «Все …» — URL</label>
                <input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>">
                <span class="form-hint">Пусто — блок сам подставит адрес раздела<?= $type === 'news_latest' ? ' (с учётом выбранной рубрики)' : '' ?>. Ссылка показывается и без заголовка блока.</span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'projects_list'): ?>
            <?php $projVariant = (string) ($data['variant'] ?? 'grid'); ?>
            <div class="form-field">
                <label for="projects_variant">Вариант отображения</label>
                <select id="projects_variant" name="variant">
                    <?php foreach ([
                        'grid' => 'Сетка карточек',
                        'list' => 'Список — фото слева, текст справа',
                        'carousel' => 'Полоса с прокруткой',
                    ] as $pv => $pl): ?>
                        <option value="<?= $pv ?>" <?= $projVariant === $pv ? 'selected' : '' ?>><?= $pl ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">В списке помещается более длинный анонс, в полосе карточки листаются стрелками и свайпом.</span>
            </div>
            <div class="form-field">
                <label for="projects_description">Описание раздела</label>
                <input type="text" id="projects_description" name="description" value="<?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?>" placeholder="Одна строка под заголовком">
            </div>
            <div class="form-field">
                <label for="projects_autoplay">Автопрокрутка полосы, секунд</label>
                <input type="number" id="projects_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
                <span class="form-hint">0 — без автопрокрутки. Работает только у варианта «полоса»; движение замирает под курсором и у посетителей, просивших меньше анимации.</span>
            </div>
            <div class="form-field">
                <label for="projects_columns">Колонок в сетке</label>
                <select id="projects_columns" name="columns">
                    <?php foreach ([2, 3, 4] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 3) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает. На узких экранах колонок всегда меньше.</span>
            </div>
        <?php endif; ?>

        <?php if (in_array($type, ['news_latest', 'news_feature', 'news_docs'], true)): ?>
            <?php
            // Выборка по рубрике: один и тот же список для всех трёх блоков
            // новостей — иначе редактор искал бы поле в разных местах формы.
            $blockCategories = \App\Models\NewsCategory::all();
            $blockCategory = (int) ($data['category'] ?? 0);
            ?>
            <div class="form-field">
                <label for="news_block_category">Категория новостей</label>
                <select id="news_block_category" name="category">
                    <option value="0">Все категории</option>
                    <?php foreach ($blockCategories as $blockCat): ?>
                        <option value="<?= (int) $blockCat['id'] ?>" <?= $blockCategory === (int) $blockCat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $blockCat['name'], ENT_QUOTES) ?><?= (int) $blockCat['is_active'] === 1 ? '' : ' (скрыта)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">
                    Блок покажет новости только этой рубрики. «Все категории» — без ограничения.
                    Порядок вывода не меняется: сначала свежие. Названия рубрики и новостей берутся на языке страницы.
                </span>
            </div>
        <?php endif; ?>

        <?php if ($type === 'team_list'): ?>
            <?php $teamDepartments = $departments ?? []; ?>
            <div class="form-field">
                <label for="department">Показывать только сотрудников сектора</label>
                <select id="department" name="department">
                    <option value="">Все сотрудники</option>
                    <?php foreach ($teamDepartments as $dep): ?>
                        <option value="<?= htmlspecialchars((string) $dep['slug'], ENT_QUOTES) ?>" <?= ($data['department'] ?? '') === $dep['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $dep['name'], ENT_QUOTES) ?> (<?= (int) $dep['count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Сектор задаётся в карточке сотрудника (раздел «Команда»).</span>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="group_by_department" name="group_by_department" value="1" <?= !empty($data['group_by_department']) ? 'checked' : '' ?>>
                <label for="group_by_department">Группировать по секторам, а внутри — по отделам и группам</label>
            </div>
            <?php if ($teamDepartments !== []): ?>
                <p class="form-hint">
                    Якори для ссылок из схемы оргструктуры (работают при включённой группировке):
                    <?php foreach ($teamDepartments as $dep): ?>
                        <code>#team-<?= htmlspecialchars((string) $dep['slug'], ENT_QUOTES) ?></code>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($type === 'partners'): ?>
            <div class="form-field">
                <label for="p_description">Описание раздела</label>
                <textarea id="p_description" name="description" rows="2"><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все партнёры"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field">
                <label for="p_columns">Логотипов в ряду</label>
                <select id="p_columns" name="columns">
                    <?php foreach ([3, 4, 5, 6, 7, 8] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 6) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Если логотипов больше, ряд превращается в прокручиваемую полосу. На узких экранах число подбирается автоматически.</span>
            </div>
            <div class="form-field">
                <label for="p_logo_size">Высота логотипа</label>
                <select id="p_logo_size" name="logo_size">
                    <?php foreach (['small' => 'Мелкие', 'medium' => 'Средние', 'large' => 'Крупные'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['logo_size'] ?? 'medium') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Общая высота выравнивает ряд: логотипы у партнёров разных пропорций.</span>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="p_grayscale" name="grayscale" value="1" <?= (!array_key_exists('grayscale', $data) || !empty($data['grayscale'])) ? 'checked' : '' ?>>
                <label for="p_grayscale">Обесцвечивать логотипы, возвращать цвет при наведении</label>
            </div>
            <div class="form-field">
                <label for="p_autoplay">Автопрокрутка, секунд (0 — выключена)</label>
                <input type="number" id="p_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
            </div>
            <div>
                <label>Логотипы партнёров</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::imageField('items[' . $i . '][logo]', (string) ($item['logo'] ?? ''), ['label' => 'Логотип']) ?>
                            <div class="form-field"><label>Название</label><input type="text" name="items[<?= $i ?>][name]" value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Ссылка (необязательно)</label><input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>" placeholder="https://..."></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::imageField('items[__INDEX__][logo]', '', ['label' => 'Логотип']) ?>
                    <div class="form-field"><label>Название</label><input type="text" name="items[__INDEX__][name]"></div>
                    <div class="form-field"><label>Ссылка (необязательно)</label><input type="text" name="items[__INDEX__][url]" placeholder="https://..."></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить логотип</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'subscribe'): ?>
            <div class="form-field">
                <label for="sub_variant">Вариант оформления</label>
                <select id="sub_variant" name="variant">
                    <?php foreach (['band' => 'Полоса во всю ширину', 'card' => 'Карточка по центру', 'image' => 'На фоне изображения'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['variant'] ?? 'band') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Без загруженного изображения вариант «На фоне» показывается как обычная полоса.</span>
            </div>
            <?= \App\Core\AdminUi::imageField('image', (string) ($data['image'] ?? ''), ['label' => 'Фоновое изображение']) ?>
            <div class="form-field">
                <label for="sub_text">Текст под заголовком</label>
                <input type="text" id="sub_text" name="text" value="<?= htmlspecialchars($data['text'] ?? '', ENT_QUOTES) ?>">
            </div>
            <div class="form-field">
                <label for="sub_placeholder">Подсказка в поле e-mail</label>
                <input type="text" id="sub_placeholder" name="placeholder" value="<?= htmlspecialchars($data['placeholder'] ?? '', ENT_QUOTES) ?>" placeholder="Ваш e-mail">
            </div>
            <div class="form-field">
                <label for="sub_btn">Текст кнопки</label>
                <input type="text" id="sub_btn" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>" placeholder="Подписаться">
            </div>
            <div class="form-field">
                <label for="sub_note">Примечание под формой</label>
                <input type="text" id="sub_note" name="note" value="<?= htmlspecialchars($data['note'] ?? '', ENT_QUOTES) ?>" placeholder="Отписаться можно в один клик">
            </div>
            <p class="form-hint">Адреса попадают в раздел «Подписчики»; рассылку раз в неделю отправляет digest_worker (cron).</p>
        <?php endif; ?>

        <?php if ($type === 'faq'): ?>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="search_enabled" name="search_enabled" value="1" <?= (!array_key_exists('search_enabled', $data) || !empty($data['search_enabled'])) ? 'checked' : '' ?>>
                <label for="search_enabled">Показывать поиск, если вопросов четыре или больше</label>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="single_open" name="single_open" value="1" <?= !empty($data['single_open']) ? 'checked' : '' ?>>
                <label for="single_open">Одновременно открывать только один ответ</label>
            </div>
            <div>
                <label>Вопросы и ответы (аккордеон)</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Категория (необязательно)</label><input type="text" name="items[<?= $i ?>][category]" value="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Вопрос</label><input type="text" name="items[<?= $i ?>][question]" value="<?= htmlspecialchars($item['question'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Ответ</label><textarea name="items[<?= $i ?>][answer]" data-wysiwyg><?= htmlspecialchars($item['answer'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить вопрос</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Категория (необязательно)</label><input type="text" name="items[__INDEX__][category]"></div>
                    <div class="form-field"><label>Вопрос</label><input type="text" name="items[__INDEX__][question]"></div>
                    <div class="form-field"><label>Ответ</label><textarea name="items[__INDEX__][answer]"></textarea></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить вопрос</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить вопрос</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'contact_cards'): ?>
            <div class="form-field">
                <label for="cc_variant">Вариант отображения</label>
                <select id="cc_variant" name="variant">
                    <option value="cards" <?= ($data['variant'] ?? 'cards') === 'cards' ? 'selected' : '' ?>>Иконка над заголовком</option>
                    <option value="inline" <?= ($data['variant'] ?? 'cards') === 'inline' ? 'selected' : '' ?>>Иконка и заголовок в одну строку</option>
                </select>
                <span class="form-hint">Во втором варианте карточка ниже — в ряд их влезает больше.</span>
            </div>
            <div class="form-field">
                <label for="cc_icon_size">Размер иконки, px</label>
                <input type="number" id="cc_icon_size" name="icon_size" min="16" max="64" value="<?= (int) ($data['icon_size'] ?? 22) ?>">
            </div>
            <div class="form-field">
                <label for="cc_icon_bg">Подложка под иконкой</label>
                <select id="cc_icon_bg" name="icon_bg">
                    <option value="on" <?= ($data['icon_bg'] ?? 'on') !== 'off' ? 'selected' : '' ?>>Есть — иконка в плитке</option>
                    <option value="off" <?= ($data['icon_bg'] ?? 'on') === 'off' ? 'selected' : '' ?>>Нет — только иконка</option>
                </select>
                <span class="form-hint">Без подложки иконка стоит на фоне карточки. Размер плитки подстраивается под размер иконки.</span>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="cc_line_icons" name="line_icons" value="1" <?= (!array_key_exists('line_icons', $data) || !empty($data['line_icons'])) ? 'checked' : '' ?>>
                <label for="cc_line_icons">Мини-иконки у строк контактов</label>
                <span class="form-hint">Подбираются по содержимому строки: трубка к номеру, конверт к почте, часы к режиму работы. Строка без узнаваемого содержимого остаётся без значка.</span>
            </div>
            <div>
                <label>Контактные карточки (адрес, телефон, e-mail, часы работы…)</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <?= \App\Core\AdminUi::imageField("items[{$i}][icon_image]", (string) ($item['icon_image'] ?? ''), ['label' => 'Своя иконка (картинка)', 'hint' => 'Заполнено — используется вместо иконки Tabler.']) ?>
                            <div class="form-field"><label>Заголовок</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>" placeholder="напр. Телефон"></div>
                            <div class="form-field"><label>Строки (по одной на строку)</label><textarea name="items[<?= $i ?>][lines]" placeholder="+998 71 000-00-00&#10;info@example.uz"><?= htmlspecialchars($item['lines'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <div class="form-field"><label>Ссылка (URL)</label><input type="text" name="items[<?= $i ?>][link_url]" value="<?= htmlspecialchars($item['link_url'] ?? '', ENT_QUOTES) ?>" placeholder="tel:+998710000000 / mailto: / https://"></div>
                            <div class="form-field"><label>Текст ссылки</label><input type="text" name="items[<?= $i ?>][link_text]" value="<?= htmlspecialchars($item['link_text'] ?? '', ENT_QUOTES) ?>" placeholder="напр. Позвонить"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить карточку</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <?= \App\Core\AdminUi::imageField('items[__INDEX__][icon_image]', '', ['label' => 'Своя иконка (картинка)', 'hint' => 'Заполнено — используется вместо иконки Tabler.']) ?>
                    <div class="form-field"><label>Заголовок</label><input type="text" name="items[__INDEX__][title]" placeholder="напр. Телефон"></div>
                    <div class="form-field"><label>Строки (по одной на строку)</label><textarea name="items[__INDEX__][lines]"></textarea></div>
                    <div class="form-field"><label>Ссылка (URL)</label><input type="text" name="items[__INDEX__][link_url]"></div>
                    <div class="form-field"><label>Текст ссылки</label><input type="text" name="items[__INDEX__][link_text]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить карточку</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить карточку</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'hero'): ?>
            <?php
            // Обложка как тип контента. Выбрана — блок только размещает её, а
            // поля ниже не участвуют в выводе: держать один и тот же текст в
            // двух местах нельзя, они разойдутся. Поля при этом сохраняются,
            // чтобы возврат к «своим настройкам» ничего не терял.
            $heroSelected = (int) ($data['hero_id'] ?? 0);
            $heroList = [];
            try {
                $heroList = \App\Models\Hero::all();
                $heroSlideCounts = \App\Models\Hero::slideCounts();
            } catch (\Throwable $e) {
                $heroSlideCounts = []; // миграция обложек не накатана
            }
            ?>
            <div class="form-field">
                <label for="hero_id">Обложка</label>
                <select id="hero_id" name="hero_id" data-hero-picker aria-describedby="hero_id_hint">
                    <option value="0" <?= $heroSelected === 0 ? 'selected' : '' ?>>— собрать прямо в блоке (старый способ) —</option>
                    <?php foreach ($heroList as $heroRow): ?>
                        <?php $rowId = (int) $heroRow['id']; ?>
                        <option value="<?= $rowId ?>" <?= $heroSelected === $rowId ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $heroRow['name'], ENT_QUOTES) ?>
                            (<?= (int) ($heroSlideCounts[$rowId]['active'] ?? 0) ?> сл.<?= (string) $heroRow['status'] !== 'published' ? ', не опубликована' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint" id="hero_id_hint">
                    Обложки создаются в разделе <a href="/admin/heroes">«Обложки»</a>: одну обложку можно вывести на нескольких
                    страницах и править в одном месте. Когда обложка выбрана, поля ниже не выводятся —
                    содержимое и оформление берутся из неё.
                </span>
            </div>
            <?php if ($heroSelected > 0): ?>
                <p class="form-hint">
                    <a class="btn btn--small" href="/admin/heroes/<?= $heroSelected ?>/edit">Открыть обложку и её слайды →</a>
                </p>
            <?php endif; ?>
            <?php // Собственные поля блока. При выбранной обложке они не участвуют
                  // в выводе — прячем их, но оставляем в форме: возврат к
                  // «старому способу» не должен терять набранное. ?>
            <div data-hero-own-fields<?= $heroSelected > 0 ? ' hidden' : '' ?>>
            <?= \App\Core\AdminUi::imageField('image_mobile', (string) ($data['image_mobile'] ?? ''), [
                'label' => 'Кадр для телефона',
                'hint' => 'Пусто — на телефоне показывается общий кадр. Пригодится, когда широкое фото на узком экране режется до полоски.',
            ]) ?>
            <div class="form-field">
                <label for="hero_video_mobile">Фоновое видео на телефоне</label>
                <select id="hero_video_mobile" name="video_mobile">
                    <option value="poster" <?= (string) ($data['video_mobile'] ?? 'poster') !== 'play' ? 'selected' : '' ?>>Показывать постер (экономит трафик)</option>
                    <option value="play" <?= (string) ($data['video_mobile'] ?? 'poster') === 'play' ? 'selected' : '' ?>>Проигрывать видео</option>
                </select>
                <span class="form-hint">Постер берётся из поля «Изображение» (а если задан кадр для телефона — из него).</span>
            </div>
            <?php
            $heroMobileHeight = (string) ($data['height_mobile'] ?? '');
            $heroMobileCustom = (string) ($data['custom_height_mobile'] ?? '');
            preg_match('/^(\d+(?:\.\d+)?)(px|vh|dvh|rem)$/', $heroMobileCustom, $heroMobileParts);
            ?>
            <div class="form-field">
                <label for="hero_height_mobile">Высота на телефоне</label>
                <select id="hero_height_mobile" name="hero_height_mobile">
                    <?php foreach (['' => 'Как на десктопе', 'regular' => 'Обычная', 'full' => 'На весь экран', 'custom' => 'Своя'] as $mh => $ml): ?>
                        <option value="<?= $mh ?>" <?= $heroMobileHeight === $mh ? 'selected' : '' ?>><?= $ml ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="hero_height_mobile_value">Своя высота на телефоне</label>
                <div class="image-field__controls">
                    <input type="number" id="hero_height_mobile_value" name="hero_height_mobile_value" min="20" max="2000" step="1" value="<?= htmlspecialchars($heroMobileParts[1] ?? '420', ENT_QUOTES) ?>">
                    <select name="hero_height_mobile_unit" aria-label="Единица измерения">
                        <?php foreach (['px', 'vh', 'dvh', 'rem'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= ($heroMobileParts[2] ?? 'px') === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="form-hint">Действует, когда выбрана «Своя» высота на телефоне.</span>
            </div>
            <div class="form-field">
                <label for="hero_text_align_y">Текст по вертикали</label>
                <select id="hero_text_align_y" name="text_align_y">
                    <?php foreach (['top' => 'Сверху', 'center' => 'По центру', 'bottom' => 'Снизу'] as $ay => $al): ?>
                        <option value="<?= $ay ?>" <?= (string) ($data['text_align_y'] ?? 'center') === $ay ? 'selected' : '' ?>><?= $al ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Заметно на высокой обложке: текст можно увести вниз, чтобы не закрывать лицо на фото.</span>
            </div>
            <?= \App\Core\AdminUi::imageField('art_image', (string) ($data['art_image'] ?? ''), [
                'label' => 'Картинка поверх фона (PNG/SVG)',
                'hint' => 'Эмблема, логотип программы или иллюстрация. Показывается вместе с текстом, а не вместо фона. К слайд-шоу не применяется — там у каждого слайда своё медиа.',
            ]) ?>
            <div class="form-field">
                <label for="hero_art_alt">Описание картинки</label>
                <input type="text" id="hero_art_alt" name="art_alt" value="<?= htmlspecialchars($data['art_alt'] ?? '', ENT_QUOTES) ?>" placeholder="например: Логотип программы «Цифровой Узбекистан»">
                <span class="form-hint">Пусто — картинка считается украшением и скрыта от скринридера. Если на ней есть текст или смысл (логотип, эмблема программы), опишите её словами.</span>
            </div>
            <div class="form-field">
                <label for="hero_art_position">Где показывать картинку</label>
                <select id="hero_art_position" name="art_position">
                    <?php foreach (['above' => 'Над текстом', 'left' => 'Слева от текста', 'right' => 'Справа от текста'] as $ap => $al): ?>
                        <option value="<?= $ap ?>" <?= (string) ($data['art_position'] ?? 'above') === $ap ? 'selected' : '' ?>><?= $al ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">На телефоне картинка в любом случае встаёт над текстом — сбоку ей не хватает ширины.</span>
            </div>
            <div class="form-field">
                <label for="hero_art_size">Размер картинки</label>
                <select id="hero_art_size" name="art_size">
                    <?php foreach (['small' => 'Небольшая (до 64px)', 'medium' => 'Средняя (до 120px)', 'large' => 'Крупная (до 200px)'] as $az => $al): ?>
                        <option value="<?= $az ?>" <?= (string) ($data['art_size'] ?? 'medium') === $az ? 'selected' : '' ?>><?= $al ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field"><label for="hero_width">Ширина секции</label>
                <select id="hero_width" name="hero_width">
                    <option value="full" <?= ($data['width'] ?? 'full') === 'full' ? 'selected' : '' ?>>Во всю ширину экрана</option>
                    <option value="standard" <?= ($data['width'] ?? '') === 'standard' ? 'selected' : '' ?>>Стандартная (по контейнеру)</option>
                </select>
            </div>
            <?php
            $heroHeightMode = (string) ($data['height'] ?? 'regular');
            $heroHeightMode = in_array($heroHeightMode, ['regular', 'full', 'custom'], true) ? $heroHeightMode : 'regular';
            $heroCustomHeight = (string) ($data['custom_height'] ?? '720px');
            preg_match('/^(\d+(?:\.\d+)?)(px|vh|dvh|rem)$/', $heroCustomHeight, $heroHeightParts);
            $heroHeightValue = $heroHeightParts[1] ?? '720';
            $heroHeightUnit = $heroHeightParts[2] ?? 'px';
            ?>
            <div class="form-field"><label for="hero_height">Высота секции</label>
                <select id="hero_height" name="hero_height" data-hero-height>
                    <option value="regular" <?= ($data['height'] ?? 'regular') === 'regular' ? 'selected' : '' ?>>Обычная</option>
                    <option value="full" <?= ($data['height'] ?? '') === 'full' ? 'selected' : '' ?>>Полноэкранная (100vh)</option>
                    <option value="custom" <?= $heroHeightMode === 'custom' ? 'selected' : '' ?>>Своя высота</option>
                </select>
            </div>
            <div class="form-field" data-hero-custom-height<?= $heroHeightMode !== 'custom' ? ' hidden' : '' ?>>
                <label for="hero_height_value">Своя высота секции</label>
                <div class="u-inline-6c78ba1694">
                    <input type="number" id="hero_height_value" name="hero_height_value" min="10" max="2000" step="0.1" value="<?= htmlspecialchars($heroHeightValue, ENT_QUOTES) ?>">
                    <select id="hero_height_unit" name="hero_height_unit" aria-label="Единица высоты">
                        <?php foreach (['px' => 'px', 'vh' => 'vh', 'dvh' => 'dvh', 'rem' => 'rem'] as $unit => $label): ?>
                            <option value="<?= $unit ?>" <?= $heroHeightUnit === $unit ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="form-hint">Допустимо: 160–2000 px, 20–150 vh/dvh или 10–120 rem. Используется минимальная высота, поэтому содержимое не обрезается.</span>
            </div>
            <div class="form-field">
                <label for="eyebrow">Надзаголовок (мелкий текст над заголовком)</label>
                <input type="text" id="eyebrow" name="eyebrow" value="<?= htmlspecialchars($data['eyebrow'] ?? '', ENT_QUOTES) ?>" placeholder="СТРАТЕГИЯ. РЕФОРМЫ. РАЗВИТИЕ.">
            </div>
            <div class="form-field">
                <label for="subtitle">Подзаголовок</label>
                <textarea id="subtitle" name="subtitle" rows="2"><?= htmlspecialchars($data['subtitle'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field"><label for="bg_type">Фон секции</label>
                <select id="bg_type" name="bg_type" data-hero-bg>
                    <?php
                    $bt = (string) ($data['bg_type'] ?? 'none');
                    $bt = in_array($bt, ['none', 'image', 'video', 'youtube'], true) ? $bt : 'none';
                    ?>
                    <option value="none" <?= $bt === 'none' ? 'selected' : '' ?>>Без фона (светлая секция)</option>
                    <option value="image" <?= $bt === 'image' ? 'selected' : '' ?>>Фото</option>
                    <option value="video" <?= $bt === 'video' ? 'selected' : '' ?>>Видео из медиа (mp4)</option>
                    <option value="youtube" <?= $bt === 'youtube' ? 'selected' : '' ?>>Видео с YouTube</option>
                </select>
                <span class="form-hint">Выберите источник фона. Поля ниже подстраиваются под выбор. Если выбрать фото при значении «Без фона», тип переключится на «Фото» автоматически — иначе снимок сохранился бы, но не показывался.</span>
            </div>
            <?= \App\Core\AdminUi::imageField('image', $data['image'] ?? '', ['label' => 'Фото фона (и постер для видео)', 'hint' => 'Показывается как фон, а для видео — как заставка до загрузки.']) ?>
            <?= \App\Core\AdminUi::mediaPositionFields($data['image_position'] ?? 'center-center', $data['image_position_mobile'] ?? 'center-center') ?>
            <div class="form-field">
                <label for="video_url">Видео-фон из медиа (mp4)</label>
                <div class="image-field__controls">
                    <input type="text" id="video_url" name="video_url" value="<?= htmlspecialchars($data['video_url'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/public/hero.mp4">
                    <button type="button" class="btn btn--small" data-media-pick data-media-target="#video_url" data-media-type="video">Медиабиблиотека</button>
                </div>
                <span class="form-hint">Выберите mp4 из медиабиблиотеки или вставьте ссылку. Видео зациклено, без звука.</span>
            </div>
            <div class="form-field">
                <label for="youtube_url">Ссылка на YouTube</label>
                <input type="text" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($data['youtube_url'] ?? '', ENT_QUOTES) ?>" placeholder="https://www.youtube.com/watch?v=…">
                <span class="form-hint">После вставки корректной ссылки фон автоматически переключается на YouTube. Ролик проигрывается без звука и зациклено.</span>
            </div>
            <?php
            $overlayEnabled = !empty($data['overlay_enabled']);
            $rawOverlayDirection = (string) ($data['overlay_direction'] ?? 'auto');
            $overlayMode = (string) ($data['overlay_mode'] ?? 'gradient');
            $overlayMode = in_array($overlayMode, ['solid', 'gradient'], true) ? $overlayMode : 'gradient';
            $overlayDirection = $rawOverlayDirection;
            $overlayDirection = in_array($overlayDirection, ['auto', 'to_right', 'to_left', 'to_bottom', 'to_top', 'to_bottom_right', 'to_bottom_left', 'to_top_right', 'to_top_left'], true)
                ? $overlayDirection
                : 'auto';
            ?>
            <div class="form-field">
                <label class="hb-switch">
                    <input type="checkbox" name="overlay_enabled" value="1"
                           data-hero-visual-toggle="hero_overlay_settings"
                           aria-controls="hero_overlay_settings"
                           aria-expanded="<?= $overlayEnabled ? 'true' : 'false' ?>"
                           <?= $overlayEnabled ? 'checked' : '' ?>>
                    <span class="hb-switch__track"></span>
                    Затемнение изображения
                </label>
                <span class="form-hint">По умолчанию выключено: фото и видео показываются без наложения. Включайте только когда текст теряется на светлом или пёстром фоне.</span>
            </div>
            <div id="hero_overlay_settings" class="hero-visual-settings" data-hero-visual-panel<?= $overlayEnabled ? '' : ' hidden' ?>>
            <fieldset class="hero-overlay-mode">
                <legend>Режим затемнения</legend>
                <div class="hero-overlay-mode__options">
                    <label class="hero-overlay-mode__option">
                        <input type="radio" name="overlay_mode" value="solid" data-hero-overlay-mode
                               <?= $overlayMode === 'solid' ? 'checked' : '' ?>>
                        <span><strong>Равномерное</strong><small>Одинаковая плотность по всей фотографии или видео.</small></span>
                    </label>
                    <label class="hero-overlay-mode__option">
                        <input type="radio" name="overlay_mode" value="gradient" data-hero-overlay-mode
                               <?= $overlayMode === 'gradient' ? 'checked' : '' ?>>
                        <span><strong>Градиентное от края</strong><small>Начинается у края Hero и плавно исчезает к центру блока.</small></span>
                    </label>
                </div>
            </fieldset>
            <div class="form-field"><label for="overlay_color">Цвет затемнения</label>
                <input type="color" id="overlay_color" name="overlay_color" value="<?= htmlspecialchars($data['overlay_color'] ?? '#0b1a30', ENT_QUOTES) ?>">
            </div>
            <div data-hero-overlay-gradient<?= $overlayMode === 'gradient' ? '' : ' hidden' ?>>
            <div class="form-field"><label for="overlay_direction">Край, от которого начинается затемнение</label>
                <select id="overlay_direction" name="overlay_direction">
                    <option value="auto" <?= $overlayDirection === 'auto' ? 'selected' : '' ?>>Авто — ближайший к тексту край</option>
                    <option value="to_right" <?= $overlayDirection === 'to_right' ? 'selected' : '' ?>>От левого края →</option>
                    <option value="to_left" <?= $overlayDirection === 'to_left' ? 'selected' : '' ?>>От правого края ←</option>
                    <option value="to_bottom" <?= $overlayDirection === 'to_bottom' ? 'selected' : '' ?>>От верхнего края ↓</option>
                    <option value="to_top" <?= $overlayDirection === 'to_top' ? 'selected' : '' ?>>От нижнего края ↑</option>
                    <option value="to_bottom_right" <?= $overlayDirection === 'to_bottom_right' ? 'selected' : '' ?>>От левого верхнего угла ↘</option>
                    <option value="to_bottom_left" <?= $overlayDirection === 'to_bottom_left' ? 'selected' : '' ?>>От правого верхнего угла ↙</option>
                    <option value="to_top_right" <?= $overlayDirection === 'to_top_right' ? 'selected' : '' ?>>От левого нижнего угла ↗</option>
                    <option value="to_top_left" <?= $overlayDirection === 'to_top_left' ? 'selected' : '' ?>>От правого нижнего угла ↖</option>
                </select>
                <span class="form-hint">Затемнение имеет максимальную плотность непосредственно у выбранного края и полностью исчезает примерно к двум третям ширины или высоты Hero. Для текста по центру режим «Авто» использует нижний край.</span>
            </div>
            </div>
            <div class="form-field"><label for="overlay_opacity">Плотность затемнения: <output data-range-output="overlay_opacity"><?= (int) ($data['overlay_opacity'] ?? 35) ?></output>%</label>
                <input type="range" min="0" max="100" id="overlay_opacity" name="overlay_opacity" value="<?= (int) ($data['overlay_opacity'] ?? 35) ?>" data-range-input="overlay_opacity">
                <span class="form-hint">Указанное значение теперь является реальной максимальной плотностью без скрытого усиления.</span>
            </div>
            </div>
            <div class="colorfield-row">
                <?= \App\Core\AdminUi::colorField('bg_color', $data['bg_color'] ?? '', 'Фон Hero без фото/видео', '#0b1a30', 'Нет (по теме)') ?>
            </div>
            <span class="form-hint u-inline-1e51bacc25">Используется только как самостоятельный фон Hero без медиа. Это не наложение поверх фотографии или видео.</span>
            <div class="form-field"><label for="text_position">Положение текста</label>
                <select id="text_position" name="text_position">
                    <?php $tp = $data['text_position'] ?? 'left'; ?>
                    <option value="left" <?= $tp === 'left' ? 'selected' : '' ?>>Слева</option>
                    <option value="center" <?= $tp === 'center' ? 'selected' : '' ?>>По центру</option>
                    <option value="right" <?= $tp === 'right' ? 'selected' : '' ?>>Справа</option>
                </select>
            </div>
            <?php
            preg_match('/^(\d+(?:\.\d+)?)(px|%|vw)$/', (string) ($data['text_width'] ?? ''), $twParts);
            $twValue = $twParts[1] ?? '';
            $twUnit = $twParts[2] ?? 'px';
            ?>
            <div class="form-field">
                <label for="text_width_value">Ширина текстовой колонки</label>
                <div class="u-inline-6c78ba1694">
                    <input type="number" id="text_width_value" name="text_width_value" min="10" max="2000" step="0.1" value="<?= htmlspecialchars($twValue, ENT_QUOTES) ?>" placeholder="по теме (620)">
                    <select id="text_width_unit" name="text_width_unit" aria-label="Единица ширины">
                        <?php foreach (['px' => 'px', '%' => '%', 'vw' => 'vw'] as $unit => $label): ?>
                            <option value="<?= $unit ?>" <?= $twUnit === $unit ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="form-hint">Максимальная ширина блока с заголовком и текстом: 200–2000 px или 10–100 %/vw (например, 50 vw — половина экрана). Пусто — ширина темы. На телефонах ограничение не применяется.</span>
            </div>
            <div class="colorfield-row">
                <?= \App\Core\AdminUi::colorField('text_color', $data['text_color'] ?? '', 'Цвет текста', '#ffffff', 'Авто (белый на фото, тёмный без фона)') ?>
                <?= \App\Core\AdminUi::colorField('button_color', $data['button_color'] ?? '', 'Цвет фона основной кнопки', '#173a63', 'По умолчанию') ?>
            </div>
            <span class="form-hint u-inline-1e51bacc25">Применяется к первой кнопке Hero. Вторая кнопка остаётся прозрачной и контурной.</span>
            <?php $panelEnabled = !empty($data['panel_enabled']); ?>
            <div class="form-field">
                <label class="hb-switch">
                    <input type="checkbox" name="panel_enabled" value="1"
                           data-hero-visual-toggle="hero_panel_settings"
                           aria-controls="hero_panel_settings"
                           aria-expanded="<?= $panelEnabled ? 'true' : 'false' ?>"
                           <?= $panelEnabled ? 'checked' : '' ?>>
                    <span class="hb-switch__track"></span>
                    Подложка под текстом
                </label>
                <span class="form-hint">Цветная полупрозрачная плашка под заголовком — для читаемости на пёстром фоне. Если делаете светлую подложку — задайте тёмный цвет текста выше.</span>
            </div>
            <div id="hero_panel_settings" class="hero-visual-settings" data-hero-visual-panel<?= $panelEnabled ? '' : ' hidden' ?>>
            <div class="form-field"><label for="panel_color">Цвет подложки</label>
                <input type="color" id="panel_color" name="panel_color" value="<?= htmlspecialchars($data['panel_color'] ?? '#0b1a30', ENT_QUOTES) ?>">
            </div>
            <div class="form-field"><label for="panel_opacity">Прозрачность подложки: <output data-range-output="panel_opacity"><?= (int) ($data['panel_opacity'] ?? 40) ?></output>%</label>
                <input type="range" min="0" max="100" id="panel_opacity" name="panel_opacity" value="<?= (int) ($data['panel_opacity'] ?? 40) ?>" data-range-input="panel_opacity">
            </div>
            </div>
            <div class="form-field"><label for="button_text">Кнопка 1 — текст</label><input type="text" id="button_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="button_url">Кнопка 1 — ссылка</label><input type="text" id="button_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>" placeholder="/o-nas"></div>
            <?= \App\Core\AdminUi::iconField('button_icon', $data['button_icon'] ?? '', [
                'id' => 'button_icon',
                'label' => 'Кнопка 1 — иконка из библиотеки',
                'hint' => 'Необязательно. Своя иконка ниже важнее выбранной здесь.',
            ]) ?>
            <?= \App\Core\AdminUi::imageField('button_icon_image', $data['button_icon_image'] ?? '', [
                'label' => 'Кнопка 1 — своя иконка (SVG или картинка)',
                'hint' => 'Загруженный SVG очищается от скриптов. Показывается вместо иконки из библиотеки.',
            ]) ?>
            <div class="form-field"><label for="button2_text">Кнопка 2 — текст (контурная)</label><input type="text" id="button2_text" name="button2_text" value="<?= htmlspecialchars($data['button2_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="button2_url">Кнопка 2 — ссылка</label><input type="text" id="button2_url" name="button2_url" value="<?= htmlspecialchars($data['button2_url'] ?? '', ENT_QUOTES) ?>"></div>
            <?= \App\Core\AdminUi::iconField('button2_icon', $data['button2_icon'] ?? '', [
                'id' => 'button2_icon',
                'label' => 'Кнопка 2 — иконка из библиотеки',
                'hint' => 'Необязательно. Своя иконка ниже важнее выбранной здесь.',
            ]) ?>
            <?= \App\Core\AdminUi::imageField('button2_icon_image', $data['button2_icon_image'] ?? '', [
                'label' => 'Кнопка 2 — своя иконка (SVG или картинка)',
                'hint' => 'Загруженный SVG очищается от скриптов. Показывается вместо иконки из библиотеки.',
            ]) ?>
            <div class="form-field"><label for="video_button_text">Кнопка «Смотреть видео» — текст</label><input type="text" id="video_button_text" name="video_button_text" value="<?= htmlspecialchars($data['video_button_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="video_button_url">Кнопка «Смотреть видео» — ссылка</label><input type="text" id="video_button_url" name="video_button_url" value="<?= htmlspecialchars($data['video_button_url'] ?? '', ENT_QUOTES) ?>"></div>

            <?php
            $heroSlides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
            $slideField = static function (int $index, string $key, string $label, string $value, string $type = 'text', string $hint = ''): string {
                $id = 'slide_' . $index . '_' . $key;
                return '<div class="form-field"><label for="' . $id . '">' . htmlspecialchars($label, ENT_QUOTES) . '</label>'
                    . '<input type="' . $type . '" id="' . $id . '" name="slides[' . $index . '][' . $key . ']" value="' . htmlspecialchars($value, ENT_QUOTES) . '">'
                    . ($hint !== '' ? '<span class="form-hint">' . htmlspecialchars($hint, ENT_QUOTES) . '</span>' : '')
                    . '</div>';
            };
            ?>
            <h3 class="form-subtitle">Слайды</h3>
            <p class="form-hint u-inline-291b7bbb01">
                Заполните слайды, чтобы обложка стала слайдером: до <?= \App\Core\BlockData\HeroBlockNormalizer::MAX_SLIDES ?> штук.
                Высота и цвета остаются общими; затемнение, подложку и картинку поверх фона слайд может
                переопределить — по умолчанию берёт их у обложки. Своё у слайда — текст, фон, кнопки,
                ссылка и срок показа. Пока слайдов нет, обложка работает как раньше.
            </p>
            <div class="form-field">
                <label for="hero_autoplay">Автопрокрутка, секунд</label>
                <input type="number" id="hero_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
                <span class="form-hint">0 — переключать только вручную. Прокрутка останавливается под курсором, при фокусе с клавиатуры и у посетителей, попросивших меньше движения.</span>
            </div>
            <div data-repeater="heroslides" data-repeater-max="<?= \App\Core\BlockData\HeroBlockNormalizer::MAX_SLIDES ?>" class="fb-grid">
                <?php foreach ($heroSlides as $i => $slide): ?>
                    <div class="repeater-row fb-card">
                        <div class="fb-card__head">
                            <span class="fb-card__badge">Слайд</span>
                            <span class="fb-card__tools">
                                <button type="button" class="fb-move" data-fb-move="up" aria-label="Выше" title="Переместить">↑</button>
                                <button type="button" class="fb-move" data-fb-move="down" aria-label="Ниже" title="Переместить">↓</button>
                            </span>
                        </div>
                        <?= $slideField($i, 'eyebrow', 'Надзаголовок', (string) ($slide['eyebrow'] ?? '')) ?>
                        <?= $slideField($i, 'title', 'Заголовок', (string) ($slide['title'] ?? '')) ?>
                        <?= $slideField($i, 'subtitle', 'Подзаголовок', (string) ($slide['subtitle'] ?? '')) ?>
                        <?= \App\Core\AdminUi::imageField('slides[' . $i . '][image]', (string) ($slide['image'] ?? ''), ['label' => 'Изображение слайда', 'hint' => 'Фон слайда, а для видео — заставка до загрузки.']) ?>
                        <div class="form-field">
                            <label for="slide_<?= $i ?>_video_url">Видео слайда (mp4)</label>
                            <div class="image-field__controls">
                                <input type="text" id="slide_<?= $i ?>_video_url" name="slides[<?= $i ?>][video_url]" value="<?= htmlspecialchars((string) ($slide['video_url'] ?? ''), ENT_QUOTES) ?>" placeholder="/uploads/public/hero.mp4">
                                <button type="button" class="btn btn--small" data-media-pick data-media-target="#slide_<?= $i ?>_video_url" data-media-type="video">Медиабиблиотека</button>
                            </div>
                            <span class="form-hint">Заполнено — слайд показывает видео вместо картинки. Без звука, зациклено; играет только пока слайд на экране.</span>
                        </div>
                        <?= $slideField($i, 'youtube_url', 'Видео слайда с YouTube', (string) ($slide['youtube_url'] ?? ''), 'text', 'Корректная ссылка перебивает mp4 и картинку. Ролик загружается только когда слайд показан.') ?>
                        <?= $slideField($i, 'link_url', 'Ссылка со всего слайда', (string) ($slide['link_url'] ?? ''), 'text', 'Клик по слайду ведёт сюда. Кнопки при этом работают по своим ссылкам.') ?>
                        <?= $slideField($i, 'button_text', 'Кнопка 1 — текст', (string) ($slide['button_text'] ?? '')) ?>
                        <?= $slideField($i, 'button_url', 'Кнопка 1 — ссылка', (string) ($slide['button_url'] ?? '')) ?>
                        <?= $slideField($i, 'button2_text', 'Кнопка 2 — текст', (string) ($slide['button2_text'] ?? '')) ?>
                        <?= $slideField($i, 'button2_url', 'Кнопка 2 — ссылка', (string) ($slide['button2_url'] ?? '')) ?>
                        <div class="form-field">
                            <label for="slide_<?= $i ?>_text_position">Положение текста</label>
                            <select id="slide_<?= $i ?>_text_position" name="slides[<?= $i ?>][text_position]">
                                <?php foreach (['' => 'Как у обложки', 'left' => 'Слева', 'center' => 'По центру', 'right' => 'Справа'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= (string) ($slide['text_position'] ?? '') === (string) $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php
                        // Оформление слайда: «как у обложки» — значение по
                        // умолчанию, чтобы правки не пришлось повторять у
                        // каждого слайда.
                        $slideChoices = [
                            'overlay' => ['label' => 'Затемнение фона', 'options' => ['' => 'Как у обложки', 'on' => 'Включить', 'off' => 'Выключить']],
                            'overlay_mode' => ['label' => 'Тип затемнения', 'options' => ['' => 'Как у обложки', 'gradient' => 'Градиент', 'solid' => 'Сплошное']],
                            'panel' => ['label' => 'Подложка под текстом', 'options' => ['' => 'Как у обложки', 'on' => 'Включить', 'off' => 'Выключить']],
                            'art_position' => ['label' => 'Где картинка поверх фона', 'options' => ['' => 'Как у обложки', 'above' => 'Над текстом', 'left' => 'Слева', 'right' => 'Справа']],
                            'art_size' => ['label' => 'Размер картинки', 'options' => ['' => 'Как у обложки', 'small' => 'Небольшая', 'medium' => 'Средняя', 'large' => 'Крупная']],
                        ];
                        ?>
                        <?= \App\Core\AdminUi::imageField('slides[' . $i . '][art_image]', (string) ($slide['art_image'] ?? ''), [
                            'label' => 'Картинка поверх фона (PNG/SVG)',
                            'hint' => 'Пусто — берётся картинка обложки, если она задана.',
                        ]) ?>
                        <?= $slideField($i, 'art_alt', 'Описание картинки', (string) ($slide['art_alt'] ?? ''), 'text', 'Пусто — картинка считается украшением.') ?>
                        <?php foreach ($slideChoices as $choiceKey => $choice): ?>
                            <div class="form-field">
                                <label for="slide_<?= $i ?>_<?= $choiceKey ?>"><?= htmlspecialchars($choice['label'], ENT_QUOTES) ?></label>
                                <select id="slide_<?= $i ?>_<?= $choiceKey ?>" name="slides[<?= $i ?>][<?= $choiceKey ?>]">
                                    <?php foreach ($choice['options'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= (string) ($slide[$choiceKey] ?? '') === (string) $val ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <?= $slideField($i, '_visible_from', 'Показывать с', \App\Core\BlockVisibility::forInput($slide['_visible_from'] ?? ''), 'datetime-local', 'Пусто — сразу.') ?>
                        <?= $slideField($i, '_visible_to', 'Показывать до', \App\Core\BlockVisibility::forInput($slide['_visible_to'] ?? ''), 'datetime-local', 'Пусто — бессрочно. Слайд исчезнет сам, кэш страницы пересоберётся.') ?>
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить слайд</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template="heroslides">
                <div class="fb-card__head">
                    <span class="fb-card__badge">Слайд</span>
                    <span class="fb-card__tools">
                        <button type="button" class="fb-move" data-fb-move="up" aria-label="Выше" title="Переместить">↑</button>
                        <button type="button" class="fb-move" data-fb-move="down" aria-label="Ниже" title="Переместить">↓</button>
                    </span>
                </div>
                <div class="form-field"><label>Надзаголовок</label><input type="text" name="slides[__INDEX__][eyebrow]"></div>
                <div class="form-field"><label>Заголовок</label><input type="text" name="slides[__INDEX__][title]"></div>
                <div class="form-field"><label>Подзаголовок</label><input type="text" name="slides[__INDEX__][subtitle]"></div>
                <?= \App\Core\AdminUi::imageField('slides[__INDEX__][image]', '', ['label' => 'Изображение слайда', 'hint' => 'Фон слайда, а для видео — заставка до загрузки.']) ?>
                <div class="form-field">
                    <label for="slide___INDEX___video_url">Видео слайда (mp4)</label>
                    <div class="image-field__controls">
                        <input type="text" id="slide___INDEX___video_url" name="slides[__INDEX__][video_url]" placeholder="/uploads/public/hero.mp4">
                        <button type="button" class="btn btn--small" data-media-pick data-media-target="#slide___INDEX___video_url" data-media-type="video">Медиабиблиотека</button>
                    </div>
                    <span class="form-hint">Заполнено — слайд показывает видео вместо картинки.</span>
                </div>
                <div class="form-field"><label>Видео слайда с YouTube</label><input type="text" name="slides[__INDEX__][youtube_url]"><span class="form-hint">Корректная ссылка перебивает mp4 и картинку.</span></div>
                <div class="form-field"><label>Ссылка со всего слайда</label><input type="text" name="slides[__INDEX__][link_url]"></div>
                <div class="form-field"><label>Кнопка 1 — текст</label><input type="text" name="slides[__INDEX__][button_text]"></div>
                <div class="form-field"><label>Кнопка 1 — ссылка</label><input type="text" name="slides[__INDEX__][button_url]"></div>
                <div class="form-field"><label>Кнопка 2 — текст</label><input type="text" name="slides[__INDEX__][button2_text]"></div>
                <div class="form-field"><label>Кнопка 2 — ссылка</label><input type="text" name="slides[__INDEX__][button2_url]"></div>
                <div class="form-field">
                    <label>Положение текста</label>
                    <select name="slides[__INDEX__][text_position]">
                        <option value="">Как у обложки</option>
                        <option value="left">Слева</option>
                        <option value="center">По центру</option>
                        <option value="right">Справа</option>
                    </select>
                </div>
                <div class="form-field"><label>Показывать с</label><input type="datetime-local" name="slides[__INDEX__][_visible_from]"></div>
                <div class="form-field"><label>Показывать до</label><input type="datetime-local" name="slides[__INDEX__][_visible_to]"></div>
                <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить слайд</button>
            </template>
            <div class="repeater-actions">
                <button type="button" class="btn btn--small" data-repeater-add="heroslides"><?= \App\Core\AdminUi::icon('plus') ?>Добавить слайд</button>
            </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'news_feature'): ?>
            <?php $nfVariant = ($data['variant'] ?? 'cards') === 'mosaic' ? 'mosaic' : 'cards'; ?>
            <div class="form-field">
                <label for="nf_variant">Макет блока</label>
                <select id="nf_variant" name="variant">
                    <option value="cards" <?= $nfVariant === 'cards' ? 'selected' : '' ?>>Карточки — крупная новость и ряд карточек</option>
                    <option value="mosaic" <?= $nfVariant === 'mosaic' ? 'selected' : '' ?>>Мозаика — крупная с текстом на фото и колонка справа</option>
                </select>
                <span class="form-hint">«Карточки» повторяют вид страницы новостей. «Мозаика» плотнее: помещает больше материалов на ту же высоту.</span>
            </div>
            <div class="form-field"><label for="nf_limit">Сколько новостей показывать</label><input type="number" id="nf_limit" name="limit" min="2" max="12" value="<?= (int) ($data['limit'] ?? 5) ?>"><span class="form-hint">Всего, вместе с крупной. Для макета «Карточки» ряд заполняется без остатка при 5 или 9 (1 крупная + 4 или 8), для «Мозаики» — при 6.</span></div>
            <div class="form-field"><label for="nf_all_text">Ссылка «Все …» — текст</label><input type="text" id="nf_all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все новости"></div>
            <div class="form-field"><label for="nf_all_url">Ссылка «Все …» — URL (пусто = /news)</label><input type="text" id="nf_all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
        <?php endif; ?>

        <?php if ($type === 'media_gallery'): ?>
            <div class="form-field">
                <label for="mg_description">Описание раздела</label>
                <textarea id="mg_description" name="description" rows="2"><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field">
                <label for="mg_ratio">Пропорция плитки</label>
                <select id="mg_ratio" name="ratio">
                    <?php foreach (['16-9' => '16:9 — как у видео', '4-3' => '4:3 — классическая', '1-1' => '1:1 — квадрат'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['ratio'] ?? '16-9') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Обложки видео и фотоальбомов приходят разного размера — общая пропорция выравнивает ряд.</span>
            </div>
        <?php endif; ?>

        <?php if (in_array($type, ['cards_grid', 'media_gallery'], true)): ?>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все направления"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <?php
            $srcVal = $data['source'] ?? 'manual';
            $srcOptions = $type === 'cards_grid'
                ? ['projects' => 'Из раздела «Проекты»']
                : ['media' => 'Видео + фотоальбомы', 'albums' => 'Из фотоальбомов', 'videos' => 'Из раздела «Видео»'];
            ?>
            <div class="form-field">
                <label for="source">Источник данных</label>
                <select id="source" name="source">
                    <option value="manual" <?= !isset($srcOptions[$srcVal]) ? 'selected' : '' ?>>Ручной список (ниже)</option>
                    <?php foreach ($srcOptions as $value => $label): ?><option value="<?= $value ?>" <?= $srcVal === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES) ?></option><?php endforeach; ?>
                </select>
                <span class="form-hint">Автоматический источник использует записи с отметкой «Показать на главной».</span>
            </div>
            <div class="form-field"><label for="limit">Сколько карточек показывать</label><input type="number" id="limit" name="limit" min="2" max="24" value="<?= (int) ($data['limit'] ?? ($type === 'cards_grid' ? 6 : 8)) ?>"></div>
            <?php if ($type === 'cards_grid'): ?>
                <div class="form-field">
                    <label for="cards_variant">Вариант карточек</label>
                    <select id="cards_variant" name="variant">
                        <option value="icon" <?= ($data['variant'] ?? 'icon') === 'icon' ? 'selected' : '' ?>>Иконка, заголовок и текст</option>
                        <option value="compact" <?= ($data['variant'] ?? 'icon') === 'compact' ? 'selected' : '' ?>>Компактные категории</option>
                        <option value="image" <?= ($data['variant'] ?? 'icon') === 'image' ? 'selected' : '' ?>>Карточки с фотографией</option>
                        <option value="image_below" <?= ($data['variant'] ?? 'icon') === 'image_below' ? 'selected' : '' ?>>Фото сверху, заголовок и текст под ним</option>
                    </select>
                    <span class="form-hint">Один набор данных можно показать как карточки с иконками, категории или карточки с фотографиями.</span>
                </div>
                <div class="form-field"><label for="columns">Колонок</label>
                    <select id="columns" name="columns">
                        <?php foreach ([2,3,4,5] as $n): ?><option value="<?= $n ?>" <?= (int)($data['columns'] ?? 5)===$n?'selected':'' ?>><?= $n ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="colorfield-row">
                    <?= \App\Core\AdminUi::colorField('card_bg', $data['card_bg'] ?? '', 'Цвет карточек (фон)', '#ffffff') ?>
                    <?= \App\Core\AdminUi::colorField('text_color', $data['text_color'] ?? '', 'Цвет текста и иконок', '#173a63') ?>
                </div>
                <?= \App\Core\AdminUi::mediaPositionFields($data['image_position'] ?? 'center-center', $data['image_position_mobile'] ?? 'center-center') ?>
            <?php else: ?>
                <div class="form-field">
                    <label for="columns">Плиток в ряду</label>
                    <select id="columns" name="columns">
                        <?php foreach ([2, 3, 4, 5] as $n): ?><option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 4) === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
                    </select>
                    <span class="form-hint">Действует на широких экранах; ниже 1000px число колонок подбирается автоматически.</span>
                </div>
            <?php endif; ?>
            <div>
                <label>Элементы</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?php if ($type === 'cards_grid'): ?>
                                <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                                <?= \App\Core\AdminUi::imageField("items[{$i}][image]", (string) ($item['image'] ?? ''), ['label' => 'Изображение для варианта с фото']) ?>
                            <?php else: ?>
                                <?= \App\Core\AdminUi::imageField("items[{$i}][image]", (string) ($item['image'] ?? ''), ['label' => 'Превью / фотография']) ?>
                            <?php endif; ?>
                            <div class="form-field"><label>Заголовок</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <?php if ($type === 'cards_grid'): ?>
                                <div class="form-field"><label>Текст</label><textarea name="items[<?= $i ?>][text]"><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <?php elseif ($type === 'media_gallery'): ?>
                                <div class="form-field"><label>Тип</label><select name="items[<?= $i ?>][kind]"><option value="video" <?= ($item['kind'] ?? 'video')==='video'?'selected':'' ?>>Видео</option><option value="photo" <?= ($item['kind'] ?? '')==='photo'?'selected':'' ?>>Фото</option></select></div>
                                <div class="form-field"><label>Длительность (напр. 02:35)</label><input type="text" name="items[<?= $i ?>][meta]" value="<?= htmlspecialchars($item['meta'] ?? '', ENT_QUOTES) ?>"></div>
                                <div class="form-field"><label>Дата</label><input type="text" name="items[<?= $i ?>][text]" value="<?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?>"></div>
                            <?php endif; ?>
                            <div class="form-field"><label>Ссылка</label><input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?php if ($type === 'cards_grid'): ?>
                        <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                        <?= \App\Core\AdminUi::imageField('items[__INDEX__][image]', '', ['label' => 'Изображение для варианта с фото']) ?>
                    <?php else: ?>
                        <?= \App\Core\AdminUi::imageField('items[__INDEX__][image]', '', ['label' => 'Превью / фотография']) ?>
                    <?php endif; ?>
                    <div class="form-field"><label>Заголовок</label><input type="text" name="items[__INDEX__][title]"></div>
                    <?php if ($type === 'cards_grid'): ?>
                        <div class="form-field"><label>Текст</label><textarea name="items[__INDEX__][text]"></textarea></div>
                    <?php elseif ($type === 'media_gallery'): ?>
                        <div class="form-field"><label>Тип</label><select name="items[__INDEX__][kind]"><option value="video">Видео</option><option value="photo">Фото</option></select></div>
                        <div class="form-field"><label>Длительность</label><input type="text" name="items[__INDEX__][meta]"></div>
                        <div class="form-field"><label>Дата</label><input type="text" name="items[__INDEX__][text]"></div>
                    <?php endif; ?>
                    <div class="form-field"><label>Ссылка</label><input type="text" name="items[__INDEX__][url]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'person_cards'): ?>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все руководство"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field">
                <label for="pc_description">Описание раздела</label>
                <textarea id="pc_description" name="description" rows="2"><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field">
                <label for="pc_columns">Колонок в сетке</label>
                <select id="pc_columns" name="columns">
                    <?php foreach ([2, 3, 4, 5] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 4) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает.</span>
            </div>
            <div>
                <label>Персоны (без фото и имени — карточка «Вакантно»)</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::imageField('items[' . $i . '][photo]', (string) ($item['photo'] ?? ''), ['label' => 'Фото']) ?>
                            <div class="form-field"><label>Имя</label><input type="text" name="items[<?= $i ?>][name]" value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Должность</label><input type="text" name="items[<?= $i ?>][role]" value="<?= htmlspecialchars($item['role'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Телефон</label><input type="text" name="items[<?= $i ?>][phone]" value="<?= htmlspecialchars($item['phone'] ?? '', ENT_QUOTES) ?>" placeholder="+998 71 200-00-00"></div>
                            <div class="form-field"><label>E-mail</label><input type="text" name="items[<?= $i ?>][email]" value="<?= htmlspecialchars($item['email'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Ссылка «Подробнее»</label><input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::imageField('items[__INDEX__][photo]', '', ['label' => 'Фото']) ?>
                    <div class="form-field"><label>Имя</label><input type="text" name="items[__INDEX__][name]"></div>
                    <div class="form-field"><label>Должность</label><input type="text" name="items[__INDEX__][role]"></div>
                    <div class="form-field"><label>Телефон</label><input type="text" name="items[__INDEX__][phone]" placeholder="+998 71 200-00-00"></div>
                    <div class="form-field"><label>E-mail</label><input type="text" name="items[__INDEX__][email]"></div>
                    <div class="form-field"><label>Ссылка «Подробнее»</label><input type="text" name="items[__INDEX__][url]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить персону</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'timeline'): ?>
            <?php
            $timelineItems = is_array($data['items'] ?? null) ? $data['items'] : [];
            $timelineHasStatuses = false;
            foreach ($timelineItems as $timelineItem) {
                if (in_array($timelineItem['status'] ?? '', ['done', 'active', 'planned'], true)) {
                    $timelineHasStatuses = true;
                    break;
                }
            }
            $timelineLastIndex = count($timelineItems) - 1;
            ?>
            <div>
                <label>События (год + описание + статус)</label>
                <div data-repeater="items">
                    <?php foreach ($timelineItems as $i => $item): ?>
                        <?php
                        $timelineStatus = in_array($item['status'] ?? '', ['done', 'active', 'planned'], true)
                            ? (string) $item['status']
                            : (!$timelineHasStatuses ? ($i === $timelineLastIndex ? 'active' : 'done') : 'planned');
                        ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Год</label><input type="text" name="items[<?= $i ?>][year]" value="<?= htmlspecialchars($item['year'] ?? '', ENT_QUOTES) ?>" placeholder="2023+"></div>
                            <div class="form-field"><label>Текст</label><textarea name="items[<?= $i ?>][text]"><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <div class="form-field"><label>Статус</label><select name="items[<?= $i ?>][status]">
                                <?php foreach (['done' => 'Завершён', 'active' => 'В процессе', 'planned' => 'Запланирован'] as $statusValue => $statusLabel): ?>
                                    <option value="<?= $statusValue ?>" <?= $timelineStatus === $statusValue ? 'selected' : '' ?>><?= $statusLabel ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Год</label><input type="text" name="items[__INDEX__][year]"></div>
                    <div class="form-field"><label>Текст</label><textarea name="items[__INDEX__][text]"></textarea></div>
                    <div class="form-field"><label>Статус</label><select name="items[__INDEX__][status]"><option value="done">Завершён</option><option value="active">В процессе</option><option value="planned" selected>Запланирован</option></select></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить событие</button></div>
            </div>
            <div class="form-field"><label for="button_text">Кнопка под таймлайном — текст</label><input type="text" id="button_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>" placeholder="Вся история"></div>
            <div class="form-field"><label for="button_url">Кнопка под таймлайном — ссылка</label><input type="text" id="button_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>"></div>
            <hr>
            <div class="form-field"><label for="cta_title">CTA-карточка справа — заголовок (пусто = без карточки)</label><input type="text" id="cta_title" name="cta_title" value="<?= htmlspecialchars($data['cta_title'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="cta_text">CTA — текст</label><textarea id="cta_text" name="cta_text" rows="2"><?= htmlspecialchars($data['cta_text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field"><label for="cta_button_text">CTA — текст кнопки</label><input type="text" id="cta_button_text" name="cta_button_text" value="<?= htmlspecialchars($data['cta_button_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="cta_button_url">CTA — ссылка кнопки</label><input type="text" id="cta_button_url" name="cta_button_url" value="<?= htmlspecialchars($data['cta_button_url'] ?? '', ENT_QUOTES) ?>"></div>
            <?= \App\Core\AdminUi::imageField('cta_image', (string) ($data['cta_image'] ?? ''), ['label' => 'CTA — фоновое фото']) ?>
        <?php endif; ?>

        <?php if ($type === 'news_docs'): ?>
            <div class="form-field"><label for="news_title">Колонка новостей — заголовок</label><input type="text" id="news_title" name="news_title" value="<?= htmlspecialchars($data['news_title'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="limit">Сколько новостей (1–6)</label><input type="number" id="limit" name="limit" min="1" max="6" value="<?= (int) ($data['limit'] ?? 3) ?>"><span class="form-hint">Берутся последние опубликованные новости.</span></div>
            <div class="form-field"><label for="news_all_text">Новости: «Все …» — текст</label><input type="text" id="news_all_text" name="news_all_text" value="<?= htmlspecialchars($data['news_all_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="news_all_url">Новости: «Все …» — URL (пусто = /news)</label><input type="text" id="news_all_url" name="news_all_url" value="<?= htmlspecialchars($data['news_all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <hr>
            <div class="form-field"><label for="docs_title">Колонка документов — заголовок</label><input type="text" id="docs_title" name="docs_title" value="<?= htmlspecialchars($data['docs_title'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="docs_all_text">Документы: «Все …» — текст</label><input type="text" id="docs_all_text" name="docs_all_text" value="<?= htmlspecialchars($data['docs_all_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="docs_all_url">Документы: «Все …» — URL</label><input type="text" id="docs_all_url" name="docs_all_url" value="<?= htmlspecialchars($data['docs_all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div>
                <label>Документы</label>
                <div data-repeater="docs">
                    <?php foreach (($data['docs'] ?? []) as $i => $doc): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Название</label><input type="text" name="docs[<?= $i ?>][title]" value="<?= htmlspecialchars($doc['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Мета (напр. PDF · 2.4 МБ)</label><input type="text" name="docs[<?= $i ?>][meta]" value="<?= htmlspecialchars($doc['meta'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field">
                                <label>Ссылка на файл</label>
                                <div class="u-inline-b9bbe540d3">
                                    <input class="u-inline-7623f05545" type="text" name="docs[<?= $i ?>][url]" value="<?= htmlspecialchars($doc['url'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/public/....pdf">
                                    <button type="button" class="btn btn--secondary btn--small" data-media-pick data-media-target="[name='docs[<?= $i ?>][url]']" data-media-type="all_files">Выбрать</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="docs">
                    <div class="form-field"><label>Название</label><input type="text" name="docs[__INDEX__][title]"></div>
                    <div class="form-field"><label>Мета (напр. PDF · 2.4 МБ)</label><input type="text" name="docs[__INDEX__][meta]"></div>
                    <div class="form-field">
                        <label>Ссылка на файл</label>
                        <div class="u-inline-b9bbe540d3">
                            <input class="u-inline-7623f05545" type="text" name="docs[__INDEX__][url]">
                            <button type="button" class="btn btn--secondary btn--small" data-media-pick data-media-target="[name='docs[__INDEX__][url]']" data-media-type="all_files">Выбрать</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="docs"><?= \App\Core\AdminUi::icon('plus') ?>Добавить документ</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'icon_text'): ?>
            <div class="form-field"><label for="it_description">Описание под заголовком</label><textarea id="it_description" name="description" rows="2"><?= htmlspecialchars($data['description'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field">
                <label for="it_variant">Вариант отображения</label>
                <select id="it_variant" name="variant">
                    <?php foreach (['cards' => 'Карточки с рамкой', 'plain' => 'Без рамок — иконка и текст', 'inline' => 'В строку — короткие реквизиты'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['variant'] ?? 'cards') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">«Без рамок» стоит выбрать, если рядом уже есть карточные секции — иначе блоки спорят друг с другом.</span>
            </div>
            <?php
            // Позиция иконки появилась позже выравнивания: у блоков, сохранённых
            // до неё, «по центру» означало иконку сверху — подставляем то же.
            $itIconPos = (string) ($data['icon_position'] ?? '');
            if (!in_array($itIconPos, ['left', 'top', 'right'], true)) {
                $itIconPos = ($data['align'] ?? 'left') === 'center' ? 'top' : 'left';
            }
            ?>
            <div class="form-field">
                <label for="it_icon_position">Позиция иконки</label>
                <select id="it_icon_position" name="icon_position">
                    <?php foreach (['left' => 'Слева от текста', 'top' => 'Над текстом', 'right' => 'Справа от текста'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $itIconPos === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Не зависит от выравнивания: можно поставить иконку над текстом, оставив сам текст по левому краю.</span>
            </div>
            <div class="form-field">
                <label for="it_rows_layout">Подпись и значение</label>
                <select id="it_rows_layout" name="rows_layout">
                    <option value="stacked" <?= ($data['rows_layout'] ?? 'stacked') === 'stacked' ? 'selected' : '' ?>>В столбик — подпись над значением</option>
                    <option value="inline" <?= ($data['rows_layout'] ?? 'stacked') === 'inline' ? 'selected' : '' ?>>В одну строку — «Приёмная: +998 71 200-00-00»</option>
                </select>
                <span class="form-hint">В одну строку удобно для коротких реквизитов. Двоеточие после подписи ставится вручную в поле строк.</span>
            </div>
            <div class="form-field">
                <label for="it_align">Выравнивание текста</label>
                <select id="it_align" name="align">
                    <option value="left" <?= ($data['align'] ?? 'left') === 'left' ? 'selected' : '' ?>>По левому краю</option>
                    <option value="center" <?= ($data['align'] ?? 'left') === 'center' ? 'selected' : '' ?>>По центру</option>
                </select>
            </div>
            <div class="form-field">
                <label for="it_columns">Колонок</label>
                <select id="it_columns" name="columns">
                    <?php foreach ([1, 2, 3, 4] as $itCols): ?>
                        <option value="<?= $itCols ?>" <?= (int) ($data['columns'] ?? 3) === $itCols ? 'selected' : '' ?>><?= $itCols ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Карточки</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field"><label>Цвет иконки</label><input type="text" name="items[<?= $i ?>][icon_color]" value="<?= htmlspecialchars($item['icon_color'] ?? '', ENT_QUOTES) ?>" placeholder="#3f9c5a — пусто = цвет сайта"></div>
                            <div class="form-field">
                                <label>Строки</label>
                                <textarea name="items[<?= $i ?>][rows]" rows="3" placeholder="Телефон доверия | (71) 202-06-00"><?= htmlspecialchars($item['rows'] ?? '', ENT_QUOTES) ?></textarea>
                                <span class="form-hint">По строке на пару: подпись, вертикальная черта, значение. Строка без черты выводится подписью.</span>
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field"><label>Цвет иконки</label><input type="text" name="items[__INDEX__][icon_color]" placeholder="#3f9c5a — пусто = цвет сайта"></div>
                    <div class="form-field"><label>Строки</label><textarea name="items[__INDEX__][rows]" rows="3" placeholder="Телефон доверия | (71) 202-06-00"></textarea></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить карточку</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'leader_card'): ?>
            <?= \App\Core\AdminUi::imageField('photo', (string) ($data['photo'] ?? ''), ['label' => 'Фото руководителя']) ?>
            <div class="form-field"><label for="lc_name">Имя</label><input type="text" id="lc_name" name="name" value="<?= htmlspecialchars($data['name'] ?? '', ENT_QUOTES) ?>" placeholder="Фамилия Имя Отчество"></div>
            <?php $lcTag = in_array($data['name_tag'] ?? 'p', ['p', 'h2', 'h3'], true) ? $data['name_tag'] : 'p'; ?>
            <div class="form-field">
                <label for="lc_name_tag">Тег имени</label>
                <select id="lc_name_tag" name="name_tag">
                    <option value="p" <?= $lcTag === 'p' ? 'selected' : '' ?>>Обычный текст (не заголовок)</option>
                    <option value="h2" <?= $lcTag === 'h2' ? 'selected' : '' ?>>Заголовок H2</option>
                    <option value="h3" <?= $lcTag === 'h3' ? 'selected' : '' ?>>Заголовок H3</option>
                </select>
                <span class="form-hint">Заголовок ставится, когда карточка — самостоятельный раздел страницы. Уровни не должны перескакивать: под H1 страницы идёт H2, под ним H3. Заголовки вкладок подстроятся на уровень ниже сами.</span>
            </div>
            <div class="form-field"><label for="lc_position">Должность</label><textarea id="lc_position" name="position" rows="2" placeholder="Директор Агентства…"><?= htmlspecialchars($data['position'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field"><label for="lc_phone">Телефон</label><input type="text" id="lc_phone" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '', ENT_QUOTES) ?>" placeholder="+998 71 203 10 00"></div>
            <div class="form-field"><label for="lc_email">E-mail</label><input type="text" id="lc_email" name="email" value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="lc_hours">Приёмные часы</label><input type="text" id="lc_hours" name="hours" value="<?= htmlspecialchars($data['hours'] ?? '', ENT_QUOTES) ?>" placeholder="Пн–Пт 10:00 – 12:00"></div>

            <p class="form-hint">Соцсети: пустое поле — значок не показывается.</p>
            <?php foreach (['facebook' => 'Facebook', 'x' => 'X (Twitter)', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'telegram' => 'Telegram'] as $lcNet => $lcNetLabel): ?>
                <div class="form-field">
                    <label for="lc_<?= $lcNet ?>"><?= htmlspecialchars($lcNetLabel, ENT_QUOTES) ?></label>
                    <input type="text" id="lc_<?= $lcNet ?>" name="<?= $lcNet ?>" value="<?= htmlspecialchars($data[$lcNet] ?? '', ENT_QUOTES) ?>" placeholder="https://">
                </div>
            <?php endforeach; ?>

            <hr>
            <div class="form-field"><label for="lc_facts_title">Вкладка 1 — заголовок</label><input type="text" id="lc_facts_title" name="facts_title" value="<?= htmlspecialchars($data['facts_title'] ?? 'Основная информация', ENT_QUOTES) ?>"></div>
            <?= \App\Core\AdminUi::iconField('facts_icon', $data['facts_icon'] ?? '', ['id' => 'lc_facts_icon', 'label' => 'Вкладка 1 — иконка']) ?>
            <div>
                <label>Строки первой вкладки</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field"><label>Название</label><input type="text" name="items[<?= $i ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?>" placeholder="Образование"></div>
                            <div class="form-field"><label>Значение</label><textarea name="items[<?= $i ?>][value]" rows="2"><?= htmlspecialchars($item['value'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field"><label>Название</label><input type="text" name="items[__INDEX__][label]"></div>
                    <div class="form-field"><label>Значение</label><textarea name="items[__INDEX__][value]" rows="2"></textarea></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить строку</button></div>
            </div>

            <hr>
            <div class="form-field"><label for="lc_bio_title">Вкладка 2 — заголовок</label><input type="text" id="lc_bio_title" name="bio_title" value="<?= htmlspecialchars($data['bio_title'] ?? 'Биография', ENT_QUOTES) ?>"></div>
            <?= \App\Core\AdminUi::iconField('bio_icon', $data['bio_icon'] ?? '', ['id' => 'lc_bio_icon', 'label' => 'Вкладка 2 — иконка']) ?>
            <div class="form-field"><label for="lc_bio">Вкладка 2 — текст</label><textarea id="lc_bio" name="bio" rows="6" data-wysiwyg><?= htmlspecialchars($data['bio'] ?? '', ENT_QUOTES) ?></textarea></div>

            <hr>
            <div class="form-field"><label for="lc_duties_title">Вкладка 3 — заголовок</label><input type="text" id="lc_duties_title" name="duties_title" value="<?= htmlspecialchars($data['duties_title'] ?? 'Функции', ENT_QUOTES) ?>"></div>
            <?= \App\Core\AdminUi::iconField('duties_icon', $data['duties_icon'] ?? '', ['id' => 'lc_duties_icon', 'label' => 'Вкладка 3 — иконка']) ?>
            <div class="form-field"><label for="lc_duties">Вкладка 3 — текст</label><textarea id="lc_duties" name="duties" rows="6" data-wysiwyg><?= htmlspecialchars($data['duties'] ?? '', ENT_QUOTES) ?></textarea></div>
            <hr>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="lc_mobile_icons_only" name="mobile_icons_only" value="1" <?= !empty($data['mobile_icons_only']) ? 'checked' : '' ?>>
                <label for="lc_mobile_icons_only">На узком экране оставить во вкладках только иконки</label>
            </div>
            <p class="form-hint">Экономит место, когда заголовки длинные. Работает, только если иконка задана у каждой показанной вкладки — иначе получилась бы пустая вкладка, и настройка молча не применится. Подписи остаются доступны скринридеру.</p>
            <p class="form-hint">Вкладка без заголовка и без содержимого не показывается. Цвет активной вкладки берётся из акцента сайта («Дизайн сайта»), отдельной настройки у блока нет.</p>
        <?php endif; ?>

        <?php if ($type === 'person_profile'): ?>
            <?= \App\Core\AdminUi::imageField('photo', (string) ($data['photo'] ?? ''), ['label' => 'Фото']) ?>
            <div class="form-field"><label for="name">Имя</label><input type="text" id="name" name="name" value="<?= htmlspecialchars($data['name'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="position">Должность</label><input type="text" id="position" name="position" value="<?= htmlspecialchars($data['position'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="text">Описание</label><textarea id="text" name="text" rows="4"><?= htmlspecialchars($data['text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field"><label for="phone_label">Подпись телефона</label><input type="text" id="phone_label" name="phone_label" value="<?= htmlspecialchars($data['phone_label'] ?? 'Приёмная:', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="phone">Телефон</label><input type="text" id="phone" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '', ENT_QUOTES) ?>" placeholder="+998 71 203 10 00"></div>
            <div class="form-field"><label for="email_label">Подпись e-mail</label><input type="text" id="email_label" name="email_label" value="<?= htmlspecialchars($data['email_label'] ?? 'E-mail:', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="email">E-mail</label><input type="text" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="button_text">Кнопка — текст</label><input type="text" id="button_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>" placeholder="Обратиться к руководителю"></div>
            <div class="form-field"><label for="button_url">Кнопка — ссылка</label><input type="text" id="button_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="button2_text">Вторая кнопка — текст</label><input type="text" id="button2_text" name="button2_text" value="<?= htmlspecialchars($data['button2_text'] ?? '', ENT_QUOTES) ?>" placeholder="Биография"></div>
            <div class="form-field"><label for="button2_url">Вторая кнопка — ссылка</label><input type="text" id="button2_url" name="button2_url" value="<?= htmlspecialchars($data['button2_url'] ?? '', ENT_QUOTES) ?>"><span class="form-hint">Вторая кнопка оформляется контуром — как второстепенное действие.</span></div>
            <div class="form-field">
                <label for="pp_photo_side">Сторона фото</label>
                <select id="pp_photo_side" name="photo_side">
                    <option value="left" <?= ($data['photo_side'] ?? 'left') === 'left' ? 'selected' : '' ?>>Слева</option>
                    <option value="right" <?= ($data['photo_side'] ?? 'left') === 'right' ? 'selected' : '' ?>>Справа</option>
                </select>
                <span class="form-hint">На узких экранах фото в любом случае встаёт над текстом.</span>
            </div>
            <?php foreach (['telegram' => 'Telegram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'x' => 'X', 'instagram' => 'Instagram'] as $ppSocial => $ppLabel): ?>
                <div class="form-field"><label for="pp_<?= $ppSocial ?>"><?= $ppLabel ?></label><input type="text" id="pp_<?= $ppSocial ?>" name="<?= $ppSocial ?>" value="<?= htmlspecialchars($data[$ppSocial] ?? '', ENT_QUOTES) ?>" placeholder="https://..."></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($type === 'bio_education'): ?>
            <div class="form-field"><label for="bio_title">Левая колонка — заголовок</label><input type="text" id="bio_title" name="bio_title" value="<?= htmlspecialchars($data['bio_title'] ?? 'Биография', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="bio_text">Вступительный текст</label><textarea id="bio_text" name="bio_text" rows="4"><?= htmlspecialchars($data['bio_text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field"><label for="career_title">Заголовок хронологии</label><input type="text" id="career_title" name="career_title" value="<?= htmlspecialchars($data['career_title'] ?? '', ENT_QUOTES) ?>" placeholder="Профессиональный путь"></div>
            <div>
                <label>Карьера (годы + позиция)</label>
                <div data-repeater="career">
                    <?php foreach (($data['career'] ?? []) as $i => $row): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Годы</label><input type="text" name="career[<?= $i ?>][years]" value="<?= htmlspecialchars($row['years'] ?? '', ENT_QUOTES) ?>" placeholder="2023 – н.в."></div>
                            <div class="form-field"><label>Позиция</label><textarea name="career[<?= $i ?>][text]"><?= htmlspecialchars($row['text'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="career">
                    <div class="form-field"><label>Годы</label><input type="text" name="career[__INDEX__][years]"></div>
                    <div class="form-field"><label>Позиция</label><textarea name="career[__INDEX__][text]"></textarea></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="career"><?= \App\Core\AdminUi::icon('plus') ?>Добавить период</button></div>
            </div>
            <hr>
            <div class="form-field"><label for="edu_title">Правая колонка — заголовок</label><input type="text" id="edu_title" name="edu_title" value="<?= htmlspecialchars($data['edu_title'] ?? 'Образование', ENT_QUOTES) ?>"></div>
            <div>
                <label>Образование (годы + степень + вуз)</label>
                <div data-repeater="edu_items">
                    <?php foreach (($data['edu_items'] ?? []) as $i => $row): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Годы</label><input type="text" name="edu_items[<?= $i ?>][years]" value="<?= htmlspecialchars($row['years'] ?? '', ENT_QUOTES) ?>" placeholder="2011 – 2013"></div>
                            <div class="form-field"><label>Степень</label><input type="text" name="edu_items[<?= $i ?>][title]" value="<?= htmlspecialchars($row['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Учебное заведение</label><input type="text" name="edu_items[<?= $i ?>][org]" value="<?= htmlspecialchars($row['org'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="edu_items">
                    <div class="form-field"><label>Годы</label><input type="text" name="edu_items[__INDEX__][years]"></div>
                    <div class="form-field"><label>Степень</label><input type="text" name="edu_items[__INDEX__][title]"></div>
                    <div class="form-field"><label>Учебное заведение</label><input type="text" name="edu_items[__INDEX__][org]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="edu_items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить</button></div>
            </div>
            <div class="form-field"><label for="extra_title">Доп. образование — заголовок</label><input type="text" id="extra_title" name="extra_title" value="<?= htmlspecialchars($data['extra_title'] ?? '', ENT_QUOTES) ?>" placeholder="Дополнительное образование"></div>
            <div class="form-field"><label for="extra_text">Доп. образование — пункты (по одному на строку)</label><textarea id="extra_text" name="extra_text" rows="3"><?= htmlspecialchars($data['extra_text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <?php
            $widgetSlots = [
                ['key' => 'widgets_before', 'title' => 'Виджеты над образованием', 'selected' => (array) ($data['widgets_before'] ?? [])],
                ['key' => 'widgets_after', 'title' => 'Виджеты под образованием', 'selected' => (array) ($data['widgets_after'] ?? [])],
            ];
            $widgetLabel = static function (array $widget): string {
                $typeLabel = \App\Models\Widget::TYPE_LABELS[(string) ($widget['type'] ?? '')] ?? (string) ($widget['type'] ?? 'Виджет');
                $title = trim((string) ($widget['title'] ?? ''));
                $lang = trim((string) ($widget['lang'] ?? ''));
                $parts = [$title !== '' ? $title : $typeLabel, $lang !== '' ? strtoupper($lang) : 'все языки'];
                if (empty($widget['is_active'])) {
                    $parts[] = 'выключен';
                }
                return implode(' · ', $parts);
            };
            ?>
            <div class="bio-widget-slots">
                <h3>Виджеты правой колонки</h3>
                <p class="form-hint">Можно добавить существующие виджеты непосредственно до или после карточки «Образование». Порядок меняется стрелками. На сайте выводятся только активные виджеты текущего языка.</p>
                <?php if ($widgets === []): ?>
                    <p class="form-hint">Сначала создайте виджет в разделе <a href="/admin/widgets/create">«Виджеты»</a>.</p>
                <?php endif; ?>
                <?php foreach ($widgetSlots as $slot): ?>
                    <?php $slotKey = (string) $slot['key']; ?>
                    <section class="bio-widget-slot">
                        <h4><?= htmlspecialchars((string) $slot['title'], ENT_QUOTES) ?></h4>
                        <div data-repeater="<?= htmlspecialchars($slotKey, ENT_QUOTES) ?>" data-repeater-max="12" data-widget-slot>
                            <?php foreach ($slot['selected'] as $i => $selectedId): ?>
                                <div class="repeater-row widget-slot-row">
                                    <div class="widget-slot-row__head">
                                        <span>Виджет</span>
                                        <span class="widget-slot-row__tools">
                                            <button type="button" class="btn btn--small" data-repeater-move="up" aria-label="Переместить выше" title="Переместить выше"><?= \App\Core\AdminUi::icon('arrow-up') ?></button>
                                            <button type="button" class="btn btn--small" data-repeater-move="down" aria-label="Переместить ниже" title="Переместить ниже"><?= \App\Core\AdminUi::icon('arrow-down') ?></button>
                                        </span>
                                    </div>
                                    <select name="<?= htmlspecialchars($slotKey, ENT_QUOTES) ?>[<?= (int) $i ?>]">
                                        <option value="">— выберите виджет —</option>
                                        <?php foreach ($widgets as $widget): ?>
                                            <option value="<?= (int) $widget['id'] ?>" <?= (int) $selectedId === (int) $widget['id'] ? 'selected' : '' ?>><?= htmlspecialchars($widgetLabel($widget), ENT_QUOTES) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <template data-repeater-template="<?= htmlspecialchars($slotKey, ENT_QUOTES) ?>">
                            <div class="widget-slot-row__head">
                                <span>Виджет</span>
                                <span class="widget-slot-row__tools">
                                    <button type="button" class="btn btn--small" data-repeater-move="up" aria-label="Переместить выше" title="Переместить выше"><?= \App\Core\AdminUi::icon('arrow-up') ?></button>
                                    <button type="button" class="btn btn--small" data-repeater-move="down" aria-label="Переместить ниже" title="Переместить ниже"><?= \App\Core\AdminUi::icon('arrow-down') ?></button>
                                </span>
                            </div>
                            <select name="<?= htmlspecialchars($slotKey, ENT_QUOTES) ?>[__INDEX__]">
                                <option value="">— выберите виджет —</option>
                                <?php foreach ($widgets as $widget): ?>
                                    <option value="<?= (int) $widget['id'] ?>"><?= htmlspecialchars($widgetLabel($widget), ENT_QUOTES) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </template>
                        <?php if ($widgets !== []): ?>
                            <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="<?= htmlspecialchars($slotKey, ENT_QUOTES) ?>"><?= \App\Core\AdminUi::icon('plus') ?>Добавить виджет</button></div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
            <div class="form-field"><label for="quote_text">Цитата</label><textarea id="quote_text" name="quote_text" rows="2"><?= htmlspecialchars($data['quote_text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field"><label for="quote_author">Автор цитаты</label><input type="text" id="quote_author" name="quote_author" value="<?= htmlspecialchars($data['quote_author'] ?? '', ENT_QUOTES) ?>"></div>
        <?php endif; ?>

        <?php if ($type === 'anchor_nav'): ?>
            <div class="form-field form-field--check">
                <label><input type="checkbox" name="auto" value="1" <?= !empty($data['auto']) ? 'checked' : '' ?>> Собирать разделы автоматически</label>
                <span class="form-hint">В навигацию попадут все блоки страницы, у которых заполнен заголовок — в том порядке, в каком они идут. Пункты ниже добавляются перед ними.</span>
            </div>
            <div class="form-field form-field--check">
                <label><input type="checkbox" name="sticky" value="1" <?= !empty($data['sticky']) ? 'checked' : '' ?>> Закреплять при прокрутке</label>
                <span class="form-hint">Полоса остаётся под шапкой и подсвечивает раздел, который сейчас на экране. Полезно на длинных страницах.</span>
            </div>
            <div>
                <label>Пункты навигации (якоря разделов или ссылки)</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Название</label><input type="text" name="items[<?= $i ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?>" placeholder="Обзор"></div>
                            <div class="form-field"><label>Ссылка (якорь #block-N или URL)</label><input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>" placeholder="#block-12"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Название</label><input type="text" name="items[__INDEX__][label]"></div>
                    <div class="form-field"><label>Ссылка</label><input type="text" name="items[__INDEX__][url]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить пункт</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'stages'): ?>
            <div class="form-field"><label for="stages_variant">Вариант отображения</label><select id="stages_variant" name="variant"><option value="default" <?= ($data['variant'] ?? 'default') === 'default' ? 'selected' : '' ?>>Этапы реализации</option><option value="history" <?= ($data['variant'] ?? 'default') === 'history' ? 'selected' : '' ?>>История организации</option></select></div>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все этапы"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field">
                <label for="stages_columns">Этапов в ряду</label>
                <select id="stages_columns" name="columns">
                    <option value="0" <?= (int) ($data['columns'] ?? 0) === 0 ? 'selected' : '' ?>>Автоматически по числу этапов</option>
                    <?php foreach ([2, 3, 4, 5] as $n): ?>
                        <option value="<?= $n ?>" <?= (int) ($data['columns'] ?? 0) === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Если этапов больше, ряд превращается в прокручиваемую полосу.</span>
            </div>
            <div class="form-field">
                <label for="stages_autoplay">Автопрокрутка, секунд (0 — выключена)</label>
                <input type="number" id="stages_autoplay" name="autoplay" min="0" max="30" value="<?= (int) ($data['autoplay'] ?? 0) ?>">
            </div>
            <div>
                <label>Этапы</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Годы</label><input type="text" name="items[<?= $i ?>][year]" value="<?= htmlspecialchars($item['year'] ?? '', ENT_QUOTES) ?>" placeholder="2026–2027"></div>
                            <div class="form-field"><label>Подпись этапа</label><input type="text" name="items[<?= $i ?>][stage]" value="<?= htmlspecialchars($item['stage'] ?? '', ENT_QUOTES) ?>" placeholder="III этап"></div>
                            <div class="form-field"><label>Заголовок</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Текст</label><textarea name="items[<?= $i ?>][text]"><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></textarea></div>
                            <div class="form-field"><label>Статус</label><select name="items[<?= $i ?>][status]">
                                <?php foreach (['done' => 'Завершён', 'active' => 'В процессе', 'planned' => 'Запланирован'] as $sv => $sl): ?>
                                    <option value="<?= $sv ?>" <?= ($item['status'] ?? 'planned') === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <div class="form-field"><label>Свой текст статуса (необязательно)</label><input type="text" name="items[<?= $i ?>][status_text]" value="<?= htmlspecialchars($item['status_text'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Ссылка с этапа (необязательно)</label><input type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить этап</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Годы</label><input type="text" name="items[__INDEX__][year]"></div>
                    <div class="form-field"><label>Подпись этапа</label><input type="text" name="items[__INDEX__][stage]"></div>
                    <div class="form-field"><label>Заголовок</label><input type="text" name="items[__INDEX__][title]"></div>
                    <div class="form-field"><label>Текст</label><textarea name="items[__INDEX__][text]"></textarea></div>
                    <div class="form-field"><label>Статус</label><select name="items[__INDEX__][status]"><option value="done">Завершён</option><option value="active">В процессе</option><option value="planned" selected>Запланирован</option></select></div>
                    <div class="form-field"><label>Свой текст статуса</label><input type="text" name="items[__INDEX__][status_text]"></div>
                    <div class="form-field"><label>Ссылка с этапа (необязательно)</label><input type="text" name="items[__INDEX__][url]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить этап</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить этап</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'text_image'): ?>
            <div class="form-field"><label for="text">Текст</label><textarea id="text" name="text" rows="5" data-wysiwyg><?= htmlspecialchars($data['text'] ?? '', ENT_QUOTES) ?></textarea></div>
            <?= \App\Core\AdminUi::imageField('image', $data['image'] ?? '', ['label' => 'Изображение']) ?>
            <div class="form-field"><label for="image_side">Положение изображения</label><select id="image_side" name="image_side"><option value="right" <?= ($data['image_side'] ?? 'right') === 'right' ? 'selected' : '' ?>>Справа</option><option value="left" <?= ($data['image_side'] ?? 'right') === 'left' ? 'selected' : '' ?>>Слева</option></select></div>
            <?= \App\Core\AdminUi::mediaPositionFields($data['image_position'] ?? 'center-center', $data['image_position_mobile'] ?? 'center-center') ?>
            <div class="form-field">
                <label for="ti_ratio">Пропорция кадра</label>
                <select id="ti_ratio" name="image_ratio">
                    <?php foreach (['auto' => 'Как у изображения', '16-9' => '16:9 — широкий кадр', '4-3' => '4:3 — классический', '1-1' => '1:1 — квадрат'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($data['image_ratio'] ?? 'auto') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Общая пропорция выравнивает высоту кадра с текстовой колонкой.</span>
            </div>
            <div class="form-field">
                <label for="ti_width">Ширина изображения, % ряда</label>
                <input type="number" id="ti_width" name="image_width" min="30" max="60" step="5" value="<?= (int) ($data['image_width'] ?? 50) ?>">
                <span class="form-hint">Оставшаяся часть ряда уходит под текст. На узких экранах колонки всё равно встают друг под друга.</span>
            </div>
            <div class="form-field"><label for="ti_btn_text">Кнопка — текст</label><input type="text" id="ti_btn_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="ti_btn_url">Кнопка — ссылка</label><input type="text" id="ti_btn_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div>
                <label>Мини-фичи под текстом (иконка + подпись)</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <?= \App\Core\AdminUi::iconField("items[{$i}][icon_svg]", $item['icon_svg'] ?? '', ['label' => 'Иконка Tabler']) ?>
                            <div class="form-field"><label>Подпись</label><input type="text" name="items[<?= $i ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?>"></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <?= \App\Core\AdminUi::iconField('items[__INDEX__][icon_svg]', '', ['label' => 'Иконка Tabler']) ?>
                    <div class="form-field"><label>Подпись</label><input type="text" name="items[__INDEX__][label]"></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'docs_list'): ?>
            <div class="form-field">
                <label for="docs_variant">Вариант списка</label>
                <select id="docs_variant" name="variant">
                    <option value="grid" <?= ($data['variant'] ?? 'grid') === 'grid' ? 'selected' : '' ?>>Карточки документов</option>
                    <option value="links" <?= ($data['variant'] ?? 'grid') === 'links' ? 'selected' : '' ?>>Компактный список ссылок</option>
                    <option value="acts" <?= ($data['variant'] ?? 'grid') === 'acts' ? 'selected' : '' ?>>Правовые акты (номер и дата)</option>
                    <option value="acts-editorial" <?= ($data['variant'] ?? 'grid') === 'acts-editorial' ? 'selected' : '' ?>>Правовые акты — редакционные карточки</option>
                </select>
                <span class="form-hint">Компактный вариант подходит для короткого списка файлов и внешних материалов. «Правовые акты» показывают номер и дату из полей ниже.</span>
            </div>
            <div class="form-field"><label for="all_text">Ссылка «Все …» — текст</label><input type="text" id="all_text" name="all_text" value="<?= htmlspecialchars($data['all_text'] ?? '', ENT_QUOTES) ?>" placeholder="Все документы"></div>
            <div class="form-field"><label for="all_url">Ссылка «Все …» — URL</label><input type="text" id="all_url" name="all_url" value="<?= htmlspecialchars($data['all_url'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="columns">Колонок</label><select id="columns" name="columns"><?php foreach ([1,2,3,4,5] as $n): ?><option value="<?= $n ?>" <?= (int)($data['columns'] ?? 4)===$n?'selected':'' ?>><?= $n ?></option><?php endforeach; ?></select></div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="search_enabled" name="search_enabled" value="1" <?= (!array_key_exists('search_enabled', $data) || !empty($data['search_enabled'])) ? 'checked' : '' ?>>
                <label for="search_enabled">Добавлять поиск и фильтр форматов</label>
            </div>
            <div>
                <label>Документы</label>
                <div data-repeater="items">
                    <?php foreach (($data['items'] ?? []) as $i => $item): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Название</label><input type="text" name="items[<?= $i ?>][title]" value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Мета (необязательно)</label><input type="text" name="items[<?= $i ?>][meta]" value="<?= htmlspecialchars($item['meta'] ?? '', ENT_QUOTES) ?>"><span class="form-hint">Формат и размер локального файла определяются автоматически.</span></div>
                            <div class="form-field"><label>Номер акта</label><input type="text" name="items[<?= $i ?>][number]" value="<?= htmlspecialchars($item['number'] ?? '', ENT_QUOTES) ?>" placeholder="ПФ-6079"><span class="form-hint">Показывается в варианте «Правовые акты».</span></div>
                            <div class="form-field"><label>Дата акта</label><input type="text" name="items[<?= $i ?>][date]" value="<?= htmlspecialchars($item['date'] ?? '', ENT_QUOTES) ?>" placeholder="12 марта 2026 г."></div>
                            <div class="form-field">
                                <label>Ссылка на файл</label>
                                <div class="u-inline-b9bbe540d3">
                                    <input class="u-inline-7623f05545" type="text" name="items[<?= $i ?>][url]" value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>">
                                    <button type="button" class="btn btn--secondary btn--small" data-media-pick data-media-target="[name='items[<?= $i ?>][url]']" data-media-type="all_files">Выбрать</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="items">
                    <div class="form-field"><label>Название</label><input type="text" name="items[__INDEX__][title]"></div>
                    <div class="form-field"><label>Мета</label><input type="text" name="items[__INDEX__][meta]"></div>
                    <div class="form-field"><label>Номер акта</label><input type="text" name="items[__INDEX__][number]" placeholder="ПФ-6079"></div>
                    <div class="form-field"><label>Дата акта</label><input type="text" name="items[__INDEX__][date]" placeholder="12 марта 2026 г."></div>
                    <div class="form-field">
                        <label>Ссылка на файл</label>
                        <div class="u-inline-b9bbe540d3">
                            <input class="u-inline-7623f05545" type="text" name="items[__INDEX__][url]">
                            <button type="button" class="btn btn--secondary btn--small" data-media-pick data-media-target="[name='items[__INDEX__][url]']" data-media-type="all_files">Выбрать</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="items"><?= \App\Core\AdminUi::icon('plus') ?>Добавить документ</button></div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'map_point'): ?>
            <div class="form-field">
                <label for="embed_url">Карта (URL встраивания или HTML-код &lt;iframe&gt;)</label>
                <input type="text" id="embed_url" name="embed_url" value="<?= htmlspecialchars($data['embed_url'] ?? '', ENT_QUOTES) ?>" placeholder="https://www.google.com/maps/embed?... или <iframe src=...>">
                <small class="form-help">Поддерживаются Google Карты, Яндекс Карты и OSM. Можно вставить как ссылку встраивания, так и весь HTML-код &lt;iframe&gt;.</small>
            </div>
            <?= \App\Core\AdminUi::imageField('image', $data['image'] ?? '', ['label' => 'Изображение-заставка карты', 'hint' => 'Показывается до загрузки интерактивной карты.']) ?>
            <div class="form-field">
                <label for="load_mode">Загрузка интерактивной карты</label>
                <select id="load_mode" name="load_mode">
                    <option value="click" <?= ($data['load_mode'] ?? 'click') === 'click' ? 'selected' : '' ?>>После нажатия — быстрее и приватнее</option>
                    <option value="immediate" <?= ($data['load_mode'] ?? 'click') === 'immediate' ? 'selected' : '' ?>>Сразу при открытии страницы</option>
                </select>
            </div>
            <div class="form-field"><label for="card_title">Карточка на карте — заголовок</label><input type="text" id="card_title" name="card_title" value="<?= htmlspecialchars($data['card_title'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="address">Карточка — адрес (можно в 2 строки)</label><textarea id="address" name="address" rows="2"><?= htmlspecialchars($data['address'] ?? '', ENT_QUOTES) ?></textarea></div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="copy_enabled" name="copy_enabled" value="1" <?= (!array_key_exists('copy_enabled', $data) || !empty($data['copy_enabled'])) ? 'checked' : '' ?>>
                <label for="copy_enabled">Показывать кнопку «Скопировать адрес»</label>
            </div>
            <div class="form-field"><label for="button_text">Кнопка (напр. «Построить маршрут») — текст</label><input type="text" id="button_text" name="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="button_url">Кнопка — ссылка</label><input type="text" id="button_url" name="button_url" value="<?= htmlspecialchars($data['button_url'] ?? '', ENT_QUOTES) ?>" placeholder="https://maps.google.com/?daddr=..."></div>
        <?php endif; ?>

        <?php if ($type === 'org_structure'): ?>
            <?php $orgLayout = ($data['layout'] ?? 'tree') === 'spine' ? 'spine' : 'tree'; ?>
            <?php $orgColumns = (int) ($data['columns'] ?? 4); ?>
            <p class="form-hint">
                В списках подразделений и органов работает разметка строк:
                <code>Название | /ссылка</code> — пункт-ссылка,
                <code>* Название</code> — выделенный пункт (проектный офис),
                <code>- Название</code> — вложенная группа внутри предыдущего пункта.
            </p>
            <?php if (($departments ?? []) !== []): ?>
                <p class="form-hint">
                    Ссылки на состав сектора в разделе «Команда» — скопируйте адрес страницы команды и добавьте якорь:
                    <?php foreach ($departments as $dep): ?>
                        <code><?= htmlspecialchars((string) $dep['name'], ENT_QUOTES) ?> | /komanda#team-<?= htmlspecialchars((string) $dep['slug'], ENT_QUOTES) ?></code>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
            <div class="form-field">
                <label for="layout">Макет схемы</label>
                <select id="layout" name="layout">
                    <option value="tree" <?= $orgLayout === 'tree' ? 'selected' : '' ?>>Дерево (колонки с соединителями)</option>
                    <option value="spine" <?= $orgLayout === 'spine' ? 'selected' : '' ?>>Компактный список (для больших структур)</option>
                </select>
            </div>
            <div class="form-field">
                <label for="columns">Колонок в ряду (для макета «Дерево»)</label>
                <select id="columns" name="columns">
                    <?php foreach ([2, 3, 4] as $c): ?><option value="<?= $c ?>" <?= $orgColumns === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
                </select>
                <span class="form-hint">Лишние ветки переносятся на следующий ряд со своим соединителем.</span>
            </div>
            <div class="form-field">
                <label for="council">Коллегиальный орган над руководителем (по одному на строку)</label>
                <textarea id="council" name="council" rows="2" placeholder="Координационный совет"><?= htmlspecialchars($data['council'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field"><label for="head_title">Руководитель — должность</label><input type="text" id="head_title" name="head_title" value="<?= htmlspecialchars($data['head_title'] ?? 'Директор', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="head_name">Руководитель — Ф.И.О. (необязательно)</label><input type="text" id="head_name" name="head_name" value="<?= htmlspecialchars($data['head_name'] ?? '', ENT_QUOTES) ?>"></div>
            <div class="form-field"><label for="head_url">Руководитель — ссылка (напр. на страницу директора)</label><input type="text" id="head_url" name="head_url" value="<?= htmlspecialchars($data['head_url'] ?? '', ENT_QUOTES) ?>" placeholder="/direktor"></div>
            <div class="form-field">
                <label for="side_items">Органы сбоку от руководителя (по одному на строку)</label>
                <textarea id="side_items" name="side_items" rows="3" placeholder="Советник"><?= htmlspecialchars($data['side_items'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <?php
            // Готовые ссылки на состав сектора: адрес собирается по данным
            // команды, а не переписывается руками (переименование сектора
            // раньше молча ломало ссылку).
            $teamAnchors = \App\Core\TeamAnchors::options();
            $sectorPicker = '';
            if ($teamAnchors !== []) {
                ob_start(); ?>
                <div class="form-field">
                    <label>Вставить ссылку на состав сектора</label>
                    <select data-org-sector-insert>
                        <option value="">— выберите сектор —</option>
                        <?php foreach ($teamAnchors as $anchor): ?>
                            <option value="<?= htmlspecialchars($anchor['name'] . ' | ' . $anchor['path'], ENT_QUOTES) ?>" <?= $anchor['path'] === '' ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($anchor['name'], ENT_QUOTES) ?> (<?= (int) $anchor['count'] ?>)<?= $anchor['path'] === '' ? ' — нет страницы с блоком «Команда»' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-hint">Строка добавится в поле выше. Секторы берутся из карточек сотрудников, адрес — из страницы с блоком «Команда» и включённой группировкой.</span>
                </div>
                <?php $sectorPicker = (string) ob_get_clean();
            }
            ?>
            <div>
                <label>Ветки (заместители / блоки подразделений)</label>
                <span class="form-hint">Ветку можно оставить без должности — тогда подразделения подчиняются руководителю напрямую.</span>
                <div data-repeater="branches">
                    <?php foreach (($data['branches'] ?? []) as $i => $branch): ?>
                        <div class="repeater-row">
                            <div class="form-field"><label>Должность</label><input type="text" name="branches[<?= $i ?>][title]" value="<?= htmlspecialchars($branch['title'] ?? '', ENT_QUOTES) ?>" placeholder="Первый заместитель директора"></div>
                            <div class="form-field"><label>Ф.И.О. (необязательно)</label><input type="text" name="branches[<?= $i ?>][name]" value="<?= htmlspecialchars($branch['name'] ?? '', ENT_QUOTES) ?>"></div>
                            <div class="form-field"><label>Ссылка на профиль (необязательно)</label><input type="text" name="branches[<?= $i ?>][url]" value="<?= htmlspecialchars($branch['url'] ?? '', ENT_QUOTES) ?>" placeholder="/rukovodstvo/..."></div>
                            <div class="form-field"><label>Подразделения (по одному на строку)</label><textarea name="branches[<?= $i ?>][units]" rows="5" placeholder="Отдел стратегического планирования&#10;  Сектор прогнозов&#10;Отдел анализа и мониторинга"><?= htmlspecialchars($branch['units'] ?? '', ENT_QUOTES) ?></textarea><?= $sectorPicker ?><span class="form-hint">Отступ в начале строки = уровень вложенности (до четырёх): «Департамент», под ним с отступом «Отдел», ещё глубже «Сектор». <code>| /адрес</code> — ссылка, <code>*</code> в начале — акцентный пункт.</span></div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить ветку</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="branches">
                    <div class="form-field"><label>Должность</label><input type="text" name="branches[__INDEX__][title]" placeholder="Заместитель директора"></div>
                    <div class="form-field"><label>Ф.И.О. (необязательно)</label><input type="text" name="branches[__INDEX__][name]"></div>
                    <div class="form-field"><label>Ссылка на профиль (необязательно)</label><input type="text" name="branches[__INDEX__][url]" placeholder="/rukovodstvo/..."></div>
                    <div class="form-field"><label>Подразделения (по одному на строку)</label><textarea name="branches[__INDEX__][units]" rows="5" placeholder="Отдел стратегического планирования&#10;  Сектор прогнозов"></textarea><?= $sectorPicker ?><span class="form-hint">Отступ в начале строки = уровень вложенности (до четырёх): «Департамент», под ним с отступом «Отдел», ещё глубже «Сектор». <code>| /адрес</code> — ссылка, <code>*</code> в начале — акцентный пункт.</span></div>
                    <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove>Удалить ветку</button>
                </template>
                <div class="repeater-actions"><button type="button" class="btn btn--small" data-repeater-add="branches"><?= \App\Core\AdminUi::icon('plus') ?>Добавить ветку</button></div>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="collapsible" name="collapsible" value="1" <?= !empty($data['collapsible']) ? 'checked' : '' ?>>
                <label for="collapsible">Сворачивать подразделения на всех экранах (на телефоне схема сворачивается всегда)</label>
            </div>
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="org_search" name="org_search" value="1" <?= !empty($data['search']) ? 'checked' : '' ?>>
                <label for="org_search">Поиск по схеме (поле над схемой; полезно, когда узлов больше трёх десятков)</label>
            </div>
            <div class="form-field">
                <label for="notes">Примечания карточками под схемой (по одному на строку)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Отделы четвёртой колонки подчиняются директору напрямую"><?= htmlspecialchars($data['notes'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
            <div class="form-field"><label for="footnote">Примечание под схемой (необязательно)</label><input type="text" id="footnote" name="footnote" value="<?= htmlspecialchars($data['footnote'] ?? '', ENT_QUOTES) ?>" placeholder="Структура утверждена постановлением…"></div>
        <?php endif; ?>

        <?php // Общие поля оформления свёрнуты: контент-поля — основная задача,
              // а отступы/фон/анимация нужны эпизодически (значения внутри
              // закрытого details всё равно отправляются с формой). ?>
        <details class="form-section">
            <summary>Оформление секции <span class="form-section__hint">отступы, фон, анимация появления</span></summary>
            <div class="form-section__body">
        <?php $spacing = $data['_spacing'] ?? 'premium'; ?>
        <div class="form-field">
            <label for="spacing">Вертикальные отступы («воздух»)</label>
            <select id="spacing" name="spacing">
                <option value="none" <?= $spacing === 'none' ? 'selected' : '' ?>>Нет</option>
                <option value="small" <?= $spacing === 'small' ? 'selected' : '' ?>>Малый</option>
                <option value="premium" <?= $spacing === 'premium' ? 'selected' : '' ?>>Премиум</option>
                <option value="max" <?= $spacing === 'max' ? 'selected' : '' ?>>Максимальный</option>
            </select>
            <span class="form-hint">Адаптивные отступы через CSS clamp() — масштабируются под ширину экрана.</span>
        </div>

        <?php
        // Фон секции + полноширинная подложка + независимые отступы сверху/снизу.
        $bg = $data['_bg'] ?? 'none';
        $surface = $data['_surface'] ?? 'flat';
        $fullwidth = !empty($data['_fullwidth']);
        $padTop = $data['_pad_top'] ?? 'default';
        $padBottom = $data['_pad_bottom'] ?? 'default';
        $bgOpts = ['none' => 'Нет', 'light' => 'Светлый', 'tint' => 'Лёгкий акцент', 'navy' => 'Тёмный (navy)'];
        $surfaceOpts = ['flat' => 'Без карточки (прозрачный)', 'card' => 'Карточка с фоном'];
        $padOpts = ['default' => 'По умолчанию', 'none' => 'Нет', 'small' => 'Малый', 'medium' => 'Средний', 'large' => 'Большой'];
        ?>
        <div class="form-field">
            <label for="bg">Фон секции</label>
            <select id="bg" name="bg">
                <?php foreach ($bgOpts as $v => $l): ?><option value="<?= $v ?>" <?= $bg === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
            <span class="form-hint">Пресеты темы. Ниже можно задать свою заливку — она отменяет пресет.</span>
        </div>

        <?php
        // Своя заливка секции: цвет, градиент, фотография или узор. Режим один:
        // два фона на секции дают кашу, и какой из них главный — не угадать.
        $bgMode = (string) ($data['_bg_mode'] ?? 'preset');
        $bgModeOpts = [
            'preset' => 'Пресет темы (как выше)',
            'color' => 'Свой цвет',
            'gradient' => 'Градиент',
            'image' => 'Фотография или плитка-узор',
            'pattern' => 'Встроенный узор',
        ];
        $bgPatterns = ['dots' => 'Точки', 'grid' => 'Сетка', 'diagonal' => 'Диагональ', 'emblem' => 'Гирих (эмблема)'];
        $bgRepeat = (string) ($data['_bg_repeat'] ?? 'cover');
        ?>
        <div class="form-field">
            <label for="bg_mode">Своя заливка</label>
            <select id="bg_mode" name="bg_mode">
                <?php foreach ($bgModeOpts as $v => $l): ?><option value="<?= $v ?>" <?= $bgMode === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <div data-bg-group="color pattern">
        <?= \App\Core\AdminUi::colorField('bg_color', $data['_bg_color'] ?? '', 'Цвет фона (для «Свой цвет» и подложки узора)', '#0f2b46', 'Не задан') ?>
        </div>
        <div data-bg-group="gradient">
        <div class="form-grid-2col">
            <?= \App\Core\AdminUi::colorField('bg_gradient_from', $data['_bg_gradient_from'] ?? '', 'Градиент: от', '#0f2b46', 'Не задан') ?>
            <?= \App\Core\AdminUi::colorField('bg_gradient_to', $data['_bg_gradient_to'] ?? '', 'Градиент: до', '#009bbe', 'Не задан') ?>
        </div>
        <div class="form-field">
            <label for="bg_gradient_angle">Градиент: угол (градусы)</label>
            <input type="number" id="bg_gradient_angle" name="bg_gradient_angle" min="0" max="360" step="5" value="<?= (int) ($data['_bg_gradient_angle'] ?? 135) ?>">
        </div>
        </div>
        <div data-bg-group="image">
        <?= \App\Core\AdminUi::imageField('bg_image', (string) ($data['_bg_image'] ?? ''), [
            'label' => 'Фон: изображение',
            'hint' => 'Фотография во всю секцию или небольшая плитка узора (PNG/SVG). Фон не грузится лениво — не ставьте тяжёлые файлы.',
        ]) ?>
        <div class="form-field">
            <label for="bg_repeat">Изображение: как показывать</label>
            <select id="bg_repeat" name="bg_repeat">
                <option value="cover" <?= $bgRepeat !== 'tile' ? 'selected' : '' ?>>Фотография — на всю секцию</option>
                <option value="tile" <?= $bgRepeat === 'tile' ? 'selected' : '' ?>>Плитка — повторять узором</option>
            </select>
        </div>
        <div class="form-field">
            <label for="bg_tile_size">Плитка: размер, px</label>
            <input type="number" id="bg_tile_size" name="bg_tile_size" min="16" max="600" step="4" value="<?= (int) ($data['_bg_tile_size'] ?? 120) ?>">
        </div>
        <div class="form-field">
            <label for="bg_position">Фотография: часть кадра</label>
            <select id="bg_position" name="bg_position">
                <?php foreach (\App\Core\MediaPosition::VALUES as $pos): ?>
                    <option value="<?= $pos ?>" <?= (string) ($data['_bg_position'] ?? 'center-center') === $pos ? 'selected' : '' ?>><?= $pos ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="bg_overlay">Фотография: затемнение, %</label>
            <input type="number" id="bg_overlay" name="bg_overlay" min="0" max="80" step="5" value="<?= (int) ($data['_bg_overlay'] ?? 45) ?>">
            <span class="form-hint">Без затемнения текст на светлом снимке читается через раз.</span>
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="bg_fixed" name="bg_fixed" value="1" <?= !empty($data['_bg_fixed']) ? 'checked' : '' ?>>
            <label for="bg_fixed">Фотография не двигается при прокрутке (только на компьютере)</label>
        </div>
        </div>
        <div data-bg-group="pattern">
        <div class="form-field">
            <label for="bg_pattern">Встроенный узор</label>
            <select id="bg_pattern" name="bg_pattern">
                <?php foreach ($bgPatterns as $v => $l): ?><option value="<?= $v ?>" <?= (string) ($data['_bg_pattern'] ?? 'dots') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-grid-2col">
            <?= \App\Core\AdminUi::colorField('bg_pattern_color', $data['_bg_pattern_color'] ?? '', 'Цвет узора', '#009bbe', 'Акцент сайта') ?>
            <div class="form-field">
                <label for="bg_pattern_size">Узор: шаг, px</label>
                <input type="number" id="bg_pattern_size" name="bg_pattern_size" min="8" max="240" step="2" value="<?= (int) ($data['_bg_pattern_size'] ?? 28) ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="bg_pattern_opacity">Узор: заметность, %</label>
            <input type="number" id="bg_pattern_opacity" name="bg_pattern_opacity" min="3" max="60" step="1" value="<?= (int) ($data['_bg_pattern_opacity'] ?? 22) ?>">
        </div>
        </div>
        <?php
        // Цвет текста — отдельно у секции и отдельно у вложенных карточек.
        // Настройка нужна при любом фоне (в том числе у пресета navy и у
        // секции без фона), поэтому она вне группы режимов фона.
        $bgTextScheme = \App\Core\SectionColors::textScheme($data);
        $bgCardScheme = \App\Core\SectionColors::cardScheme($data);
        ?>
        <div class="form-field">
            <label for="bg_text_scheme">Цвет текста секции</label>
            <select id="bg_text_scheme" name="bg_text_scheme">
                <?php foreach ([
                    'auto' => 'Как в теме',
                    'light' => 'Светлый — для тёмного фона и фотографий',
                    'dark' => 'Тёмный — для светлого фона',
                    'custom' => 'Свой цвет',
                ] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $bgTextScheme === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">Действует на весь текст секции — заголовки, описания, списки и ссылки, а не только на заголовок.</span>
        </div>
        <?= \App\Core\AdminUi::colorField('bg_text_color', (string) ($data['_bg_text_color'] ?? ''), 'Свой цвет текста', '#ffffff', 'Не задан') ?>
        <div class="form-field">
            <label for="bg_card_scheme">Цвет вложенных карточек</label>
            <select id="bg_card_scheme" name="bg_card_scheme">
                <?php foreach ([
                    'auto' => 'Своя схема карточки (рекомендуется)',
                    'light' => 'Светлые карточки с тёмным текстом',
                    'dark' => 'Тёмные карточки со светлым текстом',
                ] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $bgCardScheme === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">
                «Своя схема» — карточка не подчиняется цвету секции: белая карточка на тёмной
                секции сохраняет тёмный текст и остаётся читаемой. Остальные варианты нужны,
                когда карточки задуманы под цвет секции.
            </span>
        </div>
        <div data-bg-group="color gradient image pattern">
        <div class="form-field">
            <label for="watermark">Фоновая надпись секции</label>
            <input type="text" id="watermark" name="watermark" maxlength="120"
                   value="<?= htmlspecialchars((string) ($data['_watermark'] ?? ''), ENT_QUOTES) ?>"
                   placeholder="Например: TOP 5">
            <span class="form-hint">
                Крупное слово за содержимым секции — название раздела, цифра, аббревиатура.
                Диктор его не читает, кликам не мешает. Пусто — надписи нет.
            </span>
        </div>
        <div data-watermark-group>
        <div class="form-field">
            <label for="watermark_x">Привязка надписи по горизонтали</label>
            <select id="watermark_x" name="watermark_x">
                <?php $wmX = (string) ($data['_watermark_x'] ?? 'center'); ?>
                <?php foreach (['left' => 'К левому краю', 'center' => 'По центру', 'right' => 'К правому краю'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $wmX === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="watermark_y">Привязка надписи по вертикали</label>
            <select id="watermark_y" name="watermark_y">
                <?php $wmY = (string) ($data['_watermark_y'] ?? 'middle'); ?>
                <?php foreach (['top' => 'К верху', 'middle' => 'По центру', 'bottom' => 'К низу'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $wmY === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="watermark_size">Размер надписи, % ширины экрана</label>
            <input type="number" id="watermark_size" name="watermark_size" min="2" max="60" step="1"
                   value="<?= (int) ($data['_watermark_size'] ?? 22) ?>">
            <span class="form-hint">22 % — слово примерно в треть экрана. Считается от ширины окна, поэтому на телефоне уменьшается само.</span>
        </div>
        <div class="form-field">
            <label for="watermark_opacity">Заметность надписи, %</label>
            <input type="number" id="watermark_opacity" name="watermark_opacity" min="0" max="100" step="1"
                   value="<?= (int) ($data['_watermark_opacity'] ?? 12) ?>">
            <span class="form-hint">12 % — фон. Выше 30 % надпись начинает спорить с заголовком секции, а выше 50 % текст поверх её штрихов теряет требуемый контраст 4.5:1 (замерено).</span>
        </div>
        <div class="form-field">
            <label for="watermark_style">Начертание надписи</label>
            <select id="watermark_style" name="watermark_style">
                <?php $wmS = (string) ($data['_watermark_style'] ?? 'fill'); ?>
                <?php foreach (['fill' => 'Заливка', 'outline' => 'Только контур'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $wmS === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="watermark_font">Шрифт надписи</label>
            <select id="watermark_font" name="watermark_font">
                <?php $wmF = (string) ($data['_watermark_font'] ?? 'heading'); ?>
                <?php foreach (['heading' => 'Заголовочный', 'body' => 'Основной'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $wmF === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="watermark_stroke">Толщина контура, px</label>
            <input type="number" id="watermark_stroke" name="watermark_stroke" min="1" max="12" step="1"
                   value="<?= (int) ($data['_watermark_stroke'] ?? 2) ?>">
            <span class="form-hint">Действует только при начертании «Только контур».</span>
        </div>
        <?= \App\Core\AdminUi::colorField('watermark_color', (string) ($data['_watermark_color'] ?? ''), 'Цвет надписи') ?>
        <div class="form-field">
            <label for="watermark_dx">Сдвиг надписи вправо, %</label>
            <input type="number" id="watermark_dx" name="watermark_dx" min="-100" max="100" step="1"
                   value="<?= (int) ($data['_watermark_dx'] ?? 0) ?>">
            <span class="form-hint">Отрицательное — влево. Процент от самой надписи, а не от экрана.</span>
        </div>
        <div class="form-field">
            <label for="watermark_dy">Сдвиг надписи вниз, %</label>
            <input type="number" id="watermark_dy" name="watermark_dy" min="-100" max="100" step="1"
                   value="<?= (int) ($data['_watermark_dy'] ?? 0) ?>">
            <span class="form-hint">Отрицательное — вверх.</span>
        </div>
        </div>
        <div class="form-field">
            <label for="min_height">Минимальная высота секции</label>
            <select id="min_height" name="min_height">
                <?php $minH = (string) ($data['_min_height'] ?? ''); ?>
                <?php foreach (['' => 'По содержимому', 'small' => 'Небольшая (320px)', 'medium' => 'Средняя (480px)', 'large' => 'Крупная (640px)', 'screen' => 'На весь экран'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $minH === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">Короткая секция обрезает фотографию-фон до полоски — здесь задаётся минимум.</span>
        </div>
        </div>
        <div class="form-field">
            <label for="surface">Тип контейнера секции</label>
            <select id="surface" name="surface">
                <?php foreach ($surfaceOpts as $v => $l): ?><option value="<?= $v ?>" <?= $surface === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
            <span class="form-hint">Карточка добавляет локальный фон, рамку и внутренние поля; фон всей секции настраивается отдельно выше.</span>
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="fullwidth" name="fullwidth" value="1" <?= $fullwidth ? 'checked' : '' ?>>
            <label for="fullwidth">Фон во всю ширину экрана (контент остаётся по центру)</label>
        </div>
        <div class="form-field">
            <label for="pad_top">Отступ сверху</label>
            <select id="pad_top" name="pad_top">
                <?php foreach ($padOpts as $v => $l): ?><option value="<?= $v ?>" <?= $padTop === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="pad_bottom">Отступ снизу</label>
            <select id="pad_bottom" name="pad_bottom">
                <?php foreach ($padOpts as $v => $l): ?><option value="<?= $v ?>" <?= $padBottom === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php
        // Тип анимации появления (группа 4.2). Обратная совместимость: старое
        // булево _reveal=true трактуем как {enabled:true, type:'fade'}.
        $revealRaw = $data['_reveal'] ?? null;
        if (is_array($revealRaw)) {
            $revealEnabled = !empty($revealRaw['enabled']);
            $revealType = (string) ($revealRaw['type'] ?? 'fade');
        } else {
            $revealEnabled = !empty($revealRaw);
            $revealType = 'fade';
        }
        $revealCurrent = $revealEnabled ? $revealType : '';
        $revealOptions = [
            '' => 'Без анимации',
            'fade' => 'Плавное появление',
            'slide-up' => 'Выезд снизу',
            'slide-left' => 'Выезд слева',
            'slide-right' => 'Выезд справа',
            'zoom-in' => 'Увеличение',
            'stagger' => 'Карточки по очереди',
        ];
        ?>
        <div class="form-field">
            <label for="reveal_type">Анимация появления при прокрутке</label>
            <select id="reveal_type" name="reveal_type">
                <?php foreach ($revealOptions as $rv => $rl): ?>
                    <option value="<?= htmlspecialchars($rv, ENT_QUOTES) ?>" <?= $revealCurrent === $rv ? 'selected' : '' ?>><?= htmlspecialchars($rl, ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
            </div>
        </details>

        <?php
        // Условия показа: расписание считается на сервере (блока просто нет в
        // HTML вне окна), устройство — на CSS (кэш страницы общий для всех).
        $vis = \App\Core\BlockVisibility::class;
        $visFrom = $vis::forInput($data['_visible_from'] ?? '');
        $visTo = $vis::forInput($data['_visible_to'] ?? '');
        $visDevice = (string) ($data['_visible_device'] ?? '');
        $visLabel = $vis::label($data);
        ?>
        <details class="form-section"<?= $vis::hasConditions($data) ? ' open' : '' ?>>
            <summary>Условия показа <span class="form-section__hint"><?= $visLabel !== '' ? htmlspecialchars($visLabel, ENT_QUOTES) : 'даты показа, устройство' ?></span></summary>
            <div class="form-section__body">
        <div class="form-field">
            <label for="visible_from">Показывать с</label>
            <input type="datetime-local" id="visible_from" name="visible_from" value="<?= htmlspecialchars($visFrom, ENT_QUOTES) ?>">
            <span class="form-hint">Пусто — показывать сразу.</span>
        </div>
        <div class="form-field">
            <label for="visible_to">Показывать до</label>
            <input type="datetime-local" id="visible_to" name="visible_to" value="<?= htmlspecialchars($visTo, ENT_QUOTES) ?>">
            <span class="form-hint">Пусто — показывать бессрочно. В указанный момент блок исчезнет сам, без правки страницы.</span>
        </div>
        <div class="form-field">
            <label for="visible_device">Устройства</label>
            <select id="visible_device" name="visible_device">
                <option value="" <?= $visDevice === '' ? 'selected' : '' ?>>Все</option>
                <option value="desktop" <?= $visDevice === 'desktop' ? 'selected' : '' ?>>Только десктоп</option>
                <option value="mobile" <?= $visDevice === 'mobile' ? 'selected' : '' ?>>Только мобильные</option>
            </select>
            <span class="form-hint">Скрытие по устройству делается стилями: блок остаётся в HTML, но не отображается.</span>
        </div>
            </div>
        </details>

        <?php if (\App\Core\Auth::isSuperAdmin()): ?>
        <details class="form-section">
            <summary>Дополнительно <span class="form-section__hint">собственный CSS блока</span></summary>
            <div class="form-section__body">
        <div class="form-field">
            <label for="custom_css">Собственный CSS блока</label>
            <textarea class="u-inline-bf60e927c2" id="custom_css" name="custom_css"><?= htmlspecialchars($block['custom_css'] ?? '', ENT_QUOTES) ?></textarea>
            <span class="form-hint">
                Стили автоматически изолируются: любой селектор при выводе на сайте получает префикс
                <code>#block-<?= (int) $block['id'] ?></code>, поэтому не может повлиять на остальную страницу.
                Пример: <code>h2 { color: red; }</code> → <code>#block-<?= (int) $block['id'] ?> h2 { color: red; }</code>.
            </span>
        </div>
            </div>
        </details>
        <?php else: ?>
            <?php /* Редактор не может менять кастомный CSS — сохраняем прежнее значение. */ ?>
            <input type="hidden" name="custom_css" value="<?= htmlspecialchars($block['custom_css'] ?? '', ENT_QUOTES) ?>">
        <?php endif; ?>

        <div class="form-actions form-actions--sticky">
            <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить блок</button>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>" class="btn">Отмена</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

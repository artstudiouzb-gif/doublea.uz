<?php

use App\Core\AdminUi;
use App\Core\BlockVisibility;
use App\Core\Csrf;
use App\Models\Language;

/** @var array $hero */
/** @var array<string, mixed> $settings */
/** @var array $slide */
/** @var array<string, mixed> $data */
/** @var array<string, array<string, mixed>> $translations */

$heroId = (int) $hero['id'];
$slideId = (int) $slide['id'];
$pageTitle = 'Слайд обложки «' . $hero['name'] . '»';
$activeNav = 'heroes';
require __DIR__ . '/../layout/header.php';

$defaultCode = Language::defaultCode();
$translationLangs = array_values(array_filter(
    Language::active(),
    static fn (array $l): bool => (string) $l['code'] !== $defaultCode
));

$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES);

$select = static function (string $name, string $label, array $options, string $current, string $hint = '') use ($esc): string {
    $html = '<div class="form-field"><label for="slide_' . $name . '">' . $esc($label) . '</label>'
        . '<select id="slide_' . $name . '" name="' . $name . '">';
    foreach ($options as $value => $option) {
        $html .= '<option value="' . $esc($value) . '"' . ((string) $value === $current ? ' selected' : '') . '>'
            . $esc($option) . '</option>';
    }
    $html .= '</select>';
    if ($hint !== '') {
        $html .= '<span class="form-hint">' . $esc($hint) . '</span>';
    }

    return $html . '</div>';
};

$checkbox = static function (string $name, string $label, bool $checked, string $hint = '') use ($esc): string {
    $html = '<div class="form-field form-field--checkbox">'
        . '<input type="checkbox" id="slide_' . $name . '" name="' . $name . '" value="1"' . ($checked ? ' checked' : '') . '>'
        . '<label for="slide_' . $name . '">' . $esc($label) . '</label>';
    if ($hint !== '') {
        $html .= '<span class="form-hint">' . $esc($hint) . '</span>';
    }

    return $html . '</div>';
};

$inherit = ['' => 'Как у обложки'];
$posOptions = ['left' => 'Слева', 'center' => 'По центру', 'right' => 'Справа'];
$yOptions = ['top' => 'Сверху', 'center' => 'По центру', 'bottom' => 'Снизу'];
$sizeOptions = ['s' => 'Мелкий', 'm' => 'Средний', 'l' => 'Крупный', 'xl' => 'Очень крупный'];
$subtitleSizes = ['s' => 'Мелкий', 'm' => 'Средний', 'l' => 'Крупный'];
$ctaStyles = [
    'primary' => 'Основная (заливка акцентом)',
    'secondary' => 'Вторичная (светлая заливка)',
    'ghost' => 'Контурная',
    'link' => 'Ссылка',
];
?>
<p>
    <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn btn--small">← К обложке «<?= $esc($hero['name']) ?>»</a>
</p>

<form method="post" action="/admin/heroes/<?= $heroId ?>/slides/<?= $slideId ?>/update">
    <?= Csrf::field() ?>

    <div class="form-card">
        <?= AdminUi::cardHeader('Текст', 'typography') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="eyebrow">Надзаголовок</label>
                <input type="text" id="eyebrow" name="eyebrow" value="<?= $esc($data['eyebrow']) ?>" placeholder="Например: Национальная программа">
            </div>
            <div class="form-field">
                <label for="title">Заголовок</label>
                <input type="text" id="title" name="title" value="<?= $esc($data['title']) ?>">
            </div>
            <div class="form-field">
                <label for="subtitle">Описание</label>
                <textarea id="subtitle" name="subtitle" rows="3"><?= $esc($data['subtitle']) ?></textarea>
            </div>
        </div>

        <h3 class="form-subtitle">Фоновая надпись</h3>
        <p class="form-hint">
            Крупное слово за содержимым — приём с фирменных обложек: название программы
            или направления во всю ширину, еле заметное. Диктор его не читает, кликам
            не мешает. Пусто — надписи нет.
        </p>
        <div class="form-grid">
            <div class="form-field">
                <label for="watermark">Текст надписи</label>
                <input type="text" id="watermark" name="watermark" value="<?= $esc($data['watermark']) ?>"
                       maxlength="120" placeholder="Например: aerion">
                <span class="form-hint">Одно-два слова: надпись не переносится, длинную обрежет край экрана.</span>
            </div>
            <?= $select('watermark_x', 'Привязка по горизонтали', [
                'left' => 'К левому краю', 'center' => 'По центру', 'right' => 'К правому краю',
            ], (string) $data['watermark_x']) ?>
            <?= $select('watermark_y', 'Привязка по вертикали', [
                'top' => 'К верху', 'middle' => 'По центру', 'bottom' => 'К низу',
            ], (string) $data['watermark_y']) ?>
            <div class="form-field">
                <label for="watermark_size">Размер, % ширины экрана</label>
                <input type="number" id="watermark_size" name="watermark_size" min="2" max="60" step="1"
                       value="<?= (int) $data['watermark_size'] ?>">
                <span class="form-hint">22 % — слово примерно в треть экрана. Считается от ширины окна, поэтому на телефоне надпись уменьшается сама.</span>
            </div>
            <div class="form-field">
                <label for="watermark_opacity">Заметность, %</label>
                <input type="number" id="watermark_opacity" name="watermark_opacity" min="0" max="100" step="1"
                       value="<?= (int) $data['watermark_opacity'] ?>">
                <span class="form-hint">12 % — фон. Выше 30 % надпись начинает спорить с заголовком.</span>
            </div>
            <?= $select('watermark_style', 'Начертание', [
                'fill' => 'Заливка', 'outline' => 'Только контур',
            ], (string) $data['watermark_style']) ?>
            <?= $select('watermark_font', 'Шрифт надписи', [
                'heading' => 'Заголовочный', 'body' => 'Основной',
            ], (string) $data['watermark_font']) ?>
            <div class="form-field">
                <label for="watermark_stroke">Толщина контура, px</label>
                <input type="number" id="watermark_stroke" name="watermark_stroke" min="1" max="12" step="1"
                       value="<?= (int) $data['watermark_stroke'] ?>">
                <span class="form-hint">Действует только при начертании «Только контур».</span>
            </div>
            <?= AdminUi::colorField('watermark_color', (string) $data['watermark_color'], 'Цвет надписи') ?>
            <div class="form-field">
                <label for="watermark_dx">Сдвиг вправо, %</label>
                <input type="number" id="watermark_dx" name="watermark_dx" min="-100" max="100" step="1"
                       value="<?= (int) $data['watermark_dx'] ?>">
                <span class="form-hint">Отрицательное — влево. Процент от самой надписи, а не от экрана.</span>
            </div>
            <div class="form-field">
                <label for="watermark_dy">Сдвиг вниз, %</label>
                <input type="number" id="watermark_dy" name="watermark_dy" min="-100" max="100" step="1"
                       value="<?= (int) $data['watermark_dy'] ?>">
                <span class="form-hint">Отрицательное — вверх.</span>
            </div>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Фон слайда', 'photo') ?>
        <p class="form-hint">
            Фон — это подложка, а не содержимое: он не влияет на высоту обложки и не
            даёт горизонтальной прокрутки. Кадр-замена обязателен для видео: он же постер
            до старта, он же то, что увидит посетитель, если видео показать нельзя.
        </p>
        <div class="form-grid">
            <?= $select('media_type', 'Тип фона', [
                'none' => 'Без фона',
                'image' => 'Изображение',
                'video' => 'Загруженное видео (MP4)',
                'youtube' => 'Видео с YouTube',
            ], (string) $data['media_type']) ?>
            <?= AdminUi::imageField('image', (string) $data['image'], [
                'label' => 'Изображение (десктоп)',
                'hint' => 'Оно же кадр-замена, если поле «Кадр-замена» пустое.',
            ]) ?>
            <?= AdminUi::imageField('image_mobile', (string) $data['image_mobile'], [
                'label' => 'Изображение для телефона',
                'hint' => 'Отдельный кадр под узкий экран: широкая фотография там режется до полоски. Пусто — берётся десктопное.',
            ]) ?>
            <?= AdminUi::mediaPositionFields((string) $data['image_position'], (string) $data['image_position_mobile']) ?>
            <?= $select('image_fit', 'Режим отображения', [
                'cover' => 'Заполнить область (обрезать лишнее)',
                'contain' => 'Вписать целиком',
            ], (string) $data['image_fit']) ?>
            <div class="form-field">
                <label for="video_url">Видео MP4</label>
                <input type="text" id="video_url" name="video_url" value="<?= $esc($data['video_url']) ?>" placeholder="/uploads/public/hero.mp4">
                <span class="form-hint">Проигрывается без звука и по кругу; звуковой дорожкой в фоне пользоваться нельзя.</span>
            </div>
            <div class="form-field">
                <label for="video_mobile_url">Отдельное видео для телефона</label>
                <input type="text" id="video_mobile_url" name="video_mobile_url" value="<?= $esc($data['video_mobile_url']) ?>" placeholder="/uploads/public/hero-mobile.mp4">
            </div>
            <div class="form-field">
                <label for="youtube_url">Ссылка на YouTube</label>
                <input type="text" id="youtube_url" name="youtube_url" value="<?= $esc($data['youtube_url']) ?>" placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX">
                <span class="form-hint">
                    Подойдёт обычная ссылка (watch?v=…, youtu.be/…) — идентификатор ролика система выделит сама.
                    <?php if ($data['youtube_id'] !== ''): ?>
                        Сейчас распознан: <code><?= $esc($data['youtube_id']) ?></code>.
                    <?php endif; ?>
                </span>
            </div>
            <?= AdminUi::imageField('poster', (string) $data['poster'], [
                'label' => 'Кадр-замена (poster / fallback)',
                'hint' => 'Показывается до старта видео и вместо него, если автовоспроизведение запрещено, ролик недоступен, видео выключено на телефоне или посетитель включил «меньше движения».',
            ]) ?>
            <?= $select('mobile_media', 'Видео на телефоне', [
                'image' => 'Заменить изображением (рекомендуется)',
                'desktop' => 'Проигрывать то же видео',
                'mobile_video' => 'Проигрывать отдельное мобильное видео',
            ], (string) $data['mobile_media'],
                'Фоновое видео на мобильном трафике стоит дороже, чем даёт: по умолчанию телефону достаётся кадр-замена.') ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Кнопки', 'hand-click') ?>
        <div class="form-grid">
            <?= $checkbox('cta_enabled', 'Показывать основную кнопку', (bool) $data['cta_enabled']) ?>
            <div class="form-field">
                <label for="cta_text">Текст основной кнопки</label>
                <input type="text" id="cta_text" name="cta_text" value="<?= $esc($data['cta_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta_url">Ссылка основной кнопки</label>
                <input type="text" id="cta_url" name="cta_url" value="<?= $esc($data['cta_url']) ?>" placeholder="/about">
            </div>
            <?= $select('cta_style', 'Тип основной кнопки', $ctaStyles, (string) $data['cta_style']) ?>
            <?= AdminUi::iconField('cta_icon', (string) $data['cta_icon'], ['label' => 'Иконка основной кнопки']) ?>
            <?= AdminUi::imageField('cta_image', (string) $data['cta_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG, 20×20. Задана — используется вместо иконки набора. Цвет у картинки свой: под тип кнопки она не перекрашивается.',
            ]) ?>
            <?= $checkbox('cta_new_tab', 'Открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>

            <?= $checkbox('cta2_enabled', 'Показывать дополнительную кнопку', (bool) $data['cta2_enabled']) ?>
            <div class="form-field">
                <label for="cta2_text">Текст дополнительной кнопки</label>
                <input type="text" id="cta2_text" name="cta2_text" value="<?= $esc($data['cta2_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta2_url">Ссылка дополнительной кнопки</label>
                <input type="text" id="cta2_url" name="cta2_url" value="<?= $esc($data['cta2_url']) ?>">
            </div>
            <?= $select('cta2_style', 'Тип дополнительной кнопки', $ctaStyles, (string) $data['cta2_style']) ?>
            <?= AdminUi::iconField('cta2_icon', (string) $data['cta2_icon'], ['label' => 'Иконка дополнительной кнопки']) ?>
            <?= AdminUi::imageField('cta2_image', (string) $data['cta2_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG, 20×20. Задана — используется вместо иконки набора.',
            ]) ?>
            <?= $checkbox('cta2_new_tab', 'Открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Ссылка со всего слайда', 'link') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="link_url">Адрес</label>
                <input type="text" id="link_url" name="link_url" value="<?= $esc($data['link_url']) ?>">
                <span class="form-hint">Кликается весь слайд; кнопки при этом остаются самостоятельными ссылками.</span>
            </div>
            <?= $checkbox('link_new_tab', 'Открывать в новой вкладке', (bool) $data['link_new_tab']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Цвет и затемнение слайда', 'palette') ?>
        <p class="form-hint">Пустое значение означает «как у обложки» — так задаётся исключение для одного светлого кадра, не трогая остальные слайды.</p>
        <div class="form-grid">
            <?= $select('scheme', 'Цветовая схема', $inherit + [
                'light' => 'Light', 'dark' => 'Dark', 'navy' => 'Navy', 'custom' => 'Custom',
            ], (string) $data['scheme']) ?>
            <?= AdminUi::colorField('scheme_bg', (string) $data['scheme_bg'], 'Свой фон (Custom)', '#0b1a30', 'Как у обложки') ?>
            <?= AdminUi::colorField('scheme_text', (string) $data['scheme_text'], 'Свой цвет текста (Custom)', '#ffffff', 'Как у обложки') ?>
            <?= AdminUi::colorField('scheme_accent', (string) $data['scheme_accent'], 'Цвет основной кнопки', '#173a63', 'Как у обложки') ?>
            <?= $select('content_scheme', 'Цвет текста', $inherit + [
                'auto' => 'Auto — по фону и затемнению',
                'light' => 'Light — светлый текст',
                'dark' => 'Dark — тёмный текст',
            ], (string) $data['content_scheme']) ?>
            <?= $select('overlay', 'Затемнение', $inherit + [
                'none' => 'Нет', 'solid' => 'Сплошное', 'gradient' => 'Градиент',
            ], (string) $data['overlay']) ?>
            <?= AdminUi::colorField('overlay_color', (string) $data['overlay_color'], 'Цвет затемнения', '#0b1a30', 'Как у обложки') ?>
            <div class="form-field">
                <label for="overlay_opacity">Плотность затемнения, %</label>
                <input type="number" id="overlay_opacity" name="overlay_opacity" min="0" max="100"
                       value="<?= (int) $data['overlay_opacity'] >= 0 ? (int) $data['overlay_opacity'] : '' ?>" placeholder="как у обложки">
            </div>
            <?= $select('overlay_direction', 'Направление градиента', $inherit + [
                'auto' => 'Автоматически',
                'to_right' => 'Слева направо', 'to_left' => 'Справа налево',
                'to_bottom' => 'Сверху вниз', 'to_top' => 'Снизу вверх',
                'to_bottom_right' => 'В правый нижний угол', 'to_bottom_left' => 'В левый нижний угол',
                'to_top_right' => 'В правый верхний угол', 'to_top_left' => 'В левый верхний угол',
            ], (string) $data['overlay_direction']) ?>
            <?= $select('panel', 'Подложка под текстом', $inherit + ['on' => 'Показывать', 'off' => 'Не показывать'], (string) $data['panel']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Раскладка слайда', 'layout-align-left') ?>
        <div class="form-grid">
            <?= $select('text_position', 'Текст по горизонтали', $inherit + $posOptions, (string) $data['text_position']) ?>
            <?= $select('text_align_y', 'Текст по вертикали', $inherit + $yOptions, (string) $data['text_align_y']) ?>
            <?= $select('title_size', 'Размер заголовка', $inherit + $sizeOptions, (string) $data['title_size']) ?>
            <?= $select('subtitle_size', 'Размер описания', $inherit + $subtitleSizes, (string) $data['subtitle_size']) ?>
            <?= $select('text_position_mobile', 'Текст по горизонтали (телефон)', $inherit + $posOptions, (string) $data['text_position_mobile']) ?>
            <?= $select('text_align_y_mobile', 'Текст по вертикали (телефон)', $inherit + $yOptions, (string) $data['text_align_y_mobile']) ?>
            <?= $select('title_size_mobile', 'Размер заголовка (телефон)', $inherit + $sizeOptions, (string) $data['title_size_mobile']) ?>
            <?= $select('subtitle_size_mobile', 'Размер описания (телефон)', $inherit + $subtitleSizes, (string) $data['subtitle_size_mobile']) ?>
            <?php
            // Пусто — отступ берётся у обложки. Ноль здесь значимый: это
            // «прижать вплотную», а не «как у обложки».
            foreach ([
                'gap_art' => 'Отступ над картинкой, px',
                'gap_title' => 'Отступ над заголовком, px',
                'gap_subtitle' => 'Отступ над описанием, px',
                'gap_actions' => 'Отступ над кнопками, px',
            ] as $gapKey => $gapLabel): ?>
                <div class="form-field">
                    <label for="<?= $gapKey ?>"><?= htmlspecialchars($gapLabel, ENT_QUOTES) ?></label>
                    <input type="number" id="<?= $gapKey ?>" name="<?= $gapKey ?>" min="0" max="200" step="1"
                           value="<?= $data[$gapKey] === '' ? '' : (int) $data[$gapKey] ?>" placeholder="как у обложки">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Картинка поверх фона', 'sparkles') ?>
        <p class="form-hint">Эмблема, логотип программы, иллюстрация. Кладётся рядом с текстом, а не под него.</p>
        <div class="form-grid">
            <?= AdminUi::imageField('art_image', (string) $data['art_image'], ['label' => 'Картинка']) ?>
            <div class="form-field">
                <label for="art_alt">Описание картинки</label>
                <input type="text" id="art_alt" name="art_alt" value="<?= $esc($data['art_alt']) ?>" placeholder="например: Логотип программы «Цифровой Узбекистан»">
                <span class="form-hint">Пусто — картинка считается декоративной и скрывается от диктора. Для логотипа программы описание обязательно.</span>
            </div>
            <?= $select('art_position', 'Где показывать', ['above' => 'Над текстом', 'left' => 'Слева', 'right' => 'Справа'], (string) $data['art_position']) ?>
            <?= $select('art_size', 'Размер', ['small' => 'Маленькая', 'medium' => 'Средняя', 'large' => 'Крупная'], (string) $data['art_size']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Показ слайда', 'clock') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="duration">Длительность показа, секунд</label>
                <input type="number" id="duration" name="duration" min="0" max="120" step="1"
                       value="<?= (int) $data['duration'] > 0 ? (int) $data['duration'] : '' ?>"
                       placeholder="как у обложки">
                <span class="form-hint">
                    Пусто или 0 — слайд держится столько же, сколько остальные (интервал задан
                    в настройках обложки). Своё значение нужно там, где времени на чтение
                    требуется больше: длинный заголовок или плотная инфографика.
                </span>
            </div>
        </div>
        <p class="form-hint">Слайд вне окна показа вообще не попадает в разметку страницы. Кэш страницы пересобирается к границе окна автоматически.</p>
        <div class="form-grid">
            <div class="form-field">
                <label for="_visible_from">Показывать с</label>
                <input type="datetime-local" id="_visible_from" name="_visible_from" value="<?= $esc(BlockVisibility::forInput($data['_visible_from'])) ?>">
            </div>
            <div class="form-field">
                <label for="_visible_to">Показывать до</label>
                <input type="datetime-local" id="_visible_to" name="_visible_to" value="<?= $esc(BlockVisibility::forInput($data['_visible_to'])) ?>">
            </div>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Свой CSS-класс', 'code') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="css_class">Класс слайда</label>
                <input type="text" id="css_class" name="css_class" value="<?= $esc($data['css_class']) ?>"
                       placeholder="hero-programma-2030">
                <span class="form-hint">
                    Запас на оформление, которого нет в полях выше. Класс вешается на слайд,
                    а стили пишутся в поле «Свой CSS» у страницы: <code>.hero-programma-2030 .hero__title { … }</code>
                    — так достаётся и заголовок, и описание, и кнопки. Несколько классов — через пробел.
                    Допустимы латиница, цифры, дефис и подчёркивание; класс должен начинаться с буквы.
                </span>
            </div>
        </div>
    </div>

    <?php if ($translationLangs !== []): ?>
        <div class="form-card" id="translations">
            <?= AdminUi::cardHeader('Переводы текста', 'globe') ?>
            <p class="form-hint">
                Переводится только текст: медиа, цвета и раскладка у слайда общие для всех языков.
                Пустое поле — не ошибка: на этом языке покажется текст основного языка
                (<?= $esc(strtoupper($defaultCode)) ?>).
            </p>
            <?php foreach ($translationLangs as $language): ?>
                <?php
                $code = (string) $language['code'];
                $tr = $translations[$code] ?? [];
                $key = 'translations[' . $code . ']';
                $id = 'tr-' . preg_replace('/[^a-z0-9_-]/i', '', $code) . '-';
                ?>
                <fieldset class="form-grid">
                    <legend><?= $esc($language['name'] ?? strtoupper($code)) ?></legend>
                    <div class="form-field">
                        <label for="<?= $id ?>eyebrow">Надзаголовок</label>
                        <input type="text" id="<?= $id ?>eyebrow" name="<?= $key ?>[eyebrow]" value="<?= $esc($tr['eyebrow'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>title">Заголовок</label>
                        <input type="text" id="<?= $id ?>title" name="<?= $key ?>[title]" value="<?= $esc($tr['title'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>subtitle">Описание</label>
                        <textarea id="<?= $id ?>subtitle" name="<?= $key ?>[subtitle]" rows="3"><?= $esc($tr['subtitle'] ?? '') ?></textarea>
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>cta">Текст основной кнопки</label>
                        <input type="text" id="<?= $id ?>cta" name="<?= $key ?>[cta_text]" value="<?= $esc($tr['cta_text'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>cta2">Текст дополнительной кнопки</label>
                        <input type="text" id="<?= $id ?>cta2" name="<?= $key ?>[cta2_text]" value="<?= $esc($tr['cta2_text'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>art">Описание картинки поверх фона</label>
                        <input type="text" id="<?= $id ?>art" name="<?= $key ?>[art_alt]" value="<?= $esc($tr['art_alt'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>watermark">Фоновая надпись</label>
                        <input type="text" id="<?= $id ?>watermark" name="<?= $key ?>[watermark]" value="<?= $esc($tr['watermark'] ?? '') ?>" maxlength="120">
                    </div>
                </fieldset>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Сохранить слайд</button>
        <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn">К списку слайдов</a>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>

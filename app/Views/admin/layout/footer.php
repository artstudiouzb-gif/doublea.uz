    </main>
</div>

<div class="tabler-picker" data-icon-picker hidden role="dialog" aria-modal="true" aria-labelledby="tabler-picker-title">
    <div class="tabler-picker__dialog">
        <div class="tabler-picker__head">
            <div>
                <strong id="tabler-picker-title">Tabler Icons</strong>
                <span>Локальный каталог — без CDN</span>
            </div>
            <button type="button" class="btn btn--secondary" data-icon-picker-close aria-label="Закрыть">
                <?= \App\Core\Icon::render('x', 18) ?>
            </button>
        </div>
        <div class="tabler-picker__search">
            <?= \App\Core\Icon::render('search', 18) ?>
            <input type="search" data-icon-picker-search placeholder="Поиск: home, user, chart…" autocomplete="off">
        </div>
        <div class="tabler-picker__results" data-icon-picker-results aria-live="polite">
            <div class="tabler-picker__status">Каталог загружается…</div>
        </div>
        <div class="tabler-picker__foot">
            <span data-icon-picker-count></span>
            <button type="button" class="btn btn--secondary" data-icon-picker-empty>Без иконки</button>
        </div>
    </div>
</div>

<div class="media-modal" data-media-modal hidden role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
    <div class="media-modal__dialog">
        <div class="media-modal__head">
            <div class="media-modal__tabs">
                <button type="button" class="media-modal__tab is-active" data-media-tab="library">Библиотека файлов</button>
                <button type="button" class="media-modal__tab" data-media-tab="upload">Загрузить файлы</button>
            </div>
            <button type="button" class="media-modal__close" data-media-close aria-label="Закрыть"><?= \App\Core\Icon::render('x', 18) ?></button>
        </div>
        <div class="media-modal__toolbar" data-media-toolbar>
            <input type="search" class="media-modal__search" data-media-search placeholder="Поиск в медиабиблиотеке…">
        </div>
        <div class="media-modal__upload is-hidden" data-media-upload data-csrf="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES) ?>">
            <div class="media-modal__dropzone">
                <div class="u-inline-30220647a6">Перетащите файлы сюда</div>
                <div class="u-inline-a43690dc6d">или нажмите кнопку для выбора на диске (до 200 МБ)</div>
                <label class="btn btn--primary u-inline-42278569ee" data-media-upload-button>
                    <?= \App\Core\AdminUi::icon('plus', 16, 'btn__icon', 2.5) ?>
                    Выберите файл
                    <input class="u-inline-c8be1ccba6" type="file" data-media-upload-input>
                </label>
                <div class="media-modal__upload-status u-inline-d8a81eac84" data-media-upload-status aria-live="polite"></div>
            </div>
        </div>
        <div class="media-modal__grid" data-media-grid aria-busy="true">
            <div class="media-modal__empty">Загрузка…</div>
        </div>
        <div class="media-modal__footer">
            <div class="media-modal__selected-info" data-media-selected-info>Файл не выбран</div>
            <div class="media-modal__footer-actions">
                <button type="button" class="btn" data-media-close>Отмена</button>
                <button type="button" class="btn btn--primary" data-media-select-btn disabled>Выбрать</button>
            </div>
        </div>
    </div>
</div>

<?php
$isCardsGridEditor = isset($block)
    && is_array($block)
    && (string) ($block['type'] ?? '') === 'cards_grid'
    && isset($data)
    && is_array($data);
$cardsGridEditorStyle = $isCardsGridEditor
    ? (string) ($data['_cards_style'] ?? 'old')
    : 'old';
if (!in_array($cardsGridEditorStyle, ['old', 'new'], true)) {
    $cardsGridEditorStyle = 'old';
}
$cardsGridEditorIconSize = $isCardsGridEditor
    ? max(16, min(64, (int) ($data['_cards_icon_size'] ?? 22)))
    : 22;
$cardsGridEditorIconBackground = $isCardsGridEditor
    ? (string) ($data['_cards_icon_bg'] ?? 'on')
    : 'on';
if (!in_array($cardsGridEditorIconBackground, ['on', 'off'], true)) {
    $cardsGridEditorIconBackground = 'on';
}
$cardsGridEditorIconPosition = $isCardsGridEditor
    ? (string) ($data['_cards_icon_position'] ?? 'top')
    : 'top';
if (!in_array($cardsGridEditorIconPosition, ['top', 'left', 'right', 'center'], true)) {
    $cardsGridEditorIconPosition = 'top';
}
$cardsGridEditorTextAlign = $isCardsGridEditor
    ? (string) ($data['_cards_text_align'] ?? 'left')
    : 'left';
if (!in_array($cardsGridEditorTextAlign, ['left', 'center', 'right'], true)) {
    $cardsGridEditorTextAlign = 'left';
}
?>
<script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
<?php if ($isCardsGridEditor): ?>
/* Настройки внешнего вида cards_grid добавляем только в редактор конкретного
   блока. На остальные карточки и страницы эти поля не распространяются. */
(function () {
    var variant = document.getElementById('cards_variant');
    if (!variant || document.getElementById('cards_style')) { return; }
    var anchor = variant.closest('.form-field');
    if (!anchor || !anchor.parentNode) { return; }

    var styleField = document.createElement('div');
    styleField.className = 'form-field';
    var styleLabel = document.createElement('label');
    styleLabel.setAttribute('for', 'cards_style');
    styleLabel.textContent = 'Стиль карточек';
    var styleSelect = document.createElement('select');
    styleSelect.id = 'cards_style';
    styleSelect.name = 'cards_style';
    [
        ['old', 'Старый — классические карточки'],
        ['new', 'Новый — редакционный стиль']
    ].forEach(function (item) {
        var option = document.createElement('option');
        option.value = item[0];
        option.textContent = item[1];
        styleSelect.appendChild(option);
    });
    styleSelect.value = <?= json_encode($cardsGridEditorStyle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var styleHint = document.createElement('span');
    styleHint.className = 'form-hint';
    styleHint.textContent = 'Только для этого блока. Новый стиль — плоская редакционная подача «Основных направлений» без глобальной замены карточек сайта.';
    styleField.appendChild(styleLabel);
    styleField.appendChild(styleSelect);
    styleField.appendChild(styleHint);

    var sizeField = document.createElement('div');
    sizeField.className = 'form-field';
    var sizeLabel = document.createElement('label');
    sizeLabel.setAttribute('for', 'cards_icon_size');
    sizeLabel.textContent = 'Размер иконок, px';
    var sizeInput = document.createElement('input');
    sizeInput.type = 'number';
    sizeInput.id = 'cards_icon_size';
    sizeInput.name = 'cards_icon_size';
    sizeInput.min = '16';
    sizeInput.max = '64';
    sizeInput.step = '1';
    sizeInput.value = <?= (int) $cardsGridEditorIconSize ?>;
    var sizeHint = document.createElement('span');
    sizeHint.className = 'form-hint';
    sizeHint.textContent = 'Настройка действует только на иконки этого блока.';
    sizeField.appendChild(sizeLabel);
    sizeField.appendChild(sizeInput);
    sizeField.appendChild(sizeHint);

    var backgroundField = document.createElement('div');
    backgroundField.className = 'form-field';
    var backgroundLabel = document.createElement('label');
    backgroundLabel.setAttribute('for', 'cards_icon_bg');
    backgroundLabel.textContent = 'Фон иконок';
    var backgroundSelect = document.createElement('select');
    backgroundSelect.id = 'cards_icon_bg';
    backgroundSelect.name = 'cards_icon_bg';
    [
        ['on', 'С подложкой'],
        ['off', 'Без подложки']
    ].forEach(function (item) {
        var option = document.createElement('option');
        option.value = item[0];
        option.textContent = item[1];
        backgroundSelect.appendChild(option);
    });
    backgroundSelect.value = <?= json_encode($cardsGridEditorIconBackground, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var backgroundHint = document.createElement('span');
    backgroundHint.className = 'form-hint';
    backgroundHint.textContent = 'Без подложки иконка остаётся акцентной и при наведении.';
    backgroundField.appendChild(backgroundLabel);
    backgroundField.appendChild(backgroundSelect);
    backgroundField.appendChild(backgroundHint);

    var positionField = document.createElement('div');
    positionField.className = 'form-field';
    var positionLabel = document.createElement('label');
    positionLabel.setAttribute('for', 'cards_icon_position');
    positionLabel.textContent = 'Положение иконки';
    var positionSelect = document.createElement('select');
    positionSelect.id = 'cards_icon_position';
    positionSelect.name = 'cards_icon_position';
    [
        ['top', 'Сверху'],
        ['left', 'Слева от текста'],
        ['right', 'Справа от текста'],
        ['center', 'Сверху по центру']
    ].forEach(function (item) {
        var option = document.createElement('option');
        option.value = item[0];
        option.textContent = item[1];
        positionSelect.appendChild(option);
    });
    positionSelect.value = <?= json_encode($cardsGridEditorIconPosition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var positionHint = document.createElement('span');
    positionHint.className = 'form-hint';
    positionHint.textContent = 'Слева и справа — рядом с текстом; по центру — над заголовком.';
    positionField.appendChild(positionLabel);
    positionField.appendChild(positionSelect);
    positionField.appendChild(positionHint);

    var textAlignField = document.createElement('div');
    textAlignField.className = 'form-field';
    var textAlignLabel = document.createElement('label');
    textAlignLabel.setAttribute('for', 'cards_text_align');
    textAlignLabel.textContent = 'Выравнивание текста';
    var textAlignSelect = document.createElement('select');
    textAlignSelect.id = 'cards_text_align';
    textAlignSelect.name = 'cards_text_align';
    [
        ['left', 'Слева'],
        ['center', 'По центру'],
        ['right', 'Справа']
    ].forEach(function (item) {
        var option = document.createElement('option');
        option.value = item[0];
        option.textContent = item[1];
        textAlignSelect.appendChild(option);
    });
    textAlignSelect.value = <?= json_encode($cardsGridEditorTextAlign, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var textAlignHint = document.createElement('span');
    textAlignHint.className = 'form-hint';
    textAlignHint.textContent = 'Применяется к заголовку и описанию карточки независимо от положения иконки.';
    textAlignField.appendChild(textAlignLabel);
    textAlignField.appendChild(textAlignSelect);
    textAlignField.appendChild(textAlignHint);

    anchor.parentNode.insertBefore(styleField, anchor.nextSibling);
    anchor.parentNode.insertBefore(sizeField, styleField.nextSibling);
    anchor.parentNode.insertBefore(backgroundField, sizeField.nextSibling);
    anchor.parentNode.insertBefore(positionField, backgroundField.nextSibling);
    anchor.parentNode.insertBefore(textAlignField, positionField.nextSibling);

    function syncCardsGridControls() {
        var iconVariant = variant.value === 'icon';
        styleField.hidden = !iconVariant;
        sizeField.hidden = !iconVariant;
        backgroundField.hidden = !iconVariant;
        positionField.hidden = !iconVariant;
        textAlignField.hidden = !iconVariant;
    }
    variant.addEventListener('change', syncCardsGridControls);
    syncCardsGridControls();
})();
<?php endif; ?>

var stickyActions = Array.prototype.slice.call(document.querySelectorAll('.form-actions--sticky'));
if (stickyActions.length) {
    document.body.classList.add('has-sticky-actions');
    stickyActions.forEach(function (actions) {
        actions.setAttribute('role', 'toolbar');
        actions.setAttribute('aria-label', 'Действия формы');
        actions.classList.remove('is-context-hidden');
    });
}

/* Обратная связь при сохранении контента: сразу показываем процесс у нажатой
   кнопки, а после серверного redirect дублируем success-flash заметным toast. */
document.querySelectorAll('form[data-content-draft]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var submitter = event.submitter;
        window.setTimeout(function () {
            if (event.defaultPrevented || !submitter) { return; }
            submitter.dataset.originalHtml = submitter.innerHTML;
            submitter.classList.add('is-loading');
            submitter.setAttribute('aria-busy', 'true');
            submitter.textContent = 'Сохранение…';

            window.setTimeout(function () {
                if (!document.body.contains(submitter)) { return; }
                submitter.classList.remove('is-loading');
                submitter.removeAttribute('aria-busy');
                if (submitter.dataset.originalHtml) {
                    submitter.innerHTML = submitter.dataset.originalHtml;
                    delete submitter.dataset.originalHtml;
                }
            }, 15000);
        }, 0);
    });
});

window.addEventListener('DOMContentLoaded', function () {
    /* Сообщение показываем тостом при любом исходе, а не только при успехе.
       Раньше ошибку выводил только .alert наверху .admin-main, и она терялась:
       обработчики возвращают на якорь (например /admin/telegram#telegram-channel),
       браузер сразу уезжает к нужной секции — и сообщение остаётся выше экрана.
       Со стороны это выглядело как «кнопка не работает». */
    var alertBox = document.querySelector('.admin-main > .alert');
    if (!alertBox) { return; }

    var message = (alertBox.textContent || '').trim();
    if (!message) { return; }

    var isError = alertBox.classList.contains('alert--error');
    var kind = isError ? 'error' : (alertBox.classList.contains('alert--warning') ? 'warning' : 'success');

    var toast = document.createElement('div');
    toast.className = 'admin-toast-notification admin-toast--' + kind;
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
    var toastOkIcon = <?= json_encode(\App\Core\Icon::render('check', 16), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    /* У ошибки свой значок: галочка рядом с красным текстом читается как
       «получилось» и сбивает с толку сильнее, чем отсутствие значка вообще.
       Берём circle-x, а не alert-triangle: символы для спрайта собираются
       регуляркой по готовому HTML, а внутри JS-строки кавычки экранированы и
       она их не находит. Гарантированно попадают в спрайт только ключи из
       Icon::RUNTIME_ICONS — circle-x там есть. */
    var toastErrIcon = <?= json_encode(\App\Core\Icon::render('circle-x', 16), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var toastCloseIcon = <?= json_encode(\App\Core\Icon::render('x', 16), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    toast.innerHTML = '<div class="u-inline-7e30d285d2">'
        + '<span class="u-inline-4f1925a8a6" aria-hidden="true">' + (isError ? toastErrIcon : toastOkIcon) + '</span>'
        + '<span class="u-inline-94c3db5540"></span>'
        + '</div>'
        + '<button class="u-inline-d8c73d8aa0" type="button" aria-label="Закрыть уведомление">' + toastCloseIcon + '</button>';
    toast.querySelector('.u-inline-94c3db5540').textContent = message;
    toast.querySelector('button').addEventListener('click', function () { toast.remove(); });
    document.body.appendChild(toast);
    alertBox.remove();

    window.requestAnimationFrame(function () { toast.classList.add('is-visible'); });

    /* Ошибка висит, пока её не закроют: в ней написано, что именно сделать
       («добавьте бота администратором и напишите в канал»), и пятью секундами
       такое не прочитать. Успех гасим сам — там читать нечего. */
    if (isError) { return; }
    window.setTimeout(function () {
        toast.classList.remove('is-visible');
        window.setTimeout(function () { toast.remove(); }, 300);
    }, 6000);
});
/* Навигация админки: мобильная панель и запоминаемое сворачивание на десктопе. */
(function () {
    var t = document.querySelector('[data-sidebar-toggle]');
    var s = document.querySelector('[data-sidebar]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    var collapse = document.querySelector('[data-sidebar-collapse]');

    function setMobileOpen(open) {
        document.body.classList.toggle('sidebar-open', open);
        if (s) {
            var mobile = window.matchMedia('(max-width: 960px)').matches;
            s.inert = mobile && !open;
            if (mobile) s.setAttribute('aria-hidden', open ? 'false' : 'true');
            else s.removeAttribute('aria-hidden');
        }
        if (t) {
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
            t.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        }
    }

    function syncCollapsedState() {
        if (!collapse) return;
        var collapsed = document.documentElement.classList.contains('admin-nav-collapsed');
        collapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        collapse.setAttribute('title', collapsed ? 'Развернуть меню' : 'Свернуть меню');
        var label = collapse.querySelector('span');
        if (label) label.textContent = collapsed ? 'Развернуть меню' : 'Свернуть меню';
    }

    if (t && s) {
        setMobileOpen(false);
        t.addEventListener('click', function () {
            var opening = !document.body.classList.contains('sidebar-open');
            setMobileOpen(opening);
            if (opening) {
                var current = s.querySelector('[aria-current="page"]') || s.querySelector('.admin-nav-item');
                if (current) current.focus();
            }
        });
        s.addEventListener('click', function (e) {
            if (e.target.closest('.admin-nav-item') && window.matchMedia('(max-width: 960px)').matches) {
                setMobileOpen(false);
            }
        });
        if (backdrop) backdrop.addEventListener('click', function () { setMobileOpen(false); t.focus(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                setMobileOpen(false);
                t.focus();
            }
        });
        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 960px)').matches) setMobileOpen(false);
        });
    }

    if (collapse) {
        syncCollapsedState();
        collapse.addEventListener('click', function () {
            var collapsed = document.documentElement.classList.toggle('admin-nav-collapsed');
            try { localStorage.setItem('artstudio:admin-sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
            syncCollapsedState();
        });
    }
})();
</script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/vendor/editor.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/vendor/coloris/coloris.min.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/admin.js'), ENT_QUOTES) ?>"></script>
</body>
</html>

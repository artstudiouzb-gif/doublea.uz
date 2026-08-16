(function () {
    'use strict';

    var gallery = document.querySelector('[data-media-gallery]');
    var pickButton = gallery ? gallery.querySelector('[data-media-gallery-pick]') : null;
    if (!gallery || !pickButton || typeof window.fetch !== 'function') {
        return;
    }

    var csrfSource = document.querySelector('[data-media-upload][data-csrf]');
    var csrfToken = csrfSource ? csrfSource.getAttribute('data-csrf') : '';
    if (!csrfToken) {
        return;
    }

    // Прямая загрузка внутри формы была отложенной: файл существовал только в
    // браузере до первого сохранения новости. Отключаем этот путь и оставляем
    // единственный предсказуемый процесс: медиабиблиотека -> метаданные -> связь.
    var directInput = gallery.querySelector('input[name="news_gallery[]"]');
    if (directInput) {
        directInput.disabled = true;
        directInput.removeAttribute('name');
        directInput.hidden = true;
        directInput.setAttribute('aria-hidden', 'true');
        var directWrap = directInput.parentElement;
        var directHint = directWrap ? directWrap.querySelector('.form-hint') : null;
        if (directHint) {
            directHint.textContent = 'Фотографии сначала загружаются в медиабиблиотеку. Там заполните описание и источник, затем прикрепите их к новости.';
        }
    }
    var localPreview = gallery.querySelector('[data-file-preview-list]');
    if (localPreview) {
        localPreview.hidden = true;
    }

    var modal = document.createElement('div');
    modal.className = 'gallery-media-modal';
    modal.hidden = true;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'gallery-media-title');
    modal.innerHTML = ''
        + '<div class="gallery-media-modal__dialog">'
        + '  <div class="gallery-media-modal__head">'
        + '    <div>'
        + '      <h2 class="gallery-media-modal__title" id="gallery-media-title">Фотографии новости</h2>'
        + '      <div class="gallery-media-modal__tabs">'
        + '        <button type="button" class="gallery-media-modal__tab is-active" data-gallery-tab="library">Медиабиблиотека</button>'
        + '        <button type="button" class="gallery-media-modal__tab" data-gallery-tab="upload">Загрузить фотографии</button>'
        + '      </div>'
        + '    </div>'
        + '    <button type="button" class="btn btn--secondary btn--small" data-gallery-media-close aria-label="Закрыть">×</button>'
        + '  </div>'
        + '  <div class="gallery-media-modal__body">'
        + '    <div class="gallery-media-modal__browser" data-gallery-browser>'
        + '      <div class="gallery-media-modal__toolbar">'
        + '        <input type="search" class="gallery-media-modal__search" data-gallery-search placeholder="Поиск по имени, подписи или источнику…" autocomplete="off">'
        + '      </div>'
        + '      <div class="gallery-media-modal__grid" data-gallery-grid aria-live="polite"></div>'
        + '    </div>'
        + '    <div class="gallery-media-modal__upload" data-gallery-upload hidden>'
        + '      <div class="gallery-media-modal__dropzone" data-gallery-dropzone>'
        + '        <h3>Сначала загрузите в медиабиблиотеку</h3>'
        + '        <p>Можно выбрать несколько JPG, PNG, WebP, GIF или SVG. После загрузки окно не закроется: заполните метаданные и прикрепите фотографии.</p>'
        + '        <label class="btn btn--primary">Выбрать фотографии<input type="file" class="gallery-media-modal__upload-input" data-gallery-upload-input accept="image/*" multiple></label>'
        + '        <div class="gallery-media-modal__upload-status" data-gallery-upload-status aria-live="polite"></div>'
        + '      </div>'
        + '    </div>'
        + '    <aside class="gallery-media-modal__metadata" data-gallery-metadata>'
        + '      <div class="gallery-media-modal__preview" data-gallery-meta-preview><span>Выберите фотографию</span></div>'
        + '      <h3 class="gallery-media-modal__meta-title" data-gallery-meta-title>Метаданные</h3>'
        + '      <div class="gallery-media-modal__field"><label for="gallery_meta_alt">Alt-текст</label><input id="gallery_meta_alt" type="text" maxlength="255" data-gallery-meta="alt_text" placeholder="Что изображено на фотографии"></div>'
        + '      <div class="gallery-media-modal__field"><label for="gallery_meta_caption">Подпись под фотографией</label><input id="gallery_meta_caption" type="text" maxlength="255" data-gallery-meta="caption" placeholder="Краткая подпись для читателя"></div>'
        + '      <div class="gallery-media-modal__field"><label for="gallery_meta_description">Описание</label><textarea id="gallery_meta_description" maxlength="4000" data-gallery-meta="description" placeholder="Расширенное редакционное описание"></textarea></div>'
        + '      <div class="gallery-media-modal__field"><label for="gallery_meta_credit">Автор или источник</label><input id="gallery_meta_credit" type="text" maxlength="255" data-gallery-meta="credit" placeholder="Фото: пресс-служба Агентства"></div>'
        + '      <div class="gallery-media-modal__focal">'
        + '        <div class="gallery-media-modal__field"><label for="gallery_meta_fx">Фокус X, %</label><input id="gallery_meta_fx" type="number" min="0" max="100" data-gallery-meta="focal_x" placeholder="50"></div>'
        + '        <div class="gallery-media-modal__field"><label for="gallery_meta_fy">Фокус Y, %</label><input id="gallery_meta_fy" type="number" min="0" max="100" data-gallery-meta="focal_y" placeholder="50"></div>'
        + '      </div>'
        + '      <button type="button" class="btn btn--secondary" data-gallery-meta-save disabled>Сохранить метаданные</button>'
        + '      <div class="gallery-media-modal__meta-status" data-gallery-meta-status aria-live="polite"></div>'
        + '    </aside>'
        + '  </div>'
        + '  <div class="gallery-media-modal__foot">'
        + '    <strong data-gallery-selection-count>Выбрано: 0</strong>'
        + '    <div class="gallery-media-modal__actions">'
        + '      <button type="button" class="btn" data-gallery-media-close>Отмена</button>'
        + '      <button type="button" class="btn btn--primary" data-gallery-attach disabled>Прикрепить к новости</button>'
        + '    </div>'
        + '  </div>'
        + '</div>';
    document.body.appendChild(modal);

    var grid = modal.querySelector('[data-gallery-grid]');
    var browser = modal.querySelector('[data-gallery-browser]');
    var uploadPanel = modal.querySelector('[data-gallery-upload]');
    var search = modal.querySelector('[data-gallery-search]');
    var uploadInput = modal.querySelector('[data-gallery-upload-input]');
    var uploadStatus = modal.querySelector('[data-gallery-upload-status]');
    var dropzone = modal.querySelector('[data-gallery-dropzone]');
    var attachButton = modal.querySelector('[data-gallery-attach]');
    var selectionCount = modal.querySelector('[data-gallery-selection-count]');
    var saveButton = modal.querySelector('[data-gallery-meta-save]');
    var metaStatus = modal.querySelector('[data-gallery-meta-status]');
    var metaTitle = modal.querySelector('[data-gallery-meta-title]');
    var metaPreview = modal.querySelector('[data-gallery-meta-preview]');
    var metaFields = modal.querySelectorAll('[data-gallery-meta]');

    var items = [];
    var itemById = {};
    var selectedIds = new Set();
    var activeId = null;
    var dirty = false;
    var saving = null;
    var previouslyFocused = null;

    function safeUrl(value) {
        var url = String(value || '').trim();
        if (!/^\/(?!\/)/.test(url) && !/^https?:\/\//i.test(url)) {
            return '';
        }
        try {
            return encodeURI(decodeURI(url));
        } catch (error) {
            return '';
        }
    }

    function setStatus(node, message, state) {
        if (!node) { return; }
        node.textContent = message || '';
        node.classList.toggle('is-error', state === 'error');
        node.classList.toggle('is-success', state === 'success');
    }

    function normalizeItem(raw) {
        var url = safeUrl(raw && raw.url);
        var id = parseInt(raw && raw.id, 10);
        if (!url || !id) { return null; }
        return {
            id: id,
            url: url,
            name: String(raw.name || ''),
            mime_type: String(raw.mime_type || ''),
            alt_text: String(raw.alt_text || ''),
            caption: String(raw.caption || ''),
            description: String(raw.description || ''),
            credit: String(raw.credit || ''),
            focal_x: raw.focal_x === null || raw.focal_x === '' ? '' : String(raw.focal_x),
            focal_y: raw.focal_y === null || raw.focal_y === '' ? '' : String(raw.focal_y)
        };
    }

    function metadataCount(item) {
        var count = 0;
        ['alt_text', 'caption', 'description', 'credit', 'focal_x', 'focal_y'].forEach(function (key) {
            if (String(item[key] || '').trim() !== '') { count += 1; }
        });
        return count;
    }

    function updateSelectionUi() {
        var count = selectedIds.size;
        selectionCount.textContent = 'Выбрано: ' + count;
        attachButton.disabled = count === 0;
    }

    function renderCards() {
        var term = String(search.value || '').toLowerCase().trim();
        grid.textContent = '';
        var visible = 0;

        items.forEach(function (item) {
            var haystack = [item.name, item.alt_text, item.caption, item.description, item.credit].join(' ').toLowerCase();
            if (term && haystack.indexOf(term) === -1) { return; }
            visible += 1;

            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'gallery-media-card';
            card.classList.toggle('is-selected', selectedIds.has(item.id));
            card.classList.toggle('is-active', activeId === item.id);
            card.setAttribute('aria-pressed', selectedIds.has(item.id) ? 'true' : 'false');
            card.title = item.name;

            var image = document.createElement('img');
            image.className = 'gallery-media-card__image';
            image.src = item.url;
            image.alt = item.alt_text || '';
            image.loading = 'lazy';
            card.appendChild(image);

            var check = document.createElement('span');
            check.className = 'gallery-media-card__check';
            check.setAttribute('aria-hidden', 'true');
            check.textContent = '✓';
            card.appendChild(check);

            var body = document.createElement('span');
            body.className = 'gallery-media-card__body';
            var name = document.createElement('span');
            name.className = 'gallery-media-card__name';
            name.textContent = item.name;
            var meta = document.createElement('span');
            meta.className = 'gallery-media-card__meta';
            var count = metadataCount(item);
            meta.textContent = count ? 'Метаданные: ' + count + '/6' : 'Метаданные не заполнены';
            body.appendChild(name);
            body.appendChild(meta);
            card.appendChild(body);

            card.addEventListener('click', function () {
                if (selectedIds.has(item.id)) { selectedIds.delete(item.id); }
                else { selectedIds.add(item.id); }
                updateSelectionUi();
                activate(item.id);
                renderCards();
            });
            grid.appendChild(card);
        });

        if (!visible) {
            var empty = document.createElement('div');
            empty.className = 'gallery-media-modal__empty';
            empty.textContent = term ? 'По запросу ничего не найдено.' : 'В медиабиблиотеке пока нет фотографий.';
            grid.appendChild(empty);
        }
    }

    function populateMetadata(item) {
        activeId = item ? item.id : null;
        dirty = false;
        saveButton.disabled = !item;
        setStatus(metaStatus, '');
        metaTitle.textContent = item ? item.name : 'Метаданные';
        metaPreview.textContent = '';

        if (item) {
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.alt_text || '';
            metaPreview.appendChild(img);
        } else {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Выберите фотографию';
            metaPreview.appendChild(placeholder);
        }

        metaFields.forEach(function (field) {
            var key = field.getAttribute('data-gallery-meta');
            field.value = item ? String(item[key] || '') : '';
            field.disabled = !item;
        });
        renderCards();
    }

    function collectMetadata() {
        var data = {};
        metaFields.forEach(function (field) {
            data[field.getAttribute('data-gallery-meta')] = field.value;
        });
        return data;
    }

    function saveActive() {
        if (!activeId || !dirty) { return Promise.resolve(); }
        if (saving) { return saving; }
        var item = itemById[activeId];
        if (!item) { return Promise.resolve(); }

        var values = collectMetadata();
        var form = new FormData();
        form.append('csrf_token', csrfToken);
        form.append('metadata_id', String(item.id));
        Object.keys(values).forEach(function (key) { form.append(key, values[key]); });

        saveButton.disabled = true;
        setStatus(metaStatus, 'Сохранение…');
        saving = fetch('/admin/files/upload', {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.error || 'Ошибка сохранения');
                }
                return payload;
            });
        }).then(function (payload) {
            var updated = normalizeItem(payload.item);
            if (updated) {
                itemById[updated.id] = updated;
                items = items.map(function (candidate) { return candidate.id === updated.id ? updated : candidate; });
            }
            dirty = false;
            setStatus(metaStatus, 'Метаданные сохранены.', 'success');
            renderCards();
        }).catch(function (error) {
            setStatus(metaStatus, error.message || 'Не удалось сохранить метаданные.', 'error');
            throw error;
        }).finally(function () {
            saving = null;
            saveButton.disabled = !activeId;
        });

        return saving;
    }

    function activate(id) {
        var next = itemById[id] || null;
        if (activeId === id) {
            populateMetadata(next);
            return;
        }
        if (dirty) {
            saveActive().then(function () { populateMetadata(next); }).catch(function () {});
        } else {
            populateMetadata(next);
        }
    }

    function loadLibrary(selectIds) {
        grid.innerHTML = '<div class="gallery-media-modal__empty">Загрузка медиабиблиотеки…</div>';
        return fetch('/admin/media/list?type=image', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) { throw new Error('Не удалось загрузить медиабиблиотеку.'); }
                return response.json();
            })
            .then(function (payload) {
                items = [];
                itemById = {};
                (payload.items || []).forEach(function (raw) {
                    var item = normalizeItem(raw);
                    if (!item) { return; }
                    items.push(item);
                    itemById[item.id] = item;
                });
                (selectIds || []).forEach(function (id) {
                    id = parseInt(id, 10);
                    if (itemById[id]) { selectedIds.add(id); }
                });
                if (activeId && !itemById[activeId]) { activeId = null; }
                updateSelectionUi();
                renderCards();
                if (!activeId && selectedIds.size) {
                    activate(Array.from(selectedIds)[0]);
                }
            })
            .catch(function (error) {
                grid.innerHTML = '';
                var empty = document.createElement('div');
                empty.className = 'gallery-media-modal__empty';
                empty.textContent = error.message || 'Ошибка загрузки медиабиблиотеки.';
                grid.appendChild(empty);
            });
    }

    function setTab(tab) {
        modal.querySelectorAll('[data-gallery-tab]').forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-gallery-tab') === tab);
        });
        browser.hidden = tab !== 'library';
        uploadPanel.hidden = tab !== 'upload';
        modal.querySelector('[data-gallery-metadata]').hidden = tab !== 'library';
    }

    function randomUploadId() {
        var bytes = new Uint8Array(16);
        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(bytes);
            return Array.prototype.map.call(bytes, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
        }
        var id = '';
        for (var i = 0; i < 32; i += 1) { id += Math.floor(Math.random() * 16).toString(16); }
        return id;
    }

    function uploadOne(file, number, totalFiles) {
        if (!file || !file.size || file.size > 200 * 1024 * 1024 || !/^image\//i.test(file.type || '')) {
            return Promise.reject(new Error('Файл «' + (file ? file.name : '') + '» не является допустимым изображением.'));
        }
        var chunkSize = 1024 * 1024;
        var total = Math.ceil(file.size / chunkSize);
        var uploadId = randomUploadId();

        function send(index) {
            setStatus(uploadStatus, 'Файл ' + number + ' из ' + totalFiles + ': ' + file.name + ' — ' + Math.round((index / total) * 100) + '%');
            var form = new FormData();
            form.append('csrf_token', csrfToken);
            form.append('upload_id', uploadId);
            form.append('index', String(index));
            form.append('total', String(total));
            form.append('name', file.name);
            form.append('access_type', 'public');
            form.append('chunk', file.slice(index * chunkSize, Math.min((index + 1) * chunkSize, file.size)));

            return fetch('/admin/files/chunk', { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload.ok) { throw new Error(payload.error || 'Ошибка загрузки'); }
                        return payload;
                    });
                }).then(function (payload) {
                    if (payload.done) {
                        if (!/^image\//i.test(String(payload.mime_type || ''))) {
                            throw new Error('Сервер не распознал файл как изображение.');
                        }
                        return parseInt(payload.file_id, 10);
                    }
                    return send(index + 1);
                });
        }

        return send(0);
    }

    function uploadFiles(fileList) {
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) { return; }
        uploadInput.disabled = true;
        var uploadedIds = [];
        var chain = Promise.resolve();
        files.forEach(function (file, index) {
            chain = chain.then(function () {
                return uploadOne(file, index + 1, files.length).then(function (id) {
                    if (id) { uploadedIds.push(id); }
                });
            });
        });
        chain.then(function () {
            setStatus(uploadStatus, 'Загружено фотографий: ' + uploadedIds.length + '. Заполните метаданные.', 'success');
            setTab('library');
            return loadLibrary(uploadedIds);
        }).then(function () {
            if (uploadedIds.length) { activate(uploadedIds[0]); }
        }).catch(function (error) {
            setStatus(uploadStatus, error.message || 'Не удалось загрузить фотографии.', 'error');
        }).finally(function () {
            uploadInput.disabled = false;
            uploadInput.value = '';
        });
    }

    function addSelectionItem(item) {
        var selection = gallery.querySelector('[data-media-gallery-selection]');
        if (!selection || !item) { return; }
        var duplicate = Array.prototype.some.call(selection.querySelectorAll('input[name="gallery_library[]"]'), function (input) {
            return input.value === item.url;
        });
        if (duplicate) { return; }

        var wrapper = document.createElement('div');
        wrapper.className = 'file-preview__item';
        var image = document.createElement('img');
        image.src = item.url;
        image.alt = item.alt_text || '';
        wrapper.appendChild(image);

        var text = document.createElement('span');
        text.className = 'file-preview__name';
        text.textContent = item.caption || item.name;
        if (item.credit) { text.title = item.credit; }
        wrapper.appendChild(text);

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'gallery_library[]';
        input.value = item.url;
        wrapper.appendChild(input);

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'file-preview__drop';
        remove.setAttribute('data-media-gallery-drop', '');
        remove.setAttribute('aria-label', 'Убрать фотографию из выбора');
        remove.textContent = '×';
        wrapper.appendChild(remove);

        selection.appendChild(wrapper);
        selection.hidden = false;
    }

    function attachSelected() {
        saveActive().then(function () {
            selectedIds.forEach(function (id) { addSelectionItem(itemById[id]); });
            closeModal();
        }).catch(function () {});
    }

    function openModal() {
        previouslyFocused = document.activeElement;
        selectedIds.clear();
        activeId = null;
        dirty = false;
        search.value = '';
        populateMetadata(null);
        setTab('library');
        modal.hidden = false;
        document.body.classList.add('gallery-media-modal-open');

        var existingUrls = Array.prototype.map.call(
            gallery.querySelectorAll('input[name="gallery_library[]"]'),
            function (input) { return input.value; }
        );
        loadLibrary().then(function () {
            items.forEach(function (item) {
                if (existingUrls.indexOf(item.url) !== -1) { selectedIds.add(item.id); }
            });
            updateSelectionUi();
            renderCards();
            window.setTimeout(function () { search.focus(); }, 0);
        });
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('gallery-media-modal-open');
        setStatus(metaStatus, '');
        if (previouslyFocused && document.body.contains(previouslyFocused)) { previouslyFocused.focus(); }
    }

    metaFields.forEach(function (field) {
        field.disabled = true;
        field.addEventListener('input', function () {
            dirty = true;
            setStatus(metaStatus, 'Есть несохранённые изменения.');
        });
    });
    saveButton.addEventListener('click', function () { saveActive().catch(function () {}); });
    search.addEventListener('input', renderCards);
    uploadInput.addEventListener('change', function () { uploadFiles(uploadInput.files); });
    attachButton.addEventListener('click', attachSelected);

    modal.querySelectorAll('[data-gallery-tab]').forEach(function (button) {
        button.addEventListener('click', function () { setTab(button.getAttribute('data-gallery-tab')); });
    });
    modal.querySelectorAll('[data-gallery-media-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) { closeModal(); }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) { closeModal(); }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
            if (eventName === 'drop' && event.dataTransfer) { uploadFiles(event.dataTransfer.files); }
        });
    });

    // Перехватываем кнопку в capture-фазе раньше старого обработчика admin.js.
    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-media-gallery-pick]');
        if (!target || !gallery.contains(target)) { return; }
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        openModal();
    }, true);
}());

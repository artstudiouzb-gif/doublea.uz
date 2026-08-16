(function () {
    'use strict';

    /**
     * Блок «Вкладки»: превращает список ссылок в настоящие вкладки.
     *
     * С сервера разметка приходит рабочей — панели идут подряд со своими
     * заголовками, вкладки прокручивают к ним. Скрипт надстраивает поведение:
     * прячет неактивные панели, расставляет роли ARIA (объявлять их в статике
     * нельзя — обещали бы переключение, которого без скрипта не будет) и
     * включает управление стрелками, как того ждёт паттерн вкладок.
     */
    var init = function (root) {
        var list = root.querySelector('[data-tabs-list]');
        var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-tabs-tab]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-tabs-panel]'));
        if (!list || tabs.length < 2 || panels.length < 2) { return; }

        root.classList.add('is-enhanced');
        list.setAttribute('role', 'tablist');

        var select = function (key, focusTab) {
            tabs.forEach(function (tab) {
                var on = tab.getAttribute('data-tabs-tab') === key;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                // Неактивные вкладки выпадают из последовательности табуляции:
                // внутри группы переключение идёт стрелками.
                tab.setAttribute('tabindex', on ? '0' : '-1');
                if (on && focusTab) { tab.focus(); }
            });
            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-tabs-panel') !== key;
            });
        };

        tabs.forEach(function (tab, index) {
            var key = tab.getAttribute('data-tabs-tab');
            var panel = root.querySelector('[data-tabs-panel="' + key + '"]');
            if (!panel) { return; }

            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', panel.id);
            if (!tab.id) { tab.id = panel.id + '-tab'; }
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tab.id);
            // Панель со скрытым заголовком остаётся достижимой с клавиатуры:
            // иначе до содержимого вкладки не добраться без мыши.
            panel.setAttribute('tabindex', '0');

            tab.addEventListener('click', function (event) {
                event.preventDefault();
                select(key, false);
            });

            tab.addEventListener('keydown', function (event) {
                var step = event.key === 'ArrowRight' ? 1 : (event.key === 'ArrowLeft' ? -1 : 0);
                if (step === 0) { return; }
                event.preventDefault();
                var next = tabs[(index + step + tabs.length) % tabs.length];
                select(next.getAttribute('data-tabs-tab'), true);
            });
        });

        select(tabs[0].getAttribute('data-tabs-tab'), false);

        // Ссылка вида /страница#block-12-tab-2 обязана открыть нужную вкладку,
        // иначе поделиться разделом страницы невозможно.
        var fromHash = function () {
            var hash = window.location.hash.replace('#', '');
            if (!hash) { return; }
            var panel = root.querySelector('[data-tabs-panel]#' + (window.CSS && CSS.escape ? CSS.escape(hash) : hash));
            if (panel) { select(panel.getAttribute('data-tabs-panel'), false); }
        };
        fromHash();
        window.addEventListener('hashchange', fromHash);
    };

    var boot = function () {
        Array.prototype.forEach.call(document.querySelectorAll('[data-tabs]'), init);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

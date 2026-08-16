(() => {
    'use strict';

    const toggle = document.querySelector('[data-aa2-menu-toggle]');
    const nav = document.querySelector('[data-aa2-nav]');

    if (!(toggle instanceof HTMLButtonElement) || !(nav instanceof HTMLElement)) {
        return;
    }

    const close = () => {
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('is-open');
    };

    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
        nav.classList.toggle('is-open', !open);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });

    const desktop = window.matchMedia('(min-width: 1001px)');
    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            close();
        }
    });
})();

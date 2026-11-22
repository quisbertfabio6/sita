/* SITA: selector de apariencia. No interviene en sesiones ni formularios. */
(function () {
    'use strict';
    const key = 'sita-web-theme';
    const root = document.documentElement;

    function currentTheme() {
        return root.getAttribute('data-sita-theme') === 'dark' ? 'dark' : 'light';
    }

    function refreshButtons() {
        const dark = currentTheme() === 'dark';
        document.querySelectorAll('[data-sita-theme-toggle]').forEach(function (button) {
            const label = dark ? 'Activar modo claro' : 'Activar modo oscuro';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', String(dark));
            const icon = button.querySelector('[data-sita-theme-icon]');
            if (icon) {
                icon.classList.toggle('fa-sun', dark);
                icon.classList.toggle('fa-moon', !dark);
            }
            const text = button.querySelector('[data-sita-theme-text]');
            if (text) text.textContent = dark ? 'Modo claro' : 'Modo oscuro';
        });
    }

    function toggleTheme() {
        const next = currentTheme() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-sita-theme', next);
        try {
            window.localStorage.setItem(key, next);
        } catch (_) {
            // El cambio funciona durante la visita aunque el navegador bloquee el almacenamiento.
        }
        refreshButtons();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-sita-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', toggleTheme);
        });
        refreshButtons();
    });
})();

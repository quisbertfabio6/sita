/* SITA: selector de apariencia. No interviene en sesiones ni formularios. */
(function () {
    'use strict';
    const key = 'sita-web-theme';
    const root = document.documentElement;

    function currentTheme() {
        return root.getAttribute('data-sita-theme') === 'dark' ? 'dark' : 'light';
    }

    // Los textos de Chart.js se dibujan en canvas: CSS no puede cambiar su color.
    // Se actualizan al cargar la página y cada vez que se cambia la apariencia.
    function refreshCharts() {
        if (!window.Chart || !window.Chart.instances) return;

        const dark = currentTheme() === 'dark';
        const labelColor = dark ? '#d8e4f3' : '#64748b';
        const gridColor = dark ? 'rgba(148, 163, 184, 0.19)' : 'rgba(0, 0, 0, 0.07)';
        window.Chart.defaults.color = labelColor;

        Object.values(window.Chart.instances).forEach(function (chart) {
            try {
                chart.options.color = labelColor;

                if (chart.options.plugins && chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels =
                        chart.options.plugins.legend.labels || {};
                    chart.options.plugins.legend.labels.color = labelColor;
                }

                if (chart.options.scales) {
                    Object.values(chart.options.scales).forEach(function (axis) {
                        axis.ticks = axis.ticks || {};
                        axis.ticks.color = labelColor;
                        axis.grid = axis.grid || {};
                        if (axis.grid.display !== false) axis.grid.color = gridColor;
                    });
                }

                chart.update('none');
            } catch (_) {
                // Un gráfico externo no debe impedir el cambio de apariencia.
            }
        });
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
        refreshCharts();
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

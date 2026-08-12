/**
 * chart.js is only ever dynamically imported when #admin-sales-chart
 * actually exists on the page - same reasoning as media.js's FilePond
 * import (CLAUDE.md §13's GSAP/Lenis dynamic-import rule). It must be
 * imported from inside a bundled JS module, not a raw inline
 * <script type="module"> in Blade - the bare specifier 'chart.js/auto'
 * only resolves through Vite's dependency graph; the browser parses an
 * inline module tag directly with no bundler involved, so it throws
 * "Failed to resolve module specifier" there.
 */
function init() {
    const canvas = document.getElementById('admin-sales-chart');
    if (!canvas) return;

    import('chart.js/auto').then(({ default: Chart }) => {
        // Canvas 2D can't resolve CSS custom properties itself
        // (var(--color-primary) means nothing to strokeStyle) - the
        // computed value has to be read out as a real color string first.
        const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: JSON.parse(canvas.dataset.chartLabels),
                datasets: [{
                    data: JSON.parse(canvas.dataset.chartValues),
                    borderColor: primaryColor,
                    tension: 0.35,
                    fill: false,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    });
}

export default { init };

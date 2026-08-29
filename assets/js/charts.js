/* Chart.js wiring.
 *
 * A view supplies data as <script type="application/json" id="..."> and marks a
 * <canvas data-chart="line|doughnut" data-source="#id">. Charts redraw when the
 * theme flips so axis and grid colours stay readable. */
(function () {
    'use strict';

    if (typeof window.Chart === 'undefined') {
        return;
    }

    var live = [];
    var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function token(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    function palette() {
        return {
            text:   token('--txt-muted', '#94a3b8'),
            grid:   token('--line', '#1e293b'),
            panel:  token('--panel', '#0c0f16'),
            accent: token('--accent', '#575beb'),
            accent2: token('--accent-2', '#8b5cf6'),
            ok:     token('--ok', '#10b981'),
            warn:   token('--warn', '#f59e0b'),
            bad:    token('--bad', '#ef4444'),
            info:   token('--info', '#0ea5e9')
        };
    }

    function payload(canvas) {
        var node = document.querySelector(canvas.getAttribute('data-source'));
        if (! node) {
            return null;
        }
        try { return JSON.parse(node.textContent); } catch (e) { return null; }
    }

    function fill(ctx, from, to) {
        var g = ctx.createLinearGradient(0, 0, 0, 260);
        g.addColorStop(0, from);
        g.addColorStop(1, to);
        return g;
    }

    function lineChart(canvas, data, c) {
        return new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label || 'Earnings',
                    data: data.values,
                    borderColor: c.accent,
                    backgroundColor: fill(canvas.getContext('2d'), 'rgba(87, 91, 235, .28)', 'rgba(87, 91, 235, 0)'),
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: c.accent,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    tension: .38,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: calm ? false : { duration: 900, easing: 'easeOutCubic' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: c.panel,
                        borderColor: c.grid,
                        borderWidth: 1,
                        titleColor: token('--txt', '#f1f5f9'),
                        bodyColor: c.text,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function (item) {
                                return (data.prefix || '') + Number(item.parsed.y).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: c.grid },
                        ticks: { color: c.text, maxRotation: 0, autoSkipPadding: 18, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: c.grid },
                        border: { display: false },
                        ticks: {
                            color: c.text,
                            font: { size: 11 },
                            callback: function (v) { return (data.prefix || '') + v; }
                        }
                    }
                }
            }
        });
    }

    function doughnutChart(canvas, data, c) {
        var wheel = [c.accent, c.ok, c.warn, c.info, c.accent2, c.bad];

        return new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.labels.map(function (_, i) { return wheel[i % wheel.length]; }),
                    borderColor: c.panel,
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                animation: calm ? false : { duration: 900, easing: 'easeOutCubic' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: c.text,
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: c.panel,
                        borderColor: c.grid,
                        borderWidth: 1,
                        titleColor: token('--txt', '#f1f5f9'),
                        bodyColor: c.text,
                        padding: 10,
                        callbacks: {
                            label: function (item) {
                                return item.label + ': ' + (data.prefix || '') + Number(item.parsed).toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }

    function build() {
        live.forEach(function (chart) { chart.destroy(); });
        live = [];

        var c = palette();

        document.querySelectorAll('canvas[data-chart]').forEach(function (canvas) {
            var data = payload(canvas);
            if (! data || ! data.values || ! data.values.length) {
                return;
            }
            var kind = canvas.getAttribute('data-chart');
            live.push(kind === 'doughnut' ? doughnutChart(canvas, data, c) : lineChart(canvas, data, c));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }

    window.addEventListener('samrat:theme', build);
}());

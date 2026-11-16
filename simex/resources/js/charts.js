// Dashboard and report charts. Data arrives as JSON in <script type="application/json"> blocks.
import { Chart, BarController, BarElement, LineController, LineElement, PointElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Tooltip, Legend, Filler } from 'chart.js';

Chart.register(BarController, BarElement, LineController, LineElement, PointElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
const palette = ['#15181b', '#ffc40e', '#8a929b', '#17753f', '#1f5fbf', '#b3261e', '#b9bfc6', '#9a5b00'];
Chart.defaults.font.family = "'IBM Plex Sans', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = css('--text-3') || '#7a838d';
Chart.defaults.borderColor = css('--line') || '#dfe2e6';

document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    const source = document.getElementById(canvas.dataset.chart);
    if (!source) return;
    const cfg = JSON.parse(source.textContent);
    const dark = document.documentElement.dataset.theme === 'dark';
    const colors = dark ? ['#e9ecef', ...palette.slice(1)] : palette;
    const datasets = cfg.datasets.map((d, i) => ({
        ...d,
        backgroundColor: cfg.type === 'doughnut' ? colors : (d.color || colors[i % colors.length]),
        borderColor: cfg.type === 'line' ? (d.color || colors[i % colors.length]) : undefined,
        borderWidth: cfg.type === 'line' ? 2 : 0,
        borderRadius: cfg.type === 'bar' ? 2 : 0,
        tension: 0.25,
        pointRadius: 2,
        maxBarThickness: 34,
    }));
    new Chart(canvas, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: cfg.horizontal ? 'y' : 'x',
            plugins: { legend: { display: cfg.type === 'doughnut' || datasets.length > 1, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } },
            scales: cfg.type === 'doughnut' ? {} : { x: { grid: { display: Boolean(cfg.horizontal) }, stacked: Boolean(cfg.stacked), ticks: { precision: 0 } }, y: { beginAtZero: true, grid: { display: !cfg.horizontal }, stacked: Boolean(cfg.stacked), ticks: { precision: 0 } } },
        },
    });
});

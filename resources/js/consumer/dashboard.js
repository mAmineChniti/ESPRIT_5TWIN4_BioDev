/**
 * Charts for the consumer dashboard: energy per day and the eco grade mix of
 * what the consumer actually ate. Colours come from the theme tokens and are
 * refreshed when the theme changes.
 */
import { Chart } from 'chart.js';
import { axisTheme, gradeColour, readTheme, registerChart } from './theme';

function readJson(id) {
    const node = document.getElementById(id);

    if (!node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch {
        return null;
    }
}

function tint(colour) {
    return `color-mix(in srgb, ${colour} 18%, transparent)`;
}

function grid(colour) {
    return `color-mix(in srgb, ${colour} 60%, transparent)`;
}

export function renderConsumerCharts() {
    const data = readJson('consumer-dashboard-data');

    if (!data) {
        return;
    }

    const energyCanvas = document.getElementById('chart-energy');

    if (energyCanvas && Array.isArray(data.energy) && data.energy.length > 0) {
        const chart = new Chart(energyCanvas, {
            type: 'line',
            data: {
                labels: data.energy.map((point) => point.date),
                datasets: [
                    {
                        label: 'Calories (kcal)',
                        data: data.energy.map((point) => point.calories),
                        borderColor: [],
                        backgroundColor: [],
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: axisTheme(readTheme()),
                    y: { ...axisTheme(readTheme()), beginAtZero: true },
                },
                plugins: { legend: { display: false } },
            },
        });

        registerChart((theme) => {
            chart.data.datasets[0].borderColor = theme.primary;
            chart.data.datasets[0].backgroundColor = tint(theme.primary);
            chart.options.scales.x.ticks.color = theme.mutedForeground;
            chart.options.scales.x.grid.color = grid(theme.border);
            chart.options.scales.y.ticks.color = theme.mutedForeground;
            chart.options.scales.y.grid.color = grid(theme.border);
            chart.update('none');
        });
    }

    const gradeCanvas = document.getElementById('chart-grades');

    if (gradeCanvas && Array.isArray(data.grades) && data.grades.length > 0) {
        const chart = new Chart(gradeCanvas, {
            type: 'doughnut',
            data: {
                labels: data.grades.map((grade) => `Grade ${grade.grade}`),
                datasets: [
                    {
                        data: data.grades.map((grade) => grade.total),
                        backgroundColor: [],
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: readTheme().foreground, boxWidth: 12 },
                    },
                },
            },
        });

        registerChart((theme) => {
            chart.data.datasets[0].backgroundColor = data.grades.map((grade) =>
                gradeColour(grade.grade, theme)
            );
            chart.options.plugins.legend.labels.color = theme.foreground;
            chart.update('none');
        });
    }
}
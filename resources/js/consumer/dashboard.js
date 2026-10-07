/**
 * Charts for the consumer dashboard: energy per day and the eco grade mix of
 * what the consumer actually ate. Colours come from the theme tokens and are
 * refreshed when the theme changes.
 */
import { axisTheme, gradeColour, mountChart, readTheme } from './theme';

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

    // Read the tokens once and share them across both charts.
    const theme = readTheme();

    const energyCanvas = document.getElementById('chart-energy');

    if (energyCanvas && Array.isArray(data.energy) && data.energy.length > 0) {
        const energy = data.energy;

        mountChart(
            energyCanvas,
            {
                type: 'line',
                data: {
                    labels: energy.map((point) => point.date),
                    datasets: [
                        {
                            label: 'Calories (kcal)',
                            data: energy.map((point) => point.calories),
                            // Painted up front rather than left empty for the
                            // recolour callback to fill in.
                            borderColor: theme.primary,
                            backgroundColor: tint(theme.primary),
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
                        x: axisTheme(theme),
                        y: { ...axisTheme(theme), beginAtZero: true },
                    },
                    plugins: { legend: { display: false } },
                },
            },
            (next) => {
                chart.data.datasets[0].borderColor = next.primary;
                chart.data.datasets[0].backgroundColor = tint(next.primary);
                chart.options.scales.x.ticks.color = next.mutedForeground;
                chart.options.scales.x.grid.color = grid(next.border);
                chart.options.scales.y.ticks.color = next.mutedForeground;
                chart.options.scales.y.grid.color = grid(next.border);
                chart.update('none');
            }
        );
    }

    const gradeCanvas = document.getElementById('chart-grades');

    if (gradeCanvas && Array.isArray(data.grades) && data.grades.length > 0) {
        const grades = data.grades;

        mountChart(
            gradeCanvas,
            {
                type: 'doughnut',
                data: {
                    labels: grades.map((grade) => `Grade ${grade.grade}`),
                    datasets: [
                        {
                            data: grades.map((grade) => grade.total),
                            backgroundColor: grades.map((grade) => gradeColour(grade.grade, theme)),
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
                            labels: { color: theme.foreground, boxWidth: 12 },
                        },
                    },
                },
            },
            (next) => {
                chart.data.datasets[0].backgroundColor = grades.map((grade) =>
                    gradeColour(grade.grade, next)
                );
                chart.options.plugins.legend.labels.color = next.foreground;
                chart.update('none');
            }
        );
    }
}
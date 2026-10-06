/**
 * Interactive supply chain timeline.
 *
 * Renders a Gantt style horizontal bar chart where each stage occupies the time
 * span it actually took, so a consumer can see not just the order of the steps
 * but how long the product sat between them. Long unexplained gaps are visible
 * at a glance, which is the point of the chart.
 *
 * Colours come from the theme tokens and are refreshed when the theme changes.
 */
import { Chart } from 'chart.js';
import { axisTheme, readTheme, registerChart, stageColour } from './theme';

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

function formatDate(value) {
    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function renderTimeline() {
    const canvas = document.getElementById('trace-timeline');

    if (!canvas) {
        return;
    }

    const data = readJson('trace-timeline-data');

    if (!data || !Array.isArray(data.steps) || data.steps.length === 0) {
        const empty = document.getElementById('trace-timeline-empty');

        if (empty) {
            empty.classList.remove('hidden');
        }

        canvas.classList.add('hidden');

        return;
    }

    // Convert each step into an absolute [start, end] window measured in days
    // from the first recorded step.
    const origin = new Date(data.steps[0].date).getTime();
    const windows = data.steps.map((step) => {
        const start = Math.round((new Date(step.date).getTime() - origin) / 86400000);

        return { ...step, start, end: start + Math.max(step.daysInStage, 1) };
    });

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: windows.map((step) => step.stage),
            datasets: [
                {
                    label: 'Days in stage',
                    data: windows.map((step) => [step.start, step.end]),
                    backgroundColor: [],
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.6,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Days since the product was registered',
                        color: readTheme().mutedForeground,
                    },
                    ...axisTheme(readTheme()),
                },
                y: {
                    ticks: { color: readTheme().foreground },
                    grid: { display: false },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const step = windows[context.dataIndex];

                            return [
                                `Recorded ${formatDate(step.date)}`,
                                `By ${step.actor ?? 'unknown'}`,
                                step.notes ? `Note: ${step.notes}` : 'No note recorded',
                            ];
                        },
                    },
                },
            },
            onClick: (event, elements) => {
                if (elements.length === 0) {
                    return;
                }

                window.dispatchEvent(
                    new CustomEvent('trace-step-selected', {
                        detail: windows[elements[0].index],
                    })
                );
            },
        },
    });

    registerChart((theme) => {
        chart.data.datasets[0].backgroundColor = windows.map((step) => stageColour(step.stage, theme));
        chart.options.scales.x.ticks.color = theme.mutedForeground;
        chart.options.scales.x.grid.color = `color-mix(in srgb, ${theme.border} 60%, transparent)`;
        chart.options.scales.x.title.color = theme.mutedForeground;
        chart.options.scales.y.ticks.color = theme.foreground;
        chart.update('none');
    });
}
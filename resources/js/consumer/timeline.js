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
import { axisTheme, mountChart, readTheme, stageColour } from './theme';

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
        // The "nothing recorded" message is rendered server side; all that is
        // left to do here is keep the empty canvas out of the layout.
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

    // Read the tokens once per render. Each readTheme() walks the whole custom
    // property set through getComputedStyle, so calling it per option built a
    // surprising amount of layout work on every paint.
    const theme = readTheme();

    const chart = mountChart(
        canvas,
        {
            type: 'bar',
            data: {
                labels: windows.map((step) => step.stage),
                datasets: [
                    {
                        label: 'Days in stage',
                        data: windows.map((step) => [step.start, step.end]),
                        // Painted up front rather than left empty for the
                        // recolour callback to fill in.
                        backgroundColor: windows.map((step) => stageColour(step.stage, theme)),
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
                            color: theme.mutedForeground,
                        },
                        ...axisTheme(theme),
                    },
                    y: {
                        ticks: { color: theme.foreground },
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
        },
        (next) => {
            chart.data.datasets[0].backgroundColor = windows.map((step) => stageColour(step.stage, next));
            chart.options.scales.x.ticks.color = next.mutedForeground;
            chart.options.scales.x.grid.color = axisTheme(next).grid.color;
            chart.options.scales.x.title.color = next.mutedForeground;
            chart.options.scales.y.ticks.color = next.foreground;
            chart.update('none');
        }
    );

    return chart;
}
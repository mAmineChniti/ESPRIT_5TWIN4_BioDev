/**
 * Entry point for the consumer charts.
 *
 * Registers only the Chart.js pieces that are actually used so the bundle stays
 * as small as the feature allows, and boots each renderer once the DOM is ready.
 */
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineController,
    LineElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

import { renderTimeline } from './consumer/timeline';
import { renderConsumerCharts } from './consumer/dashboard';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineController,
    LineElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
);

document.addEventListener('DOMContentLoaded', () => {
    renderTimeline();
    renderConsumerCharts();
});
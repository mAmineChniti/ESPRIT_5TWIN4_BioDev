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

let booted = false;

function boot() {
    // The bundle can be evaluated more than once (a re-injected script tag, or
    // a client-side navigation back into the page). Rendering twice would try
    // to bind a second Chart to a canvas that already has one.
    if (booted) {
        return;
    }

    booted = true;

    renderTimeline();
    renderConsumerCharts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
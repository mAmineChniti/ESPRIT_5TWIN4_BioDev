import { Chart } from 'chart.js';

/**
 * Reads the theme tokens straight from CSS so the charts use exactly the same
 * colours as the rest of the UI, and follow the theme if it ever changes.
 */
const TOKENS = {
    background: '--background',
    foreground: '--foreground',
    muted: '--muted',
    mutedForeground: '--muted-foreground',
    border: '--border',
    primary: '--primary',
    primaryForeground: '--primary-foreground',
    secondary: '--secondary',
    secondaryForeground: '--secondary-foreground',
    destructive: '--destructive',
    destructiveForeground: '--destructive-foreground',
};

/**
 * @returns {Record<string, string>} token name -> hsl() colour
 */
export function readTheme() {
    const computed = getComputedStyle(document.documentElement);
    const theme = {};

    for (const [name, property] of Object.entries(TOKENS)) {
        const channels = computed.getPropertyValue(property).trim();

        // CanvasText is a system colour that adapts to light/dark, used only if
        // a token is ever missing.
        theme[name] = channels ? `hsl(${channels})` : 'CanvasText';
    }

    return theme;
}

/**
 * Grade colours mirror the eco badge: the two lowest grades read as "good"
 * (primary), the middle as a caution (secondary), the worst as bad
 * (destructive). Same mapping, one source of truth.
 */
export function gradeColour(grade, theme) {
    switch (String(grade).toUpperCase()) {
        case 'A':
        case 'B':
            return theme.primary;
        case 'C':
            return theme.secondary;
        default:
            return theme.destructive;
    }
}

/**
 * Supply chain stages use the same three-step signal.
 */
export function stageColour(stage, theme) {
    switch (stage) {
        case 'Produced':
            return theme.primary;
        case 'Processed':
            return theme.secondary;
        default:
            return theme.destructive;
    }
}

/**
 * Scales, grid lines and tick labels that Chart.js cannot take from CSS.
 */
export function axisTheme(theme) {
    return {
        ticks: { color: theme.mutedForeground },
        grid: { color: `color-mix(in srgb, ${theme.border} 60%, transparent)` },
    };
}

/**
 * Charts read their colours from CSS once, so a theme switch has to be pushed
 * to them. Each chart registers a recolour callback here and is refreshed when
 * the theme engine emits.
 */
const registered = [];

/**
 * A canvas can only back one Chart instance, and re-running a renderer (a live
 * reload, or a Turbo-style visit) would otherwise throw "Canvas is already in
 * use". The live instance and its recolour callback are tracked per canvas so
 * both are torn down together — leaving a callback behind would keep a destroyed
 * chart alive, repainting into a detached canvas on every theme change.
 */
const mounted = new WeakMap();

export function registerChart(recolour) {
    registered.push(recolour);

    recolour(readTheme());
}

function unregisterChart(recolour) {
    const index = registered.indexOf(recolour);

    if (index !== -1) {
        registered.splice(index, 1);
    }
}

/**
 * Build a chart bound to a canvas, replacing whatever was there before.
 *
 * @param {HTMLCanvasElement} canvas
 * @param {object} config        Chart.js configuration
 * @param {(theme: object) => void} recolour  Repaint callback, called once now
 *                                           and again on every theme change
 */
export function mountChart(canvas, config, recolour) {
    const previous = mounted.get(canvas);

    if (previous) {
        previous.chart.destroy();
        unregisterChart(previous.recolour);
    }

    const chart = new Chart(canvas, config);

    mounted.set(canvas, { chart, recolour });

    // Register after construction: the callback paints the chart it was given,
    // so it must run only once the instance exists.
    registerChart(recolour);

    return chart;
}

export function destroyChart(canvas) {
    const previous = mounted.get(canvas);

    if (previous) {
        previous.chart.destroy();
        unregisterChart(previous.recolour);
        mounted.delete(canvas);
    }
}

function refreshAll() {
    const theme = readTheme();

    registered.forEach((recolour) => {
        try {
            recolour(theme);
        } catch (error) {
            console.warn('Theme refresh failed for a chart.', error);
        }
    });
}

window.addEventListener('nutritrace:theme', refreshAll);
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

export function registerChart(recolour) {
    registered.push(recolour);

    recolour(readTheme());
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
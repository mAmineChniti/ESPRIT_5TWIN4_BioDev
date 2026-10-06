/**
 * Theme engine: light, dark or follow-the-system.
 *
 * The choice is kept in localStorage and applied as a `dark` class on <html>,
 * which is what the `.dark` block in resources/css/app.css keys off. Every
 * colour in the app comes from those CSS variables, so switching the class
 * repaints the whole UI — including the charts, which re-read the tokens when
 * the `nutritrace:theme` event fires.
 */
const STORAGE_KEY = 'nutritrace-theme';
const EVENT_NAME = 'nutritrace:theme';
const MODES = ['light', 'dark', 'system'];
const QUERY = '(prefers-color-scheme: dark)';

function readStoredMode() {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return MODES.includes(stored) ? stored : 'system';
    } catch {
        // Private mode or storage disabled: fall back to the system preference.
        return 'system';
    }
}

function prefersDark() {
    return window.matchMedia?.(QUERY).matches ?? false;
}

function resolve(mode) {
    if (mode === 'system') {
        return prefersDark() ? 'dark' : 'light';
    }

    return mode;
}

function applyToDocument(theme) {
    const root = document.documentElement;

    root.classList.toggle('dark', theme === 'dark');
    root.style.colorScheme = theme;
    root.dataset.theme = theme;

    // Keep the mobile browser chrome in step with the page background. The two
    // values live on the meta tag itself, so there is a single definition.
    const meta = document.querySelector('meta[data-theme-color]');

    if (meta) {
        const value = theme === 'dark' ? meta.dataset.dark : meta.dataset.light;

        if (value) {
            meta.setAttribute('content', value);
        }
    }
}

function emit(theme, mode) {
    window.dispatchEvent(
        new CustomEvent(EVENT_NAME, {
            detail: { theme, mode },
        })
    );
}

let mode = readStoredMode();

applyToDocument(resolve(mode));

// Follow the OS while the user has not made an explicit choice.
window.matchMedia?.(QUERY).addEventListener?.('change', () => {
    if (mode === 'system') {
        const theme = resolve(mode);

        applyToDocument(theme);
        emit(theme, mode);
    }
});

/**
 * Set and persist the theme mode.
 */
function setTheme(nextMode) {
    if (!MODES.includes(nextMode)) {
        return;
    }

    mode = nextMode;

    try {
        window.localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        // Preference simply will not survive the session.
    }

    const theme = resolve(mode);

    applyToDocument(theme);
    emit(theme, mode);
}

window.NutriTraceTheme = {
    set: setTheme,
    get: () => mode,
    /** The theme actually in effect, with "system" already resolved. */
    current: () => resolve(mode),
    modes: MODES,
};
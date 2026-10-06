{{--
    Applies the stored theme before the first paint.

    This must stay inline and synchronous: if it waits for the bundle, the page
    renders with the light palette first and then snaps to dark. Kept as a
    component so all three layouts stay identical.

    The theme-color values are the one deliberate colour literal in the app: a
    <meta name="theme-color"> tag cannot read a CSS custom property. They live
    here only, and resources/js/theme.js reuses these attributes rather than
    repeating the values.
--}}
<meta name="theme-color" content="#f8faf3" data-theme-color data-light="#f8faf3" data-dark="#0d1a12">
<script>
    (function () {
        var key = 'nutritrace-theme';
        var mode = 'system';

        try {
            var stored = window.localStorage.getItem(key);

            if (['light', 'dark', 'system'].includes(stored)) {
                mode = stored;
            }
        } catch (e) {
            /* storage unavailable: fall through to the system preference */
        }

        var dark =
            mode === 'dark' ||
            (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        var root = document.documentElement;

        root.classList.toggle('dark', dark);
        root.style.colorScheme = dark ? 'dark' : 'light';
        root.dataset.theme = dark ? 'dark' : 'light';
        root.dataset.themeMode = mode;

        var meta = document.querySelector('meta[data-theme-color]');

        if (meta) {
            meta.setAttribute('content', dark ? meta.dataset.dark : meta.dataset.light);
        }
    })();
</script>
@props(['food'])

@php
    use App\Services\OriginRegistry;

    $registry = app(OriginRegistry::class);
    $route = $registry->describe($food->origin);
    $distance = $registry->displayDistance($food->origin);
    // One id per product, because a product list can put two of these on one page.
    $mapId = 'origin-map-'.$food->id;
@endphp

{{--
    Where this product came from, and how far that is from Tunisia.

    The distance is computed in PHP so the figure is real content: a shopper
    without JavaScript, and a screen reader, still gets the kilometres. The
    script only draws the line those same numbers describe, so there is one
    source of truth.

    Colours come from the theme tokens so the map follows the light/dark palette.
    The route uses the primary token rather than foreground or destructive:
    foreground turns near-white in dark mode and vanishes on the light
    OpenStreetMap tiles, and destructive is this theme's error channel while
    a shipping route is not an error.
--}}
<div class="space-y-3">
    <h3 class="text-base font-medium leading-6 text-foreground">Origin map</h3>

    @if (! $food->origin)
        <p class="text-sm text-muted-foreground">No origin is recorded for this product.</p>
    @else
        @if ($route['isKnown'] && $route['isLocal'])
            <p class="text-sm text-muted-foreground">
                Grown in <span class="font-medium text-foreground">{{ $route['home']['name'] }}</span> —
                a short supply chain with no import leg.
            </p>
        @elseif ($route['isKnown'])
            <p class="text-sm text-muted-foreground">
                <span class="font-semibold text-foreground">{{ $route['origin_']['name'] }}</span>
                to {{ $route['home']['name'] }}: about
                <span class="font-semibold text-foreground">{{ number_format((float) $route['distanceKm']) }} km</span>
                of freight.
            </p>
        @else
            <p class="text-sm text-muted-foreground">
                Recorded as <span class="font-medium text-foreground">{{ $food->origin }}</span>.
                NutriTrace has no coordinates for it, so the map below is a world view.
            </p>
        @endif

        <div id="{{ $mapId }}" class="relative h-64 w-full rounded-lg border border-border shadow-inner"></div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            (function () {
                const origin = @js($route['origin_']);
                const home = @js($route['home']);
                const known = @js($route['isKnown']);
                const local = @js($route['isLocal']);
                const kilometres = @js($route['distanceKm']);
                const originLabel = @js($route['origin'] ?? 'Unknown origin');

                function draw() {
                    const el = document.getElementById(@js($mapId));
                    // window.L is read as a property so a not-yet-loaded CDN
                    // script simply yields a falsy value instead of throwing.
                    if (!el || !window.L || el.dataset.drawn) {
                        return;
                    }
                    el.dataset.drawn = 'true';

                    const styles = getComputedStyle(document.documentElement);
                    const token = (name) => `hsl(${styles.getPropertyValue(name).trim()})`;
                    const tone = {
                        line: token('--primary'),
                        fill: token('--card'),
                    };

                    const map = L.map(el);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }).addTo(map);

                    if (!known) {
                        // Nothing to plot, so show the world rather than an empty box.
                        map.setView([20, 0], 2);
                        return;
                    }

                    const start = [origin.lat, origin.lng];
                    const end = [home.lat, home.lng];

                    if (local) {
                        // Same country: centre on it, with a pin and no route line.
                        map.setView(start, 5);
                        L.marker(start).addTo(map)
                            .bindPopup(`<b>${originLabel}</b><br>Grown in ${home.name}`)
                            .openPopup();
                        return;
                    }

                    // Imported: a line from the origin to Tunisia, labelled with
                    // the distance so the figure is on the map rather than only
                    // inside a popup someone has to click open.
                    const line = L.polyline([start, end], {
                        color: tone.line,
                        weight: 5,
                        opacity: 1,
                    }).addTo(map);

                    L.marker(start).addTo(map)
                        .bindPopup(`<b>${originLabel}</b><br>Origin of this product<br>~${kilometres.toLocaleString()} km`);

                    L.circleMarker(end, {
                        radius: 7, color: tone.line, fillColor: tone.fill, fillOpacity: 1, weight: 3,
                    }).addTo(map)
                        .bindPopup(`<b>${home.name}</b><br>Destination market`);

                    line.bindTooltip(`~${kilometres.toLocaleString()} km`, { sticky: true }).openTooltip();
                    map.fitBounds(line.getBounds(), { padding: [45, 45] });
                }

                // The CDN script may arrive after DOMContentLoaded, so try now,
                // then again on DOM ready and on window load.
                draw();
                document.addEventListener('DOMContentLoaded', draw);
                window.addEventListener('load', draw);
            })();
        </script>
    @endif
</div>
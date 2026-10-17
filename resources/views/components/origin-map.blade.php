@props(['food'])

@php
    use App\Services\Geo\VisitorLocation;
    use App\Services\OriginRegistry;

    $viewer = app(VisitorLocation::class)->current();
    $route = app(OriginRegistry::class)->describe($food->origin, $viewer);

    // One id per product, because a product list can put two of these on one page.
    $mapId = 'origin-map-'.$food->id;
    $summaryId = $mapId.'-summary';
    $statusId = $mapId.'-status';
    $buttonId = $mapId.'-locate';

    // Only offered when a better answer is actually reachable: a precise fix
    // cannot be improved on, and an unresolvable origin has no route to draw.
    $canRefine = $route['isKnown'] && ($viewer === null || ! $viewer->isPrecise());

    $originName = $route['originPoint']['name'] ?? $route['origin'];
    $destination = $viewer?->name ?? __('your area');

    $summary = match (true) {
        ! $route['isKnown'] => __('Recorded as :origin. NutriTrace has no coordinates for it, so the map below is a world view.', [
            'origin' => e($route['origin']),
        ]),
        $route['isLocal'] => __('Grown in :origin — the country you are in, so there is no import leg.', [
            'origin' => e($originName),
        ]),
        $route['isImported'] => __(':origin to :destination, about :distance of freight.', [
            'origin' => e($originName),
            'destination' => e($destination),
            'distance' => '<span id="'.$mapId.'-distance" class="font-semibold text-foreground">'
                .e(\App\LocalizedNumber::format((float) $route['distanceKm'])).' km</span>',
        ]),
        default => __('Grown in :origin. Share your location to see the route it took to reach you.', [
            'origin' => e($originName),
        ]),
    };

    // The copy the map script needs, so the browser can restate it in the
    // visitor's language after a more precise fix rather than inventing its own.
    $copy = [
        'freight' => __(':origin to :destination, about :distance of freight.'),
        'local' => __('Grown in :origin — the country you are in, so there is no import leg.'),
        'unplaced' => __('Grown in :origin. Share your location to see the route it took to reach you.'),
        'declined' => __('Location access was declined, so no route could be drawn.'),
        'unavailable' => __('This browser cannot share a location, so no route could be drawn.'),
        'approximate' => __('Position shown is approximate, taken from your network.'),
        'precise' => __('Position shown is your precise location.'),
    ];

    $status = match (true) {
        $viewer === null => null,
        $viewer->isPrecise() => $copy['precise'],
        default => $copy['approximate'],
    };

    // A product with no resolvable origin has no route to refine, so the refine
    // button never renders and the script never reaches any of these strings.
    // Shipping them anyway would put wording the page never displays into its
    // source, which is how a test asserting on absent copy ends up matching it.
    if (! $route['isKnown']) {
        $copy = [];
    }
@endphp

{{--
    Where this product came from, and how far that is from the person reading it.

    There is no fixed home market any more. The origin end comes from the
    product's own record and is always resolved server-side; the visitor end is
    resolved here from the request, so the distance in the copy is real content
    that a screen reader reads and a test can assert.

    The script below only ever refines that: it may replace an approximate
    visitor position with a precise one and redraw the same line, but the
    numbers it uses come from the same figures already in the page, so there is
    one source of truth. When no tier can place the visitor it says so rather
    than falling back to a guess.

    Colours come from the theme tokens so the map follows the light/dark palette.
    The route uses the primary token rather than foreground or destructive:
    foreground turns near-white in dark mode and vanishes on the light
    OpenStreetMap tiles, and destructive is this theme's error channel while
    a shipping route is not an error.
--}}
<div class="space-y-3">
    <h3 class="text-base font-medium leading-6 text-foreground">{{ __('Origin map') }}</h3>

    @if (! $food->origin)
        <p class="text-sm text-muted-foreground">{{ __('No origin is recorded for this product.') }}</p>
    @else
        <p id="{{ $summaryId }}" class="text-sm text-muted-foreground">{!! $summary !!}</p>

        {{-- isolate, because Leaflet stacks its own panes at z-index 100 to
             1000. The nav is z-40 and every overlay in the app is z-50, so
             without a stacking context of its own the map's zoom buttons,
             attribution and popups paint straight over them once you scroll.
             Isolation confines those numbers to this box. --}}
        <div id="{{ $mapId }}" class="isolate relative h-64 w-full rounded-lg border border-border shadow-inner"></div>

        @if ($status !== null)
            <p id="{{ $statusId }}" role="status" aria-live="polite" class="text-xs text-muted-foreground">{{ $status }}</p>
        @else
            <p id="{{ $statusId }}" role="status" aria-live="polite" class="text-xs text-muted-foreground"></p>
        @endif

        @if ($canRefine)
            <april:button id="{{ $buttonId }}" variant="outline" size="sm" class="w-full sm:w-auto">
                <x-lucide-locate-fixed class="size-4" />
                {{ __('Use my location') }}
            </april:button>
        @endif

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            (function () {
                const origin = @js($route['originPoint']);
                const viewer = @js($route['viewer']);
                const originLabel = @js($originName);
                const copy = @js($copy);

                const el = document.getElementById(@js($mapId));
                const summary = document.getElementById(@js($summaryId));
                const status = document.getElementById(@js($statusId));
                const button = document.getElementById(@js($buttonId));

                // Same wording rules as App\Services\OriginRegistry, so a
                // refined figure is phrased exactly like the rendered one.
                const LOCAL_THRESHOLD_KM = 50;
                const EARTH_RADIUS_KM = 6371;

                // How close a browser fix has to be before the page calls it the
                // reader's own position rather than an approximation.
                const PRECISE_RADIUS_M = 1000;

                let map = null;
                let route = null;
                let markers = [];

                const numberFormat = new Intl.NumberFormat(document.documentElement.lang || 'en');

                function distanceBetween(lat1, lng1, lat2, lng2) {
                    const toRadians = Math.PI / 180;
                    const dLat = (lat2 - lat1) * toRadians;
                    const dLng = (lng2 - lng1) * toRadians;
                    const a = Math.sin(dLat / 2) ** 2
                        + Math.cos(lat1 * toRadians) * Math.cos(lat2 * toRadians) * Math.sin(dLng / 2) ** 2;

                    return EARTH_RADIUS_KM * 2 * Math.asin(Math.min(1, Math.sqrt(a)));
                }

                function kilometres(from, to) {
                    return Math.round(distanceBetween(from.lat, from.lng, to.lat, to.lng));
                }

                function format(template, replacements) {
                    return Object.keys(replacements).reduce(
                        (text, key) => text.replaceAll(':' + key, replacements[key]),
                        template,
                    );
                }

                // Two known country codes settle it exactly. A precise browser
                // fix carries no country code, so the country already resolved
                // for this visitor is carried across the refinement rather than
                // being thrown away: someone in Sousse is still in Tunisia even
                // though the browser only reports coordinates.
                function isLocal(from, to) {
                    if (from.countryCode && to.countryCode) {
                        return from.countryCode === to.countryCode;
                    }

                    return kilometres(from, to) <= LOCAL_THRESHOLD_KM;
                }

                function say(message) {
                    if (status && message) {
                        status.textContent = message;
                    }
                }

                function reset() {
                    if (route) {
                        map.removeLayer(route);
                        route = null;
                    }
                    markers.forEach((marker) => map.removeLayer(marker));
                    markers = [];
                }

                function draw(from, precise) {
                    reset();

                    const token = getComputedStyle(document.documentElement);
                    const colour = `hsl(${token.getPropertyValue('--primary').trim()})`;
                    const fill = `hsl(${token.getPropertyValue('--card').trim()})`;

                    if (precise) {
                        markers.push(L.marker([from.lat, from.lng])
                            .bindPopup(`<b>${from.name}</b><br>${copy.precise}`).addTo(map));
                    }

                    // Same country: a pin and no route, because there is no leg.
                    if (isLocal(from, origin)) {
                        markers.push(L.marker([origin.lat, origin.lng]).addTo(map)
                            .bindPopup(`<b>${originLabel}</b><br>${format(copy.local, { origin: originLabel })}`));

                        map.setView([origin.lat, origin.lng], 5);
                        summary.innerHTML = format(copy.local, { origin: originLabel });

                        return;
                    }

                    const km = kilometres(from, origin);
                    const distanceText = `~${numberFormat.format(km)} km`;

                    route = L.polyline([[from.lat, from.lng], [origin.lat, origin.lng]], {
                        color: colour, weight: 5, opacity: 1,
                    }).addTo(map);

                    route.bindTooltip(distanceText, { sticky: true }).openTooltip();

                    if (! precise) {
                        markers.push(L.circleMarker([from.lat, from.lng], {
                            radius: 7, color: colour, fillColor: fill, fillOpacity: 1, weight: 3,
                        }).addTo(map).bindPopup(`<b>${from.name}</b><br>${copy.approximate}`));
                    }

                    markers.push(L.marker([origin.lat, origin.lng]).addTo(map)
                        .bindPopup(`<b>${originLabel}</b><br>Origin of this product<br>${distanceText}`));

                    map.fitBounds(route.getBounds(), { padding: [45, 45] });

                    // Restated with the refined figure. The number replaces the
                    // one the server printed, so the sentence stays the same.
                    summary.innerHTML = format(copy.freight, {
                        origin: originLabel,
                        destination: from.name,
                        distance: `<span id="${el.id}-distance" class="font-semibold text-foreground">${numberFormat.format(km)} km</span>`,
                    });
                }

                function place(visitorPoint, precise, message) {
                    say(message);
                    draw(visitorPoint, precise);

                    // Nothing left to refine once the position is the visitor's own.
                    if (button && precise) {
                        button.hidden = true;
                    }
                }

                function boot() {
                    // window.L is read as a property so a not-yet-loaded CDN
                    // script simply yields a falsy value instead of throwing.
                    if (! el || ! window.L || el.dataset.drawn) {
                        return;
                    }
                    el.dataset.drawn = 'true';

                    map = L.map(el);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }).addTo(map);

                    if (! origin) {
                        // Nothing to plot, so show the world rather than an empty box.
                        map.setView([20, 0], 2);

                        return;
                    }

                    if (! viewer) {
                        map.setView([origin.lat, origin.lng], 4);
                        L.marker([origin.lat, origin.lng]).addTo(map)
                            .bindPopup(`<b>${originLabel}</b>`);

                        return;
                    }

                    draw(viewer, viewer.precise);
                }

                function refine() {
                    if (! origin) {
                        return;
                    }

                    if (! navigator.geolocation) {
                        say(copy.unavailable);

                        return;
                    }

                    button.disabled = true;

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            // The browser reports how far the fix could be off.
                            // Calling a five-kilometre guess the reader's precise
                            // location would be the same overclaim as calling an
                            // IP lookup precise, so the wording follows the fix.
                            const accurate = typeof position.coords.accuracy === 'number'
                                && position.coords.accuracy <= PRECISE_RADIUS_M;

                            place({
                                name: accurate ? copy.precise : copy.approximate,
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                                // Carried over, so a city inside the visitor's own
                                // country is not mistaken for an import leg.
                                countryCode: viewer ? viewer.countryCode : null,
                            }, accurate, accurate ? copy.precise : copy.approximate);
                        },
                        () => {
                            button.disabled = false;
                            say(copy.declined);
                            timezoneFallback();
                        },
                        // High accuracy asks for the best fix the device can
                        // give, which is the point of asking at all: the cheap
                        // one is a network guess that can be kilometres out.
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
                    );
                }

                // The browser knows its own timezone even when it will not share
                // a position. That names a country, which is coarse but enough
                // to draw a route and say how far it is.
                function timezoneFallback() {
                    let zone = null;

                    try {
                        zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    } catch (e) {
                        zone = null;
                    }

                    if (! zone) {
                        return;
                    }

                    fetch(@js(route('location.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ timezone: zone }),
                    })
                        .then((response) => (response.ok ? response.json() : null))
                        .then((payload) => {
                            const point = payload && payload.location;

                            if (! point) {
                                say(copy.unavailable);

                                return;
                            }

                            if (button) {
                                button.hidden = true;
                            }

                            place(point, false, copy.approximate);
                        })
                        .catch(() => say(copy.unavailable));
                }

                if (button) {
                    button.addEventListener('click', refine);
                }

                // The CDN script may arrive after DOMContentLoaded, so try now,
                // then again on DOM ready and on window load.
                boot();
                document.addEventListener('DOMContentLoaded', boot);
                window.addEventListener('load', boot);
            })();
        </script>
    @endif
</div>
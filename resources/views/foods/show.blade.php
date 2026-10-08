@extends('layouts.back')

@section('title', 'Food Details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            Back
        </april:button-link>
        <h1 class="text-2xl font-bold">Product details</h1>
    </div>

    {{-- The route is readable by any signed in user, so the actions are
         gated on the policy rather than assumed. --}}
    <div class="flex items-center gap-2">
        <april:button-link href="{{ route('products.show', $food) }}" variant="outline">
            Public page
        </april:button-link>
        <april:button-link href="{{ route('foods.transitions.index', $food) }}" variant="outline">
            <x-lucide-route class="size-4" />
            Trace record
        </april:button-link>
        @can('update', $food)
            <april:button-link href="{{ route('foods.edit', $food) }}" variant="outline">Edit</april:button-link>
        @endcan
        @can('delete', $food)
            <april:alert-dialog>
                <x-slot:trigger>
                    <april:button type="button" variant="destructive">Delete</april:button>
                </x-slot:trigger>
                <x-slot:content>
                    <div>
                        <h2 class="text-lg font-semibold" x-bind="title">Delete this product?</h2>
                        <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                            <strong>{{ $food->name }}</strong> and its recorded supply chain will be removed.
                            This cannot be undone.
                        </p>
                    </div>
                    <april:alert-dialog-footer>
                        <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                        <form action="{{ route('foods.destroy', $food) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <april:button type="submit" variant="destructive" x-bind="action">
                                Delete product
                            </april:button>
                        </form>
                    </april:alert-dialog-footer>
                </x-slot:content>
            </april:alert-dialog>
        @endcan
    </div>
</div>

<april:card class="max-w-3xl">
    <x-slot:title class="text-lg">Traceability information</x-slot:title>
    <x-slot:description>From farm to plate.</x-slot:description>
    <x-slot:content>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">Product name</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->name }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">Category</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->category->name ?? 'None' }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">Origin</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->origin ?? 'Unknown' }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">Environmental score</dt>
                <dd class="mt-1 text-sm text-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">Producer</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->producer?->name ?? 'Unattributed' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">Certifications</dt>
                <dd class="mt-1 text-sm text-foreground">
                    @forelse($food->certifications as $certification)
                        <div class="flex flex-wrap items-center gap-2 py-1">
                            <span class="font-medium">{{ $certification->name }}</span>
                            <span class="text-muted-foreground text-xs">{{ $certification->issuer }}</span>
                            @if($certification->certificate_number)
                                <span class="text-muted-foreground text-xs">No. {{ $certification->certificate_number }}</span>
                            @endif
                            @if($certification->valid_until)
                                <span class="text-xs {{ $certification->isExpired() ? 'text-destructive' : 'text-muted-foreground' }}">
                                    {{ $certification->isExpired() ? 'Expired' : 'Valid until' }} {{ $certification->valid_until->format('Y-m-d') }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted-foreground">No certification recorded</span>
                    @endforelse
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">Supply chain</dt>
                <dd class="mt-1 text-sm text-foreground">
                    @php $current = null; @endphp
                    @forelse($food->transitions as $transition)
                        <div class="flex items-center gap-3 py-1">
                            <span class="font-medium">{{ $transition->to_stage?->label() }}</span>
                            @if($transition->from_stage)
                                <span class="text-muted-foreground text-xs">from {{ $transition->from_stage->label() }}</span>
                            @endif
                            <span class="text-muted-foreground text-xs">
                                {{ $transition->occurred_at->format('Y-m-d') }}
                                @if($transition->actor) — {{ $transition->actor->name }} @endif
                            </span>
                        </div>
                    @empty
                        <span class="text-muted-foreground">No supply chain steps recorded yet</span>
                    @endforelse
                </dd>
            </div>
        </dl>

        <april:separator />

        <div class="py-5">
        <h3 class="mb-4 text-base font-medium leading-6 text-foreground">Origin Map 🗺️</h3>
        
        @if($food->origin)
            <div id="origin-map" class="h-64 w-full rounded-lg z-0 relative shadow-inner border border-border"></div>
            
            <!-- Leaflet CSS & JS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // mb_strtolower so accented origins such as "BRÉSIL" still
                    // match the dictionary keys below.
                    const origin = @js(mb_strtolower(trim($food->origin)));
                    
                    // Coordinate dictionary (lat, lng), expanded for the demo.
                    const coordinates = {
                        'tunisie': [33.8869, 9.5375],
                        'france': [46.2276, 2.2137],
                        'espagne': [40.4637, -3.7492],
                        'italie': [41.8719, 12.5674],
                        'maroc': [31.7917, -7.0926],
                        'algerie': [28.0339, 1.6596],
                        'bresil': [-14.2350, -51.9253],
                        'brésil': [-14.2350, -51.9253],
                        'brasil': [-14.2350, -51.9253], // Orthographe portugaise/espagnole
                        'brazil': [-14.2350, -51.9253], // Orthographe anglaise
                        'usa': [37.0902, -95.7129],
                        'etats-unis': [37.0902, -95.7129],
                        'chine': [35.8617, 104.1954],
                        'allemagne': [51.1657, 10.4515],
                        'canada': [56.1304, -106.3468],
                        'mexique': [23.6345, -102.5528],
                        'argentine': [-38.4161, -63.6167],
                        'inde': [20.5937, 78.9629],
                        'japon': [36.2048, 138.2529],
                        'turquie': [38.9637, 35.2433],
                        'royaume-uni': [55.3781, -3.4360],
                    };

                    // Colours come from the theme tokens so the map follows the
                    // light/dark palette instead of hardcoded literals.
                    const styles = getComputedStyle(document.documentElement);
                    const token = (name) => `hsl(${styles.getPropertyValue(name).trim()})`;
                    const tone = {
                        primary: token('--primary'),
                        destructive: token('--destructive'),
                        foreground: token('--foreground'),
                        background: token('--background'),
                    };

                    const coord = coordinates[origin];
                    const homeCoord = coordinates['tunisie']; // Default destination for the local market.
                    
                    if (coord) {
                        const map = L.map('origin-map');
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                        }).addTo(map);

                        if (origin === 'tunisie') {
                            // Local product.
                            map.setView(coord, 5);
                            L.marker(coord).addTo(map)
                                .bindPopup('<b>🌿 Local product</b><br>Short supply chain (Tunisia)')
                                .openPopup();
                        } else {
                            // Imported product: draw the route and calculate distance.
                            const distanceKm = Math.round(map.distance(coord, homeCoord) / 1000);
                            
                            // Dashed line from origin to destination
                            const polyline = L.polyline([coord, homeCoord], {
                                color: tone.destructive,
                                weight: 3,
                                dashArray: '10, 10'
                            }).addTo(map);
                            
                            // Fit the full route in the viewport.
                            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

                            // Origin marker with distance.
                            L.marker(coord).addTo(map)
                                .bindPopup(`<b>Origin:</b> {{ $food->origin }}<br><b>Distance:</b> ~${distanceKm} km ✈️<br><span style="color:${tone.destructive};font-size:12px">High transport impact</span>`)
                                .openPopup();
                                
                            // Destination marker.
                            L.circleMarker(homeCoord, { color: tone.primary, radius: 5 }).addTo(map)
                                .bindPopup('Destination (you)');
                        }
                    } else {
                        const map = L.map('origin-map').setView([20, 0], 2);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    }
                });
            </script>
        @else
            <p class="text-sm text-muted-foreground">No origin is defined for this product.</p>
        @endif
    </div>

    <april:separator />

    <div class="pt-5">
        <h3 class="mb-4 text-base font-medium leading-6 text-foreground">Nutritional values (per 100g)</h3>
        <div class="grid grid-cols-4 text-center gap-4">
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->calories }}</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Calories</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->protein }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Protein</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-secondary-foreground">{{ $food->carbs }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Carbs</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                {{-- Fat is a nutrient, not a fault: it uses the same primary
                     token as the other three rather than destructive. --}}
                <span class="block text-2xl font-bold text-primary">{{ $food->fat }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Fat</span>
            </div>
        </div>
    </div>
    </x-slot:content>
</april:card>
@endsection

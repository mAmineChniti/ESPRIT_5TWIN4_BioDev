@extends('layouts.back')

@section('title', 'Food Details')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('foods.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
            <h1 class="text-2xl font-bold">Product details</h1>
        </div>
        <div class="space-x-2">
            <a href="{{ route('foods.edit', $food) }}" class="bg-card border border-input text-foreground hover:bg-muted font-medium py-2 px-4 rounded-md">
                Edit
            </a>
            <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-destructive hover:bg-destructive/90 text-destructive-foreground font-medium py-2 px-4 rounded-md">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden max-w-3xl">
    <div class="px-6 py-5 border-b border-border">
        <h3 class="text-lg font-medium leading-6 text-foreground">Traceability information</h3>
        <p class="mt-1 max-w-2xl text-sm text-muted-foreground">From farm to plate.</p>
    </div>
    <div class="px-6 py-5">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">Nom du produit</dt>
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
                <dt class="text-sm font-medium text-muted-foreground">Score Environnemental</dt>
                <dd class="mt-1 text-sm text-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">Producteur</dt>
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
    </div>

    <div class="px-6 py-5 border-t border-border">
        <h3 class="text-md font-medium leading-6 text-foreground mb-4">Origin Map 🗺️</h3>
        
        @if($food->origin)
            <div id="origin-map" class="h-64 w-full rounded-lg z-0 relative shadow-inner border border-border"></div>
            
            <!-- Leaflet CSS & JS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const origin = "{{ strtolower(trim($food->origin)) }}";
                    
                    // Dictionnaire de coordonnées (Lat, Lng) enrichi pour la démo
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

                    const coord = coordinates[origin];
                    const homeCoord = coordinates['tunisie']; // Destination par défaut (Ex: L'utilisateur est en Tunisie)
                    
                    if (coord) {
                        const map = L.map('origin-map');
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                        }).addTo(map);

                        if (origin === 'tunisie') {
                            // Produit Local
                            map.setView(coord, 5);
                            L.marker(coord).addTo(map)
                                .bindPopup('<b>🌿 Produit Local</b><br>Circuit court (Tunisie)')
                                .openPopup();
                        } else {
                            // Produit importé : On trace la ligne et on calcule la distance
                            const distanceKm = Math.round(map.distance(coord, homeCoord) / 1000);
                            
                            // Ligne rouge pointillée
                            const polyline = L.polyline([coord, homeCoord], {
                                color: 'red',
                                weight: 3,
                                dashArray: '10, 10'
                            }).addTo(map);
                            
                            // Zoomer pour voir toute la ligne
                            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

                            // Marqueur d'origine avec la distance
                            L.marker(coord).addTo(map)
                                .bindPopup(`<b>Origine :</b> {{ $food->origin }}<br><b>Distance :</b> ~${distanceKm} km ✈️<br><span class="text-xs text-red-500">Fort impact transport</span>`)
                                .openPopup();
                                
                            // Marqueur d'arrivée (Maison)
                            L.circleMarker(homeCoord, { color: 'green', radius: 5 }).addTo(map)
                                .bindPopup('Destination (Vous)');
                        }
                    } else {
                        const map = L.map('origin-map').setView([20, 0], 2);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    }
                });
            </script>
        @else
            <p class="text-sm text-muted-foreground">Aucune origine définie pour ce produit.</p>
        @endif
    </div>

    <div class="px-6 py-5 border-t border-border">
        <h3 class="text-md font-medium leading-6 text-foreground mb-4">Nutritional values (per 100g)</h3>
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
                <span class="block text-2xl font-bold text-destructive">{{ $food->fat }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Fat</span>
            </div>
        </div>
    </div>
</div>
@endsection

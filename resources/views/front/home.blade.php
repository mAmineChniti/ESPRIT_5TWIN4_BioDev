@extends('layouts.front')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-2xl border border-border bg-card">
        <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -top-24 right-0 h-72 w-72 rounded-full bg-primary/15 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-28 left-1/4 h-72 w-72 rounded-full bg-accent/60 blur-3xl" aria-hidden="true"></div>

        <div class="relative grid gap-8 p-8 sm:p-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
            <div>
                <april:badge variant="secondary" class="gap-1.5">
                    <x-lucide-sprout class="size-3.5" />
                    Farm → plate traceability
                </april:badge>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    Know what's really <span class="text-primary">on your plate</span>.
                </h1>
                <p class="mt-4 max-w-xl text-muted-foreground">
                    NutriTrace follows every food product from the farm to your fork —
                    producer, processor, distributor — and exposes its environmental
                    footprint and certifications, so greenwashing has nowhere to hide.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <april:button-link href="#journey">Follow a product</april:button-link>
                    @auth
                        <april:button-link href="{{ route('dashboard') }}" variant="outline">Back Office</april:button-link>
                    @else
                        <april:button-link href="{{ route('login') }}" variant="outline">Sign in</april:button-link>
                    @endauth
                </div>
                <dl class="mt-8 flex flex-wrap gap-8">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Products tracked</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $foodCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Meals logged</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $mealCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Certified products</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $certifiedCount }}</dd>
                    </div>
                </dl>
            </div>

            <april:card>
                <x-slot:title>A product from the catalog</x-slot:title>
                <x-slot:description>Real data, taken from the products below.</x-slot:description>
                <x-slot:content>
                    @php $spotlight = $latestFoods->first(); @endphp
                    @if($spotlight)
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <x-lucide-salad class="size-6" />
                            </span>
                            <div>
                                <p class="font-semibold">{{ $spotlight->name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ $spotlight->producer?->name ?? 'Unattributed' }}
                                    @if($spotlight->origin) · {{ $spotlight->origin }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse($spotlight->certifications as $certification)
                                <april:badge variant="none" class="border-transparent bg-primary text-primary-foreground">
                                    {{ $certification->name }}
                                </april:badge>
                            @empty
                                <span class="text-sm text-muted-foreground">No certifications recorded.</span>
                            @endforelse
                        </div>
                        <div class="mt-4 flex items-center gap-3 text-sm">
                            <span class="w-28 shrink-0 text-muted-foreground">Footprint</span>
                            <x-eco-score :score="$spotlight->environmental_score?->value" />
                        </div>
                        <a href="{{ route('foods.show', $spotlight) }}" class="mt-4 inline-block text-sm text-primary hover:underline">
                            View full traceability →
                        </a>
                    @else
                        <p class="text-sm text-muted-foreground">No products in the catalog yet.</p>
                    @endif
                </x-slot:content>
            </april:card>
        </div>
    </section>

    {{-- Journey: farm to plate --}}
    <section id="journey" class="mt-6 scroll-mt-6">
        <april:card>
            <x-slot:title>The journey we trace</x-slot:title>
            <x-slot:description>Four checkpoints, zero blind spots.</x-slot:description>
            <x-slot:content>
                <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'wheat', 'color' => 'bg-primary/10 text-primary', 'title' => 'Producer', 'text' => 'The farm: soil, water, growing practices.'],
                        ['icon' => 'factory', 'color' => 'bg-primary/10 text-primary', 'title' => 'Processor', 'text' => 'Transformation steps and additives.'],
                        ['icon' => 'truck', 'color' => 'bg-primary/10 text-primary', 'title' => 'Distributor', 'text' => 'Kilometres, cold chain, packaging.'],
                        ['icon' => 'shopping-basket', 'color' => 'bg-primary/10 text-primary', 'title' => 'Consumer', 'text' => 'You: scan, compare, choose better.'],
                    ] as $step)
                        <li class="relative rounded-xl border border-border bg-background p-4">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ $step['color'] }}">
                                <x-dynamic-component :component="'lucide-' . $step['icon']" class="size-5" />
                            </span>
                            <p class="mt-3 font-semibold">{{ $step['title'] }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-slot:content>
        </april:card>
    </section>

    {{-- Certifications & footprint scale --}}
    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <april:card>
            <x-slot:title>Certifications, decoded</x-slot:title>
            <x-slot:description>One honest hue per label.</x-slot:description>
            <x-slot:content>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-center gap-3">
                        <april:badge variant="none" class="w-24 justify-center border-transparent bg-primary text-primary-foreground">Organic</april:badge>
                        <span class="text-muted-foreground">Grown without synthetic pesticides.</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <april:badge variant="none" class="w-24 justify-center border-transparent bg-primary text-primary-foreground">Local</april:badge>
                        <span class="text-muted-foreground">Produced within 100 km of you.</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <april:badge variant="none" class="w-24 justify-center border-transparent bg-primary text-primary-foreground">Fair trade</april:badge>
                        <span class="text-muted-foreground">Producers paid a fair price.</span>
                    </li>
                </ul>
            </x-slot:content>
            <x-slot:footer>
                <p class="flex items-center gap-2 text-sm text-muted-foreground">
                    <x-lucide-shield-check class="size-4 text-primary" />
                    Every claim is verified — that is the whole point.
                </p>
            </x-slot:footer>
        </april:card>

        <april:card>
            <x-slot:title>Footprint, at a glance</x-slot:title>
<x-slot:description>Real grades recorded in the catalog.</x-slot:description>
                <x-slot:content>
                    @php $scoredTotal = max($scoreDistribution->sum(), 1); @endphp
                    @if($scoreDistribution->isNotEmpty())
                        <div class="space-y-3 text-sm">
                            @foreach(\App\Enums\EnvironmentalScore::cases() as $grade)
                                @php $count = (int) $scoreDistribution->get($grade->value, 0); @endphp
                                <div class="flex items-center gap-3">
                                    <span class="w-32 shrink-0">{{ $grade->value }} — {{ $grade->label() }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                        <div class="h-full rounded-full {{ $grade->badgeClasses() }}" style="width: {{ round($count / $scoredTotal * 100) }}%"></div>
                                    </div>
                                    <span class="w-8 shrink-0 text-right tabular-nums text-muted-foreground">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">No products have been environmentally scored yet.</p>
                    @endif
                </x-slot:content>
        </april:card>
    </section>

    {{-- Latest from the catalog --}}
    @if($latestFoods->isNotEmpty())
        <section class="mt-6">
            <april:card>
                <x-slot:title>Fresh in the catalog</x-slot:title>
                <x-slot:description>The latest products added by producers.</x-slot:description>
                <x-slot:content>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach($latestFoods->take(6) as $food)
                            <li class="flex items-center justify-between gap-3 rounded-xl border border-border bg-background px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <x-lucide-apple class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold">{{ $food->name }}</p>
                                        <p class="text-xs capitalize text-muted-foreground">{{ $food->category->name ?? '' }}</p>
                                    </div>
                                </div>
                                <april:badge variant="secondary" class="tabular-nums">{{ $food->calories }} kcal</april:badge>
                            </li>
                        @endforeach
                    </ul>
                </x-slot:content>
            </april:card>
        </section>
    @endif
@endsection

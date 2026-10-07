@extends('layouts.front')

@section('title', 'Consumer Space')

@section('content')
{{-- Espace Consommateur — Amine Chnitti --}}
<div class="mb-8">
    <p class="text-xs font-semibold uppercase tracking-widest text-primary">Espace Consommateur</p>
    <h1 class="mt-1 text-3xl font-bold">Your consumer space</h1>
    <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
        Four tools that work off the same record: an AI auditor that looks for claims the evidence does
        not support, an assistant that answers questions about a product, a shortlist of better-evidenced
        alternatives, and one-click reporting when something looks wrong.
    </p>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <april:card>
        <x-slot:content>
            <p class="text-sm font-medium text-muted-foreground">Meals logged</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums">{{ $mealsLogged }}</p>
        </x-slot:content>
    </april:card>
    <april:card>
        <x-slot:content>
            <p class="text-sm font-medium text-muted-foreground">Reports filed</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums">{{ $reportsFiled }}</p>
        </x-slot:content>
    </april:card>
    <april:card>
        <x-slot:content>
            <p class="text-sm font-medium text-muted-foreground">Assistant</p>
            <p class="mt-2">
                @if($assistantEnabled)
                    <april:badge variant="secondary">Ready</april:badge>
                @else
                    <april:badge variant="outline">Needs an API key</april:badge>
                @endif
            </p>
        </x-slot:content>
    </april:card>
</div>

{{-- 1. AI greenwashing detection over what this consumer actually eats --}}
<section class="mt-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">AI audit of what you eat</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                Each product you have logged is checked for gaps between what it claims and what
                NutriTrace has on record.
            </p>
        </div>
        <april:button-link href="{{ route('products.index') }}" variant="outline" size="sm">
            Browse the catalog
        </april:button-link>
    </div>

    @forelse($watchList as $entry)
        @php $food = $entry['food']; $report = $entry['report']; @endphp
        <april:card class="mt-4">
            <x-slot:title>
                <a href="{{ route('products.show', $food) }}" class="hover:text-primary">{{ $food->name }}</a>
            </x-slot:title>
            <x-slot:description>
                {{ $food->category->name ?? 'Uncategorised' }}
                @if($food->origin) · {{ $food->origin }} @endif
                · {{ $food->transparencyScore() }}/100 transparency
            </x-slot:description>
            <x-slot:content>
                <x-greenwashing-report :report="$report" :food="$food" />
            </x-slot:content>
        </april:card>
    @empty
        <april:card class="mt-4">
            <x-slot:content>
                <div class="p-8 text-center">
                    <x-lucide-utensils class="mx-auto mb-3 size-10 text-muted-foreground" />
                    <p class="text-sm font-medium">Nothing to audit yet.</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Log a meal and the products in it will be audited here automatically.
                    </p>
                    <april:button-link href="{{ route('meals.create') }}" class="mt-4">Log a meal</april:button-link>
                </div>
            </x-slot:content>
        </april:card>
    @endforelse
</section>

{{-- 3. Recommendations --}}
<section class="mt-10">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">More responsible alternatives</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                Ranked by how well each product is evidenced, in the categories you already buy from.
            </p>
        </div>
        <april:button-link href="{{ route('consumer.recommendations') }}" variant="outline" size="sm">
            See all
        </april:button-link>
    </div>

    @if($recommendations->isEmpty())
        <april:card class="mt-4">
            <x-slot:content>
                <p class="p-6 text-sm text-muted-foreground">
                    No alternatives to suggest yet. Once there are graded products in the categories you
                    buy from, they will appear here.
                </p>
            </x-slot:content>
        </april:card>
    @else
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($recommendations->take(3) as $recommendation)
                <x-product-recommendation-card :recommendation="$recommendation" />
            @endforeach
        </div>
    @endif
</section>

{{-- 2. Assistant --}}
<section class="mt-10">
    <april:card>
        <x-slot:title>Ask about a product</x-slot:title>
        <x-slot:description>
            The assistant answers from the traceability record only, and tells you which fields it used.
        </x-slot:description>
        <x-slot:content>
            <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
                <div class="flex-1">
                    <april:label for="space-search">Find the product you want to ask about</april:label>
                    <april:input id="space-search" name="q" class="mt-1"
                                 placeholder="Search name, origin, producer or certification" />
                </div>
                <april:button type="submit">Find it</april:button>
            </form>
            <p class="mt-3 text-xs text-muted-foreground">
                Open a product page and the assistant panel is available at the bottom of it.
            </p>
        </x-slot:content>
    </april:card>
</section>
@endsection
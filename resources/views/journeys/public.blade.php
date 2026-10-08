@extends('layouts.front')

@section('title', 'Traceability journey')

@section('content')
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wider text-primary">Traceability</p>
    <h1 class="mt-2 text-3xl font-bold">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</h1>
    <p class="mt-2 text-muted-foreground">Production and distribution journey.</p>
</div>

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Environmental score</p>
        <p class="mt-1 text-3xl font-bold text-primary">{{ $journey->environmental_score }}/100</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Total distance</p>
        <p class="mt-1 text-3xl font-bold">{{ $journey->total_distance_km }} km</p>
    </div>
</div>

<section class="rounded-lg bg-card p-6 shadow">
    <h2 class="mb-6 text-xl font-semibold">Chronologie</h2>
    <ol class="space-y-6">
        @forelse($journey->steps as $step)
            <li class="flex gap-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary font-bold text-primary-foreground">{{ $step->step_order }}</span>
                <div>
                    <h3 class="font-semibold">{{ ucfirst($step->type) }} — {{ $step->location }}</h3>
                    <p class="text-sm text-muted-foreground">{{ $step->step_date?->format('Y-m-d') }}</p>
                    @if($step->description)
                        <p class="mt-1 text-sm">{{ $step->description }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">No steps available.</li>
        @endforelse
    </ol>
</section>

@if($journey->ai_summary)
    <section class="mt-6 rounded-lg bg-card p-6 shadow">
        <h2 class="mb-2 text-xl font-semibold">Summary</h2>
        <p class="text-muted-foreground">{{ $journey->ai_summary }}</p>
    </section>
@endif
@endsection

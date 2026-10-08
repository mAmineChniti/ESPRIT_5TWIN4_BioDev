@extends('layouts.back')

@section('title', 'Journey details')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('processor.journeys.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <div>
        <h1 class="text-2xl font-bold">Journey details</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Environmental score</p>
        <p class="mt-1 text-2xl font-bold text-primary">{{ $journey->environmental_score }}/100</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Total distance</p>
        <p class="mt-1 text-2xl font-bold">{{ $journey->total_distance_km }} km</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Code QR</p>
        <p class="mt-1 break-all text-sm font-medium">{{ $journey->qr_code ?? 'Not generated' }}</p>
    </div>
</div>

<div class="rounded-lg bg-card p-6 shadow">
    <h2 class="mb-4 text-lg font-semibold">Journey steps</h2>
    <ol class="space-y-4">
        @forelse($journey->steps as $step)
            <li class="border-l-2 border-primary pl-4">
                <div class="flex flex-wrap items-baseline gap-2">
                    <span class="font-semibold">{{ $step->step_order }}. {{ ucfirst($step->type) }}</span>
                    <span class="text-sm text-muted-foreground">{{ $step->step_date?->format('Y-m-d') }}</span>
                </div>
                <p class="text-sm text-foreground">{{ $step->location }}</p>
                @if($step->description)
                    <p class="text-sm text-muted-foreground">{{ $step->description }}</p>
                @endif
            </li>
        @empty
            <li class="text-sm text-muted-foreground">No steps recorded.</li>
        @endforelse
    </ol>
</div>
@endsection

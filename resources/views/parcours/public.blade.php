@extends('layouts.front')

@section('title', 'Parcours de traçabilité')

@section('content')
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wider text-primary">Traçabilité</p>
    <h1 class="mt-2 text-3xl font-bold">{{ $parcours->produit?->name ?? 'Produit #'.$parcours->produit_id }}</h1>
    <p class="mt-2 text-muted-foreground">Parcours de production et de distribution.</p>
</div>

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Score environnemental</p>
        <p class="mt-1 text-3xl font-bold text-primary">{{ $parcours->score_environnemental }}/100</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Distance totale</p>
        <p class="mt-1 text-3xl font-bold">{{ $parcours->distance_totale_km }} km</p>
    </div>
</div>

<section class="rounded-lg bg-card p-6 shadow">
    <h2 class="mb-6 text-xl font-semibold">Chronologie</h2>
    <ol class="space-y-6">
        @forelse($parcours->etapes as $etape)
            <li class="flex gap-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary font-bold text-primary-foreground">{{ $etape->ordre }}</span>
                <div>
                    <h3 class="font-semibold">{{ ucfirst($etape->type) }} — {{ $etape->lieu }}</h3>
                    <p class="text-sm text-muted-foreground">{{ $etape->date_etape?->format('d/m/Y') }}</p>
                    @if($etape->description)
                        <p class="mt-1 text-sm">{{ $etape->description }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">Aucune étape disponible.</li>
        @endforelse
    </ol>
</section>

@if($parcours->resume_ia)
    <section class="mt-6 rounded-lg bg-card p-6 shadow">
        <h2 class="mb-2 text-xl font-semibold">Résumé</h2>
        <p class="text-muted-foreground">{{ $parcours->resume_ia }}</p>
    </section>
@endif
@endsection

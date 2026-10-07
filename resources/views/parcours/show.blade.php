@extends('layouts.back')

@section('title', 'Détail du parcours')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('processor.parcours.index') }}" class="text-muted-foreground hover:text-foreground">← Retour</a>
    <div>
        <h1 class="text-2xl font-bold">Détail du parcours</h1>
        <p class="mt-1 text-muted-foreground">{{ $parcours->produit?->name ?? 'Produit #'.$parcours->produit_id }}</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Score environnemental</p>
        <p class="mt-1 text-2xl font-bold text-primary">{{ $parcours->score_environnemental }}/100</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Distance totale</p>
        <p class="mt-1 text-2xl font-bold">{{ $parcours->distance_totale_km }} km</p>
    </div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Code QR</p>
        <p class="mt-1 break-all text-sm font-medium">{{ $parcours->code_qr ?? 'Non généré' }}</p>
    </div>
</div>

<div class="rounded-lg bg-card p-6 shadow">
    <h2 class="mb-4 text-lg font-semibold">Étapes du parcours</h2>
    <ol class="space-y-4">
        @forelse($parcours->etapes as $etape)
            <li class="border-l-2 border-primary pl-4">
                <div class="flex flex-wrap items-baseline gap-2">
                    <span class="font-semibold">{{ $etape->ordre }}. {{ ucfirst($etape->type) }}</span>
                    <span class="text-sm text-muted-foreground">{{ $etape->date_etape?->format('d/m/Y') }}</span>
                </div>
                <p class="text-sm text-foreground">{{ $etape->lieu }}</p>
                @if($etape->description)
                    <p class="text-sm text-muted-foreground">{{ $etape->description }}</p>
                @endif
            </li>
        @empty
            <li class="text-sm text-muted-foreground">Aucune étape enregistrée.</li>
        @endforelse
    </ol>
</div>
@endsection

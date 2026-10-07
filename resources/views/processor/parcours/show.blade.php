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
    <div class="rounded-lg bg-card p-5 shadow"><p class="text-sm text-muted-foreground">Score environnemental</p><p class="mt-1 text-2xl font-bold text-primary">{{ $parcours->score_environnemental }}/100</p></div>
    <div class="rounded-lg bg-card p-5 shadow"><p class="text-sm text-muted-foreground">Distance totale</p><p class="mt-1 text-2xl font-bold">{{ $parcours->distance_totale_km }} km</p></div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">Code QR</p>
        <p class="mt-1 break-all text-sm font-medium">{{ $parcours->code_qr ?? 'Non généré' }}</p>
        @if($parcours->code_qr)
            <a href="{{ route('processor.parcours.qr', $parcours) }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">Voir le QR code</a>
        @endif
    </div>
</div>

<div class="rounded-lg bg-card p-6 shadow">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Étapes du parcours</h2>
        <a href="{{ route('processor.parcours.etapes.create', $parcours) }}" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">Ajouter une étape</a>
    </div>
    <ol class="space-y-4">
        @forelse($parcours->etapes as $etape)
            <li class="flex items-start justify-between border-l-2 border-primary pl-4">
                <div>
                    <div class="flex flex-wrap items-baseline gap-2"><span class="font-semibold">{{ $etape->ordre }}. {{ ucfirst($etape->type) }}</span><span class="text-sm text-muted-foreground">{{ $etape->date_etape?->format('d/m/Y') }}</span></div>
                    <p class="text-sm">{{ $etape->lieu }}</p>
                    @if($etape->description)<p class="text-sm text-muted-foreground">{{ $etape->description }}</p>@endif
                </div>
                <div class="ml-4 flex shrink-0 gap-3 text-sm">
                    <a href="{{ route('processor.parcours.etapes.show', [$parcours, $etape]) }}" class="text-primary hover:underline">Détail</a>
                    <a href="{{ route('processor.parcours.etapes.edit', [$parcours, $etape]) }}" class="text-primary hover:underline">Modifier</a>
                    <form action="{{ route('processor.parcours.etapes.destroy', [$parcours, $etape]) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette étape ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Supprimer</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">Aucune étape enregistrée.</li>
        @endforelse
    </ol>
</div>
@endsection

@extends('layouts.back')

@section('title', 'Étapes du parcours')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('processor.parcours.show', $parcours) }}" class="text-sm text-muted-foreground hover:text-foreground">← Retour au parcours</a>
        <h1 class="mt-2 text-2xl font-bold">Étapes du parcours</h1>
        <p class="mt-1 text-muted-foreground">{{ $parcours->produit?->name ?? 'Produit #'.$parcours->produit_id }}</p>
    </div>
    <a href="{{ route('processor.parcours.etapes.create', $parcours) }}" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">Ajouter une étape</a>
</div>

<div class="overflow-hidden rounded-lg bg-card shadow">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Ordre</th>
                <th class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Type</th>
                <th class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Lieu</th>
                <th class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Date</th>
                <th class="px-6 py-3 text-right text-xs uppercase text-muted-foreground">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($parcours->etapes as $etape)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">{{ $etape->ordre }}</td>
                    <td class="px-6 py-4 text-sm">{{ ucfirst($etape->type) }}</td>
                    <td class="px-6 py-4 text-sm">{{ $etape->lieu }}</td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $etape->date_etape?->format('d/m/Y') }}</td>
                    <td class="space-x-3 px-6 py-4 text-right text-sm">
                        <a href="{{ route('processor.parcours.etapes.show', [$parcours, $etape]) }}" class="text-primary hover:underline">Détail</a>
                        <a href="{{ route('processor.parcours.etapes.edit', [$parcours, $etape]) }}" class="text-primary hover:underline">Modifier</a>
                        <form action="{{ route('processor.parcours.etapes.destroy', [$parcours, $etape]) }}" method="POST" class="inline" onsubmit="return confirm('Voulez-vous vraiment supprimer cette étape ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-destructive hover:underline">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-6 text-center text-sm text-muted-foreground">Aucune étape enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

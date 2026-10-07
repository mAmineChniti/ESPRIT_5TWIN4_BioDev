@extends('layouts.back')

@section('title', 'Parcours')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Parcours de traçabilité</h1>
    <p class="mt-1 text-muted-foreground">Consultez les parcours générés pour les produits.</p>
</div>

<div class="overflow-hidden rounded-lg bg-card shadow">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Produit</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Score</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Étapes</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border bg-card">
            @forelse($parcours as $item)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-foreground">
                        {{ $item->produit?->name ?? 'Produit #'.$item->produit_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $item->score_environnemental }}/100</td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $item->etapes_count }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('processor.parcours.show', $item) }}" class="text-primary hover:underline">Détails</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-sm text-muted-foreground">Aucun parcours généré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $parcours->links() }}</div>
@endsection

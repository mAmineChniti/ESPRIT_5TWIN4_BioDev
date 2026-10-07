@extends('layouts.back')

@section('title', 'Demandes de Fermes')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">Demandes de Fermes</h1>
                <april:badge variant="secondary">
                    {{ $requests->total() }} en attente
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                Une ferme n'est publiée sur le site public qu'une fois validée.
            </p>
        </div>
        <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
            Retour à la liste
        </april:button-link>
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Fermes en attente de validation</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Ferme</th>
                            <th scope="col" class="px-6 py-3">Producteur</th>
                            <th scope="col" class="px-6 py-3">Région</th>
                            <th scope="col" class="px-6 py-3">Surface</th>
                            <th scope="col" class="px-6 py-3">Déposée le</th>
                            <th scope="col" class="px-6 py-3 text-right">Décision</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($requests as $farm)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    <div>{{ $farm->name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $farm->address }}</div>
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">
                                    {{ $farm->producer_name ?? ($farm->user?->name ?? 'Non spécifié') }}
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $farm->region?->name ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ number_format((float) $farm->surface_hectares, 1) }} ha</td>
                                <td class="px-6 py-4 font-mono text-xs text-muted-foreground">
                                    {{ $farm->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('back.farms.approve', $farm) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <april:button type="submit" size="sm">
                                                <x-lucide-check class="mr-1 size-3.5" />
                                                Accepter
                                            </april:button>
                                        </form>

                                        <x-confirm-action
                                            :action="route('back.farms.reject', $farm)"
                                            method="PATCH"
                                            label="Confirmer le refus"
                                            title="Refuser cette demande ?"
                                            description="Le motif sera communiqué au producteur. La ferme ne sera pas publiée."
                                            trigger-variant="destructive"

                                        >
                                            <x-lucide-x class="mr-1 size-3.5" />
                                            Refuser
                                        </x-confirm-action>

                                        <april:button-link
                                            href="{{ route('back.farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Voir les détails de {{ $farm->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                    Aucune demande en attente de validation.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($requests->hasPages())
            <x-slot:footer>
                {{ $requests->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
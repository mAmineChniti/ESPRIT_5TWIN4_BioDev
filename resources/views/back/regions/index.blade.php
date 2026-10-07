@extends('layouts.back')

@section('title', 'Gestion des Régions Agricoles')

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">Régions Agricoles</h1>
                <april:badge variant="{{ $isAdmin ? 'default' : 'secondary' }}">
                    {{ $isAdmin ? 'Supervision Admin' : 'Espace Producteur' }}
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                @if($isAdmin)
                    Supervision et création globale des régions de production agricole.
                @else
                    Consultez la liste des régions disponibles créées par l'administrateur pour y rattacher vos fermes.
                @endif
            </p>
        </div>

        {{-- Creating a region is admin-only; the route enforces it too. --}}
        @if($isAdmin)
            <april:button-link href="{{ route('back.regions.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                Ajouter une Région
            </april:button-link>
        @endif
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Régions agricoles</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Code</th>
                            <th scope="col" class="px-6 py-3">Nom de la Région</th>
                            <th scope="col" class="px-6 py-3">Climat</th>
                            <th scope="col" class="px-6 py-3">Type de Sol</th>
                            <th scope="col" class="px-6 py-3">Fermes Rattachées</th>
                            <th scope="col" class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($regions as $region)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-primary">{{ $region->code }}</td>
                                <td class="px-6 py-4 font-medium text-foreground">{{ $region->name }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $region->climate ?? 'Non spécifié' }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $region->soil_type ?? 'Non spécifié' }}</td>
                                <td class="px-6 py-4">
                                    <april:badge variant="none" class="bg-primary/10 text-primary">
                                        {{ $region->farms_count }} ferme(s)
                                    </april:badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <april:button-link
                                            href="{{ route('back.regions.show', $region) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Voir {{ $region->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>

                                        @if($isAdmin)
                                            <april:button-link
                                                href="{{ route('back.regions.edit', $region) }}"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="Modifier {{ $region->name }}"
                                            >
                                                <x-lucide-pencil class="size-4" />
                                            </april:button-link>

                                            <x-confirm-action
                                                :action="route('back.regions.destroy', $region)"
                                                label="Supprimer la région"
                                                title="Supprimer cette région ?"
                                                description="« {{ $region->name }} » et ses {{ $region->farms_count }} ferme(s) seront définitivement supprimées. Cette action est irréversible."
                                            >
                                                <x-lucide-trash-2 class="size-4" />
                                                <span class="sr-only">Supprimer {{ $region->name }}</span>
                                            </x-confirm-action>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                    Aucune région agricole enregistrée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($regions->hasPages())
            <x-slot:footer>
                {{ $regions->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
@extends('layouts.back')

@section('title', 'Gestion des Régions Agricoles')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">Régions Agricoles</h1>
                @if(Auth::user()?->isAdmin())
                    <span class="rounded-md bg-red-500/10 px-2.5 py-1 text-xs font-bold text-red-600 border border-red-200 uppercase">
                        Supervision Admin
                    </span>
                @else
                    <span class="rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-bold text-emerald-600 border border-emerald-200 uppercase">
                        Espace Producteur
                    </span>
                @endif
            </div>
            <p class="text-sm text-muted-foreground mt-1">
                @if(Auth::user()?->isAdmin())
                    Supervision et création globale des régions de production agricole.
                @else
                    Consultez la liste des régions disponibles créées par l'administrateur pour y rattacher vos fermes.
                @endif
            </p>
        </div>
        @if(Auth::user()?->isAdmin())
            <april:button-link href="{{ route('back.regions.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                Ajouter une Région
            </april:button-link>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th class="px-6 py-3">Code</th>
                        <th class="px-6 py-3">Nom de la Région</th>
                        <th class="px-6 py-3">Climat</th>
                        <th class="px-6 py-3">Type de Sol</th>
                        <th class="px-6 py-3">Fermes Rattachées</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($regions as $region)
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-primary">{{ $region->code }}</td>
                            <td class="px-6 py-4 font-medium text-foreground">{{ $region->name }}</td>
                            <td class="px-6 py-4 text-muted-foreground">{{ $region->climate ?? 'Non spécifié' }}</td>
                            <td class="px-6 py-4 text-muted-foreground">{{ $region->soil_type ?? 'Non spécifié' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                    {{ $region->farms_count }} ferme(s)
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <april:button-link href="{{ route('back.regions.show', $region) }}" variant="ghost" size="sm">
                                        <x-lucide-eye class="size-4" />
                                    </april:button-link>
                                    @if(Auth::user()?->isAdmin())
                                        <april:button-link href="{{ route('back.regions.edit', $region) }}" variant="ghost" size="sm">
                                            <x-lucide-pencil class="size-4" />
                                        </april:button-link>
                                        <form method="POST" action="{{ route('back.regions.destroy', $region) }}"
                                            onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette région ?')">
                                            @csrf
                                            @method('DELETE')
                                            <april:button type="submit" variant="ghost" size="sm"
                                                class="text-destructive hover:text-destructive"
                                                title="Supprimer la région">
                                                <x-lucide-trash-2 class="size-4" />
                                            </april:button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                Aucune région agricole enregistrée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($regions->hasPages())
            <div class="p-4 border-t border-border">
                {{ $regions->links() }}
            </div>
        @endif
    </div>
@endsection
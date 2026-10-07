@extends('layouts.back')

@section('title', $region->name)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">{{ $region->name }}</h1>
                <span class="font-mono text-xs font-bold rounded-md bg-primary/10 px-2 py-1 text-primary">{{ $region->code }}</span>
            </div>
            <p class="text-sm text-muted-foreground mt-1">Détails de la région agricole et des fermes associées.</p>
        </div>
        <div class="flex gap-2">
            @if(Auth::user()?->isAdmin())
                <april:button-link href="{{ route('back.regions.edit', $region) }}" variant="outline">
                    <x-lucide-pencil class="mr-2 size-4" /> Modifier
                </april:button-link>
            @endif
            <april:button-link href="{{ route('back.regions.index') }}" variant="ghost">
                Retour
            </april:button-link>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Climat</span>
            <p class="text-lg font-medium mt-1">{{ $region->climate ?? 'Non spécifié' }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Type de Sol</span>
            <p class="text-lg font-medium mt-1">{{ $region->soil_type ?? 'Non spécifié' }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Fermes Rattachées</span>
            <p class="text-lg font-bold text-primary mt-1">{{ $region->farms->count() }} exploitation(s)</p>
        </div>
    </div>

    @if($region->description)
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase text-muted-foreground mb-2">Description</h2>
            <p class="text-sm leading-relaxed text-foreground">{{ $region->description }}</p>
        </div>
    @endif

    <div class="rounded-xl border border-border bg-card shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Fermes dans cette Région</h2>
            <april:button-link href="{{ route('back.farms.create') }}?region_id={{ $region->id }}" size="sm">
                <x-lucide-plus class="mr-2 size-4" /> Ajouter une Ferme
            </april:button-link>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Nom de la Ferme</th>
                        <th class="px-4 py-3">Producteur</th>
                        <th class="px-4 py-3">Surface</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($region->farms as $farm)
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ $farm->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $farm->producer_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ number_format($farm->surface_hectares, 1) }} ha</td>
                            <td class="px-4 py-3">
                                @if($farm->isPending())
                                    <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-700">🟡 En attente</span>
                                @elseif($farm->isRejected())
                                    <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-xs font-semibold text-rose-700">🔴 Refusée</span>
                                @else
                                    <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-700">🟢 Validée</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <april:button-link href="{{ route('back.farms.show', $farm) }}" variant="ghost" size="sm">
                                    <x-lucide-eye class="size-4" />
                                </april:button-link>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                                Aucune ferme enregistrée dans cette région.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

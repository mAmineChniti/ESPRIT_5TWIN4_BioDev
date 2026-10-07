@extends('layouts.back')

@section('title', $region->name)

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $region->name }}</h1>
                    <april:badge variant="none" class="font-mono">{{ $region->code }}</april:badge>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">Détails de la région agricole et des fermes associées.</p>
            </div>
            <div class="flex gap-2">
                @if($isAdmin)
                    <april:button-link href="{{ route('back.regions.edit', $region) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        Modifier
                    </april:button-link>
                @endif
                <april:button-link href="{{ route('back.regions.index') }}" variant="ghost">
                    Retour
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Climat</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->climate ?? 'Non spécifié' }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Type de Sol</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->soil_type ?? 'Non spécifié' }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Fermes Rattachées</span>
                    <p class="mt-1 text-lg font-bold text-primary">{{ $region->farms->count() }} exploitation(s)</p>
                </x-slot:content>
            </april:card>
        </div>

        @if($region->description)
            <april:card>
                <x-slot:title>Description</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $region->description }}</p>
                </x-slot:content>
            </april:card>
        @endif

        <april:card>
            <x-slot:title>Fermes dans cette Région</x-slot:title>
            {{-- The controller scopes this to the viewer's own farms unless
                 the viewer is an admin, so a producer never sees another's. --}}
            <x-slot:content>
                <div class="mb-4">
                    <april:button-link href="{{ route('back.farms.create') }}?region_id={{ $region->id }}" size="sm">
                        <x-lucide-plus class="mr-2 size-4" />
                        Ajouter une Ferme
                    </april:button-link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Fermes rattachées à {{ $region->name }}</caption>
                        <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th scope="col" class="px-4 py-3">Nom de la Ferme</th>
                                <th scope="col" class="px-4 py-3">Producteur</th>
                                <th scope="col" class="px-4 py-3">Surface</th>
                                <th scope="col" class="px-4 py-3">Statut</th>
                                <th scope="col" class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($region->farms as $farm)
                                <tr class="transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium text-foreground">{{ $farm->name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $farm->producer_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ number_format((float) $farm->surface_hectares, 1) }} ha</td>
                                    <td class="px-4 py-3">
                                        <x-farm-status :status="$farm->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <april:button-link
                                            href="{{ route('back.farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Voir {{ $farm->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        Aucune ferme enregistrée dans cette région.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-slot:content>
        </april:card>
    </div>
@endsection
@extends('layouts.back')

@section('title', 'Gestion des Fermes')

@section('content')
    @php
        use App\Enums\FarmStatus;

        $isAdmin = auth()->user()->isAdmin();
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">Fermes &amp; Exploitations Agricoles</h1>
                <april:badge variant="{{ $isAdmin ? 'default' : 'secondary' }}">
                    {{ $isAdmin ? 'Supervision Admin' : 'Espace Producteur' }}
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                @if($isAdmin)
                    Supervision et contrôle d'administration globale de l'ensemble des exploitations agricoles du pays.
                @else
                    Gérez vos fermes de production et suivez l'état de validation de vos demandes.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($isAdmin && $pendingCount > 0)
                <april:button-link href="{{ route('back.farms.requests') }}">
                    <x-lucide-clipboard-check class="mr-2 size-4" />
                    Demandes en attente ({{ $pendingCount }})
                </april:button-link>
            @endif

            <april:button-link href="{{ route('back.farms.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                Ajouter une Ferme
            </april:button-link>
        </div>
    </div>

    {{-- ===== STATISTIQUES KPIs ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Fermes</span>
                <span class="text-3xl font-bold text-foreground">{{ $stats['total'] }}</span>
                <span class="text-xs text-muted-foreground">exploitations enregistrées</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-primary">Validées</span>
                <span class="text-3xl font-bold text-primary">{{ $stats['validees'] }}</span>
                <span class="text-xs text-primary/70">
                    {{ $stats['total'] > 0 ? round($stats['validees'] / $stats['total'] * 100) : 0 }}% du total
                </span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">En attente</span>
                <span class="text-3xl font-bold text-foreground">{{ $stats['en_attente'] }}</span>
                <span class="text-xs text-muted-foreground">demandes à traiter</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-destructive">Refusées</span>
                <span class="text-3xl font-bold text-destructive">{{ $stats['refusees'] }}</span>
                <span class="text-xs text-muted-foreground">demandes refusées</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-primary">Surface Validée</span>
                <span class="text-3xl font-bold text-primary">{{ number_format((float) $stats['surface'], 1) }}</span>
                <span class="text-xs text-muted-foreground">hectares exploités</span>
            </x-slot:content>
        </april:card>
    </div>

    {{-- ===== FILTRE PAR STATUT ===== --}}
    <div class="mb-4 flex items-center gap-2 overflow-x-auto border-b border-border pb-3">
        <span class="mr-2 shrink-0 text-xs font-semibold uppercase text-muted-foreground">Filtrer par statut :</span>

        <april:button-link
            href="{{ route('back.farms.index') }}"
            size="sm"
            variant="{{ $statusFilter === null ? 'default' : 'ghost' }}"
        >
            Toutes les fermes
        </april:button-link>

        @foreach(FarmStatus::cases() as $status)
            <april:button-link
                href="{{ route('back.farms.index', ['status' => $status->value]) }}"
                size="sm"
                variant="{{ $statusFilter === $status ? 'default' : 'ghost' }}"
                @class([
                    'font-semibold' => $statusFilter === $status,
                ])
            >
                {{ $status->label() }}
            </april:button-link>
        @endforeach
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Fermes agricoles et leur statut de validation</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nom de la Ferme</th>
                            <th scope="col" class="px-6 py-3">Région Agricole</th>
                            <th scope="col" class="px-6 py-3">Producteur</th>
                            <th scope="col" class="px-6 py-3">Type de Sol</th>
                            <th scope="col" class="px-6 py-3">Surface</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($farms as $farm)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    <div>{{ $farm->name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $farm->address }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($farm->region)
                                        <a href="{{ route('back.regions.show', $farm->region) }}" class="font-medium text-primary hover:underline">
                                            {{ $farm->region->name }}
                                        </a>
                                    @else
                                        <span class="text-xs text-muted-foreground">Aucune</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? 'Non spécifié') }}</td>
                                <td class="px-6 py-4 text-muted-foreground">
                                    {{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Non spécifié') }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">{{ number_format((float) $farm->surface_hectares, 1) }} ha</td>
                                <td class="px-6 py-4">
                                    <x-farm-status :status="$farm->status" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Approve and reject are admin-only and
                                             only offered on an open request. --}}
                                        @if($isAdmin && $farm->isPending())
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
                                        @endif

                                        <april:button-link
                                            href="{{ route('back.farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Voir les détails de {{ $farm->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>

                                        @if($farm->canBeEdited())
                                            <april:button-link
                                                href="{{ route('back.farms.edit', $farm) }}"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="Modifier {{ $farm->name }}"
                                            >
                                                <x-lucide-pencil class="size-4" />
                                            </april:button-link>
                                        @endif

                                        <x-confirm-action
                                            :action="route('back.farms.destroy', $farm)"
                                            label="Supprimer la ferme"
                                            title="Supprimer cette ferme ?"
                                            description="« {{ $farm->name }} » sera définitivement supprimée. Cette action est irréversible."
                                        >
                                            <x-lucide-trash-2 class="size-4" />
                                            <span class="sr-only">Supprimer {{ $farm->name }}</span>
                                        </x-confirm-action>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                                    @unless($isAdmin)
                                        <p class="text-base font-semibold text-foreground">Vous n'avez pas encore de ferme.</p>
                                        <p class="mx-auto mt-1 max-w-sm text-xs text-muted-foreground">
                                            Vous n'avez créé aucune ferme pour le moment. Cliquez sur le bouton ci-dessous pour ajouter votre première exploitation.
                                        </p>
                                        <div class="mt-4">
                                            <april:button-link href="{{ route('back.farms.create') }}">
                                                <x-lucide-plus class="mr-2 size-4" />
                                                Ajouter une Ferme
                                            </april:button-link>
                                        </div>
                                    @else
                                        <p class="font-medium text-foreground">Aucune ferme enregistrée pour le moment.</p>
                                    @endunless
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($farms->hasPages())
            <x-slot:footer>
                {{ $farms->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
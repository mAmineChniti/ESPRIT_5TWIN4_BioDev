@extends('layouts.back')

@section('title', 'Gestion des Fermes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight">Fermes & Exploitations Agricoles</h1>
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
                Supervision et contrôle d'administration globale de l'ensemble des exploitations agricoles du pays.
            @else
                Gérez vos fermes de production et suivez l'état de validation de vos demandes.
            @endif
        </p>
    </div>
    <div class="flex items-center gap-3">
        @if(Auth::user()?->isAdmin() && ($pendingCount ?? 0) > 0)
            <april:button-link href="{{ route('back.farms.requests') }}" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold">
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

@if(session('success'))
    <div class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center justify-between">
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- ===== STATISTIQUES KPIs ===== --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col gap-1">
        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total Fermes</span>
        <span class="text-3xl font-bold text-foreground">{{ $stats['total'] }}</span>
        <span class="text-xs text-muted-foreground">exploitations enregistrées</span>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-500/5 p-4 shadow-sm flex flex-col gap-1">
        <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">✅ Validées</span>
        <span class="text-3xl font-bold text-emerald-700">{{ $stats['validees'] }}</span>
        <span class="text-xs text-emerald-600/70">
            @if($stats['total'] > 0)
                {{ round($stats['validees'] / $stats['total'] * 100) }}% du total
            @else
                0%
            @endif
        </span>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-500/5 p-4 shadow-sm flex flex-col gap-1">
        <span class="text-xs font-semibold text-amber-600 uppercase tracking-wide">🟡 En attente</span>
        <span class="text-3xl font-bold text-amber-700">{{ $stats['en_attente'] }}</span>
        <span class="text-xs text-amber-600/70">demandes à traiter</span>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-500/5 p-4 shadow-sm flex flex-col gap-1">
        <span class="text-xs font-semibold text-rose-600 uppercase tracking-wide">🔴 Refusées</span>
        <span class="text-3xl font-bold text-rose-700">{{ $stats['refusees'] }}</span>
        <span class="text-xs text-rose-600/70">demandes refusées</span>
    </div>
    <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 shadow-sm flex flex-col gap-1">
        <span class="text-xs font-semibold text-primary uppercase tracking-wide">🌾 Surface Validée</span>
        <span class="text-3xl font-bold text-primary">{{ number_format($stats['surface'], 1) }}</span>
        <span class="text-xs text-primary/70">hectares exploités</span>
    </div>
</div>

<div class="flex items-center gap-2 mb-4 border-b border-border pb-3 overflow-x-auto">
    <span class="text-xs font-semibold text-muted-foreground uppercase mr-2 shrink-0">Filtrer par statut :</span>
    <a href="{{ route('back.farms.index') }}"
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shrink-0 {{ empty($statusFilter) ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
        Toutes les fermes
    </a>
    <a href="{{ route('back.farms.index', ['status' => 'en_attente']) }}"
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shrink-0 flex items-center gap-1.5 {{ $statusFilter === 'en_attente' ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-500/10 text-amber-700 border border-amber-200 hover:bg-amber-500/20' }}">
        🟡 En attente
    </a>
    <a href="{{ route('back.farms.index', ['status' => 'validee']) }}"
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shrink-0 flex items-center gap-1.5 {{ $statusFilter === 'validee' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-500/10 text-emerald-700 border border-emerald-200 hover:bg-emerald-500/20' }}">
        🟢 Validées
    </a>
    <a href="{{ route('back.farms.index', ['status' => 'refusee']) }}"
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shrink-0 flex items-center gap-1.5 {{ $statusFilter === 'refusee' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-500/10 text-rose-700 border border-rose-200 hover:bg-rose-500/20' }}">
        🔴 Refusées
    </a>
</div>

<div class="rounded-xl border border-border bg-card shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                <tr>
                    <th class="px-6 py-3">Nom de la Ferme</th>
                    <th class="px-6 py-3">Région Agricole</th>
                    <th class="px-6 py-3">Producteur</th>
                    <th class="px-6 py-3">Type de Sol</th>
                    <th class="px-6 py-3">Surface</th>
                    <th class="px-6 py-3">Statut</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($farms as $farm)
                    <tr class="hover:bg-muted/30 transition-colors">
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
                                <span class="text-muted-foreground text-xs">Aucune</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-muted-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? 'Non spécifié') }}</td>
                        <td class="px-6 py-4 text-muted-foreground">
                            {{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Non spécifié') }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs">{{ number_format($farm->surface_hectares, 1) }} ha</td>
                        <td class="px-6 py-4">
                            @if($farm->isPending())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    🟡 En attente
                                </span>
                            @elseif($farm->isRejected())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-700 border border-rose-200">
                                    🔴 Refusée
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                    🟢 Validée
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-2">
                                @if(Auth::user()?->isAdmin() && $farm->isPending())
                                    <form method="POST" action="{{ route('back.farms.approve', $farm) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <april:button type="submit" size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs py-1 px-2.5">
                                            <x-lucide-check class="mr-1 size-3.5" />
                                            Accepter
                                        </april:button>
                                    </form>
                                    <form method="POST" action="{{ route('back.farms.reject', $farm) }}" class="inline" onsubmit="return confirm('Refuser cette demande ?')">
                                        @csrf
                                        @method('PATCH')
                                        <april:button type="submit" variant="destructive" size="sm" class="text-xs py-1 px-2.5">
                                            <x-lucide-x class="mr-1 size-3.5" />
                                            Refuser
                                        </april:button>
                                    </form>
                                @endif
                                <april:button-link href="{{ route('back.farms.show', $farm) }}" variant="ghost" size="sm" title="Voir les détails">
                                    <x-lucide-eye class="size-4" />
                                </april:button-link>
                                @if(! $farm->isRejected())
                                    <april:button-link href="{{ route('back.farms.edit', $farm) }}" variant="ghost" size="sm" title="Modifier">
                                        <x-lucide-pencil class="size-4" />
                                    </april:button-link>
                                @endif
                                <form method="POST" action="{{ route('back.farms.destroy', $farm) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette ferme ?')">
                                    @csrf
                                    @method('DELETE')
                                    <april:button type="submit" variant="ghost" size="sm" class="text-destructive hover:text-destructive" title="Supprimer">
                                        <x-lucide-trash-2 class="size-4" />
                                    </april:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                            @if(!Auth::user()?->isAdmin())
                                <p class="text-base font-semibold text-foreground">Vous n'avez pas encore de ferme.</p>
                                <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
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
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($farms->hasPages())
        <div class="p-4 border-t border-border">
            {{ $farms->links() }}
        </div>
    @endif
</div>
@endsection

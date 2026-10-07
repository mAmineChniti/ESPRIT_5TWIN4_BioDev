@extends('layouts.back')

@section('title', 'Demandes de Fermes en Attente')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight">Demandes de Fermes</h1>
            <span class="rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-bold text-amber-600 border border-amber-200 uppercase">
                Validation Admin
            </span>
        </div>
        <p class="text-sm text-muted-foreground mt-1">
            Consultez et validez les demandes d'ajout de fermes soumises par les producteurs.
        </p>
    </div>
    <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
        <x-lucide-arrow-left class="mr-2 size-4" />
        Toutes les Fermes
    </april:button-link>
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
                    <th class="px-6 py-3">Nom du Producteur</th>
                    <th class="px-6 py-3">Nom de la Ferme</th>
                    <th class="px-6 py-3">Région</th>
                    <th class="px-6 py-3">Type de Sol</th>
                    <th class="px-6 py-3">Description</th>
                    <th class="px-6 py-3">Date de Création</th>
                    <th class="px-6 py-3">Statut</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($requests as $farm)
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="px-6 py-4 font-medium text-foreground">
                            <div>{{ $farm->producer_name ?? ($farm->user?->name ?? 'Producteur anonyme') }}</div>
                            @if($farm->user?->email)
                                <div class="text-xs text-muted-foreground">{{ $farm->user->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-foreground">
                            <div>{{ $farm->name }}</div>
                            <div class="text-xs text-muted-foreground">{{ $farm->address }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($farm->region)
                                <span class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                                    <x-lucide-map-pin class="size-3" />
                                    {{ $farm->region->name }}
                                </span>
                            @else
                                <span class="text-muted-foreground text-xs">Non spécifiée</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-muted-foreground">
                            {{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Non spécifié') }}
                        </td>
                        <td class="px-6 py-4 text-muted-foreground max-w-xs truncate" title="{{ $farm->description }}">
                            {{ $farm->description ?: 'Aucune description' }}
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-muted-foreground whitespace-nowrap">
                            {{ $farm->created_at ? $farm->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 border border-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                🟡 En attente
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <april:button-link href="{{ route('back.farms.show', $farm) }}" variant="ghost" size="sm" title="Voir les détails">
                                    <x-lucide-eye class="size-4" />
                                </april:button-link>
                                <form method="POST" action="{{ route('back.farms.approve', $farm) }}">
                                    @csrf
                                    @method('PATCH')
                                    <april:button type="submit" size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                                        <x-lucide-check class="mr-1 size-4" />
                                        Accepter
                                    </april:button>
                                </form>
                                {{-- Bouton Refuser ouvre un modal pour saisir le motif --}}
                                <button type="button"
                                    onclick="openRejectModal({{ $farm->id }}, '{{ addslashes($farm->name) }}')"
                                    class="inline-flex items-center gap-1 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5 transition-colors">
                                    <x-lucide-x class="size-3.5" />
                                    Refuser
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-muted-foreground">
                            <x-lucide-circle-check class="mx-auto size-10 text-emerald-500/50 mb-2" />
                            <p class="font-medium text-foreground">Aucune demande en attente</p>
                            <p class="text-xs text-muted-foreground mt-1">Toutes les demandes de fermes ont été traitées.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div class="p-4 border-t border-border">
            {{ $requests->links() }}
        </div>
    @endif
</div>

{{-- ===== MODAL MOTIF DE REFUS ===== --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-card border border-border rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 space-y-5">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold text-foreground">Refuser la demande</h2>
                <p class="text-sm text-muted-foreground mt-0.5" id="modalFarmName"></p>
            </div>
            <button onclick="closeRejectModal()" class="text-muted-foreground hover:text-foreground transition-colors">
                <x-lucide-x class="size-5" />
            </button>
        </div>

        <div class="p-3 rounded-lg bg-rose-500/10 border border-rose-200 text-rose-700 text-xs flex items-start gap-2">
            <x-lucide-alert-triangle class="size-4 shrink-0 mt-0.5" />
            <span>Ce motif sera <strong>visible par le producteur</strong> dans sa page "Fermes & Exploitations". Il l'aidera à corriger et soumettre une nouvelle demande.</span>
        </div>

        <form id="rejectForm" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="space-y-2">
                <label for="rejection_reason" class="text-sm font-semibold text-foreground">
                    Motif de refus <span class="text-destructive">*</span>
                </label>
                <textarea
                    id="rejection_reason"
                    name="rejection_reason"
                    rows="4"
                    required
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 resize-none"
                    placeholder="Ex: Informations incomplètes, adresse incorrecte, surface déclarée incorrecte, région non valide..."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeRejectModal()"
                    class="px-4 py-2 rounded-lg border border-border text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                    Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold transition-colors flex items-center gap-2">
                    <x-lucide-x class="size-4" />
                    Confirmer le refus
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(farmId, farmName) {
    document.getElementById('modalFarmName').textContent = 'Ferme : ' + farmName;
    document.getElementById('rejectForm').action = '/farms/' + farmId + '/reject';
    document.getElementById('rejection_reason').value = '';
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
// Close on backdrop click
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endsection

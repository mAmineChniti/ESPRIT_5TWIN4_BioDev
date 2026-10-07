@extends('layouts.back')

@section('title', $farm->name)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    @if(session('success'))
        <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-200 text-emerald-700 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- ===== MOTIF DE REFUS (visible par le producteur) ===== --}}
    @if($farm->isRejected() && $farm->rejection_reason)
        <div class="p-5 rounded-xl bg-rose-500/10 border border-rose-300 text-rose-800 shadow-sm">
            <div class="flex items-start gap-3">
                <x-lucide-alert-triangle class="size-5 shrink-0 mt-0.5 text-rose-500" />
                <div class="space-y-1">
                    <p class="font-bold text-sm">⛔ Demande refusée — Motif communiqué par l'administrateur :</p>
                    <p class="text-sm leading-relaxed italic">"{{ $farm->rejection_reason }}"</p>
                    <p class="text-xs text-rose-600/70 mt-2">Vous pouvez corriger votre dossier et soumettre une nouvelle demande de ferme.</p>
                </div>
            </div>
        </div>
    @elseif($farm->isRejected())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-300 text-rose-700 text-sm">
            <x-lucide-alert-triangle class="inline size-4 mr-1" />
            Cette demande a été refusée. Aucun motif n'a été fourni. Contactez l'administration pour plus d'informations.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">{{ $farm->name }}</h1>
                <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600">
                    {{ $farm->farming_type }}
                </span>
                @if($farm->isPending())
                    <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">
                        🟡 En attente
                    </span>
                @elseif($farm->isRejected())
                    <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">
                        🔴 Refusée
                    </span>
                @else
                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                        🟢 Validée
                    </span>
                @endif
            </div>
            <p class="text-sm text-muted-foreground mt-1">{{ $farm->address }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if(Auth::user()?->isAdmin() && $farm->isPending())
                <form method="POST" action="{{ route('back.farms.approve', $farm) }}">
                    @csrf
                    @method('PATCH')
                    <april:button type="submit" size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                        <x-lucide-check class="mr-1 size-4" /> Accepter
                    </april:button>
                </form>
                <button type="button"
                    onclick="document.getElementById('rejectModalShow').classList.remove('hidden')"
                    class="inline-flex items-center gap-1 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5 transition-colors">
                    <x-lucide-x class="size-3.5" /> Refuser
                </button>
            @endif
            @if($farm->isApproved())
                <april:button-link href="{{ route('back.farms.edit', $farm) }}" variant="outline">
                    <x-lucide-pencil class="mr-2 size-4" /> Modifier
                </april:button-link>
            @endif
            <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">
                Retour
            </april:button-link>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Région Agricole</span>
            @if($farm->region)
                <p class="text-lg font-bold text-primary mt-1">
                    <a href="{{ route('back.regions.show', $farm->region) }}" class="hover:underline">
                        {{ $farm->region->name }}
                    </a>
                </p>
                <span class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
            @else
                <p class="text-lg font-medium mt-1 text-muted-foreground">Non attribuée</p>
            @endif
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Producteur</span>
            <p class="text-lg font-medium mt-1 text-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? 'Non renseigné') }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Type de Sol</span>
            <p class="text-lg font-medium mt-1 text-foreground">{{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Non renseigné') }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Surface</span>
            <p class="text-lg font-bold font-mono text-foreground mt-1">{{ number_format($farm->surface_hectares, 2) }} ha</p>
        </div>
    </div>

    <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-semibold uppercase text-muted-foreground">Informations de contact & création</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-muted-foreground block text-xs">Téléphone :</span>
                <span class="font-medium">{{ $farm->phone ?? 'Non renseigné' }}</span>
            </div>
            <div>
                <span class="text-muted-foreground block text-xs">Adresse complète :</span>
                <span class="font-medium">{{ $farm->address }}</span>
            </div>
            <div>
                <span class="text-muted-foreground block text-xs">Date de création :</span>
                <span class="font-medium font-mono text-xs">{{ $farm->created_at ? $farm->created_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
    </div>

    @if($farm->description)
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase text-muted-foreground mb-2">Description & Spécificités</h2>
            <p class="text-sm leading-relaxed text-foreground">{{ $farm->description }}</p>
        </div>
    @endif
</div>

{{-- ===== MODAL MOTIF DE REFUS (Admin - page détail) ===== --}}
@if(Auth::user()?->isAdmin() && $farm->isPending())
<div id="rejectModalShow" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-card border border-border rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 space-y-5">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold text-foreground">Refuser la demande</h2>
                <p class="text-sm text-muted-foreground">Ferme : {{ $farm->name }}</p>
            </div>
            <button onclick="document.getElementById('rejectModalShow').classList.add('hidden')" class="text-muted-foreground hover:text-foreground">
                <x-lucide-x class="size-5" />
            </button>
        </div>
        <div class="p-3 rounded-lg bg-rose-500/10 border border-rose-200 text-rose-700 text-xs flex items-start gap-2">
            <x-lucide-alert-triangle class="size-4 shrink-0 mt-0.5" />
            <span>Ce motif sera <strong>visible par le producteur</strong> afin qu'il puisse corriger sa demande.</span>
        </div>
        <form method="POST" action="{{ route('back.farms.reject', $farm) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="space-y-2">
                <label for="rejection_reason_show" class="text-sm font-semibold text-foreground">
                    Motif de refus <span class="text-destructive">*</span>
                </label>
                <textarea
                    id="rejection_reason_show"
                    name="rejection_reason"
                    rows="4"
                    required
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 resize-none"
                    placeholder="Ex: Informations incomplètes, adresse incorrecte..."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('rejectModalShow').classList.add('hidden')"
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
@endif
@endsection

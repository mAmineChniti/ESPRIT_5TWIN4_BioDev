@extends('layouts.back')

@section('title', 'Ajouter une Ferme')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Nouvelle Ferme / Exploitation</h1>
            <p class="text-sm text-muted-foreground">
                @if(Auth::user()?->isAdmin())
                    Ajoutez directement une ferme validée et rattachée à une région agricole.
                @else
                    Remplissez les informations de votre ferme et envoyez-la pour validation auprès de l'administrateur.
                @endif
            </p>
        </div>
        <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
            Retour à la liste
        </april:button-link>
    </div>

    @if(!Auth::user()?->isAdmin())
        <div class="p-4 rounded-lg bg-amber-500/10 border border-amber-200 text-amber-800 text-sm flex items-start gap-3">
            <x-lucide-alert-circle class="size-5 text-amber-600 mt-0.5 shrink-0" />
            <div>
                <p class="font-semibold">Note importante :</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    Votre nouvelle ferme sera automatiquement placée en statut <strong class="underline">🟡 En attente de validation</strong>. Un administrateur doit accepter la demande avant qu'elle ne devienne validée.
                </p>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
        <form method="POST" action="{{ route('back.farms.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="agricultural_region_id" class="text-sm font-medium">Région Agricole <span class="text-destructive">*</span></label>
                <select id="agricultural_region_id" name="agricultural_region_id" required
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">-- Sélectionnez une région --</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" {{ old('agricultural_region_id', request('region_id')) == $region->id ? 'selected' : '' }}>
                            {{ $region->name }} ({{ $region->code }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-muted-foreground">Choisissez une région déjà créée par l'administrateur.</p>
                @error('agricultural_region_id') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="name" class="text-sm font-medium">Nom de la Ferme <span class="text-destructive">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ex: Ferme Ahmed">
                @error('name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="producer_name" class="text-sm font-medium">Nom du Producteur / Exploitant</label>
                    <input type="text" id="producer_name" name="producer_name" value="{{ old('producer_name', Auth::user()?->name) }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Ahmed">
                    @error('producer_name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="soil_type" class="text-sm font-medium">Type de Sol</label>
                    <input type="text" id="soil_type" name="soil_type" value="{{ old('soil_type') }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Sol argileux, Sol sableux, etc.">
                    @error('soil_type') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="address" class="text-sm font-medium">Adresse / Localisation <span class="text-destructive">*</span></label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" required
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Bizerte, Route de Mateur Km 3">
                    @error('address') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="phone" class="text-sm font-medium">Numéro de Téléphone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: +216 72 100 200">
                    @error('phone') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="surface_hectares" class="text-sm font-medium">Surface (Hectares) <span class="text-destructive">*</span></label>
                    <input type="number" step="0.01" min="0" id="surface_hectares" name="surface_hectares" value="{{ old('surface_hectares', '0.00') }}" required
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('surface_hectares') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="farming_type" class="text-sm font-medium">Type d'Agriculture <span class="text-destructive">*</span></label>
                    <select id="farming_type" name="farming_type" required
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="Biologique" {{ old('farming_type') == 'Biologique' ? 'selected' : '' }}>Biologique</option>
                        <option value="Raisonné" {{ old('farming_type') == 'Raisonné' ? 'selected' : '' }}>Raisonné</option>
                        <option value="Traditionnel" {{ old('farming_type') == 'Traditionnel' ? 'selected' : '' }}>Traditionnel</option>
                        <option value="Biodynamique" {{ old('farming_type') == 'Biodynamique' ? 'selected' : '' }}>Biodynamique</option>
                    </select>
                    @error('farming_type') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Ex: Sol fertile adapté aux cultures agricoles">{{ old('description') }}</textarea>
                @error('description') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">Annuler</april:button-link>
                <april:button type="submit" class="{{ Auth::user()?->isAdmin() ? '' : 'bg-amber-600 hover:bg-amber-700 text-white' }}">
                    <x-lucide-send class="mr-2 size-4" />
                    {{ Auth::user()?->isAdmin() ? 'Enregistrer la Ferme' : 'Envoyer pour validation' }}
                </april:button>
            </div>
        </form>
    </div>
</div>
@endsection

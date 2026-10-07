@extends('layouts.back')

@section('title', 'Modifier la Ferme')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Modifier : {{ $farm->name }}</h1>
            <p class="text-sm text-muted-foreground">Mettez à jour les informations de l'exploitation.</p>
        </div>
        <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
            Retour à la liste
        </april:button-link>
    </div>

    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
        <form method="POST" action="{{ route('back.farms.update', $farm) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="space-y-2">
                <label for="agricultural_region_id" class="text-sm font-medium">Région Agricole <span class="text-destructive">*</span></label>
                <select id="agricultural_region_id" name="agricultural_region_id" required
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" {{ old('agricultural_region_id', $farm->agricultural_region_id) == $region->id ? 'selected' : '' }}>
                            {{ $region->name }} ({{ $region->code }})
                        </option>
                    @endforeach
                </select>
                @error('agricultural_region_id') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="name" class="text-sm font-medium">Nom de la Ferme <span class="text-destructive">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $farm->name) }}" required
                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                @error('name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="producer_name" class="text-sm font-medium">Nom du Producteur / Exploitant</label>
                    <input type="text" id="producer_name" name="producer_name" value="{{ old('producer_name', $farm->producer_name) }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('producer_name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="soil_type" class="text-sm font-medium">Type de Sol</label>
                    <input type="text" id="soil_type" name="soil_type" value="{{ old('soil_type', $farm->soil_type) }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Sol argileux, Sol sableux">
                    @error('soil_type') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="address" class="text-sm font-medium">Adresse / Localisation <span class="text-destructive">*</span></label>
                    <input type="text" id="address" name="address" value="{{ old('address', $farm->address) }}" required
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('address') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="phone" class="text-sm font-medium">Numéro de Téléphone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $farm->phone) }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('phone') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="surface_hectares" class="text-sm font-medium">Surface (Hectares) <span class="text-destructive">*</span></label>
                    <input type="number" step="0.01" min="0" id="surface_hectares" name="surface_hectares" value="{{ old('surface_hectares', $farm->surface_hectares) }}" required
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @error('surface_hectares') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="farming_type" class="text-sm font-medium">Type d'Agriculture <span class="text-destructive">*</span></label>
                    <select id="farming_type" name="farming_type" required
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        @foreach(['Biologique', 'Raisonné', 'Traditionnel', 'Biodynamique'] as $type)
                            <option value="{{ $type }}" {{ old('farming_type', $farm->farming_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('farming_type') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description', $farm->description) }}</textarea>
                @error('description') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">Annuler</april:button-link>
                <april:button type="submit">Mettre à jour</april:button>
            </div>
        </form>
    </div>
</div>
@endsection

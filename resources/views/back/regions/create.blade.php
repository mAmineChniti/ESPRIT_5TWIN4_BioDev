@extends('layouts.back')

@section('title', 'Créer une Région Agricole')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Nouvelle Région Agricole</h1>
            <p class="text-sm text-muted-foreground">Ajoutez une zone géographique de production à la plateforme.</p>
        </div>
        <april:button-link href="{{ route('back.regions.index') }}" variant="outline">
            Retour à la liste
        </april:button-link>
    </div>

    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
        <form method="POST" action="{{ route('back.regions.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="name" class="text-sm font-medium">Nom de la Région <span class="text-destructive">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ex: Cap Bon - Nabeul">
                @error('name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="code" class="text-sm font-medium">Code Unique <span class="text-destructive">*</span></label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required
                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Ex: REG-CAPBON">
                @error('code') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="climate" class="text-sm font-medium">Climat Dominant</label>
                    <input type="text" id="climate" name="climate" value="{{ old('climate') }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Méditerranéen doux">
                    @error('climate') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="soil_type" class="text-sm font-medium">Type de Sol Majoritaire</label>
                    <input type="text" id="soil_type" name="soil_type" value="{{ old('soil_type') }}"
                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ex: Argilo-sableux">
                    @error('soil_type') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-medium">Description et Particularités</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Décrivez les cultures principales, spécificités géographiques, etc.">{{ old('description') }}</textarea>
                @error('description') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <april:button-link href="{{ route('back.regions.index') }}" variant="ghost">Annuler</april:button-link>
                <april:button type="submit">Enregistrer la Région</april:button>
            </div>
        </form>
    </div>
</div>
@endsection

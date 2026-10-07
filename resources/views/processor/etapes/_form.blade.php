<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label for="ordre" class="block text-sm font-medium text-foreground">Ordre</label>
        <input id="ordre" name="ordre" type="number" min="1" value="{{ old('ordre', $etape->ordre ?? '') }}"
            class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" required aria-invalid="{{ $errors->has('ordre') ? 'true' : 'false' }}" aria-describedby="ordre-error">
        @error('ordre')<p id="ordre-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-foreground">Type</label>
        <select id="type" name="type" class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" required aria-invalid="{{ $errors->has('type') ? 'true' : 'false' }}" aria-describedby="type-error">
            <option value="">Sélectionner un type</option>
            @foreach(['origine' => 'Origine', 'transport' => 'Transport', 'stockage' => 'Stockage', 'vente' => 'Vente'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $etape->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p id="type-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="lieu" class="block text-sm font-medium text-foreground">Lieu</label>
        <input id="lieu" name="lieu" type="text" maxlength="120" value="{{ old('lieu', $etape->lieu ?? '') }}"
            class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" required aria-invalid="{{ $errors->has('lieu') ? 'true' : 'false' }}" aria-describedby="lieu-error">
        @error('lieu')<p id="lieu-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="date_etape" class="block text-sm font-medium text-foreground">Date de l’étape</label>
        <input id="date_etape" name="date_etape" type="date"
            value="{{ old('date_etape', isset($etape) ? $etape->date_etape?->format('Y-m-d') : '') }}"
            class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" required aria-invalid="{{ $errors->has('date_etape') ? 'true' : 'false' }}" aria-describedby="date-etape-error">
        @error('date_etape')<p id="date-etape-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-foreground">Description</label>
        <textarea id="description" name="description" maxlength="255" rows="4"
            class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="description-error">{{ old('description', $etape->description ?? '') }}</textarea>
        @error('description')<p id="description-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">{{ $submitLabel }}</button>
    <a href="{{ route('processor.parcours.etapes.index', $parcours) }}" class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">Annuler</a>
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <april:label for="step_order">Step order</april:label>
        <april:input id="step_order" name="step_order" type="number" min="1" value="{{ old('step_order', $step->step_order ?? '') }}" required aria-invalid="{{ $errors->has('step_order') ? 'true' : 'false' }}" aria-describedby="step-order-error" />
        @error('step_order')<p id="step-order-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="type">Type</april:label>
        <april:native-select id="type" name="type" required aria-invalid="{{ $errors->has('type') ? 'true' : 'false' }}" aria-describedby="type-error">
            <option value="">Select a type</option>
            @foreach(['origin' => 'Origin', 'transport' => 'Transport', 'storage' => 'Storage', 'sale' => 'Sale'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $step->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </april:native-select>
        @error('type')<p id="type-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="location">Location</april:label>
        <april:input id="location" name="location" type="text" maxlength="120" value="{{ old('location', $step->location ?? '') }}" required aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="location-error" />
        @error('location')<p id="location-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="step_date">Step date</april:label>
        <april:input id="step_date" name="step_date" type="date" value="{{ old('step_date', isset($step) ? $step->step_date?->format('Y-m-d') : '') }}" required aria-invalid="{{ $errors->has('step_date') ? 'true' : 'false' }}" aria-describedby="step-date-error" />
        @error('step_date')<p id="step-date-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-foreground">Description</label>
        <textarea id="description" name="description" maxlength="255" rows="4"
            class="mt-1 block w-full rounded-md border-input bg-background shadow-sm" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="description-error">{{ old('description', $step->description ?? '') }}</textarea>
        @error('description')<p id="description-error" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">{{ $submitLabel }}</button>
    <a href="{{ route('processor.journeys.steps.index', $journey) }}" class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">Cancel</a>
</div>

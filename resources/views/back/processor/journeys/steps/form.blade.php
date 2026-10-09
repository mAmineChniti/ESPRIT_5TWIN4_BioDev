<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <april:label for="step_order">Step order</april:label>
        <april:input id="step_order" name="step_order" type="number" min="1" value="{{ old('step_order', $step->step_order ?? '') }}" required aria-invalid="{{ $errors->has('step_order') ? 'true' : 'false' }}" aria-describedby="step-order-error step-order-hint" />
        <p id="step-order-hint" class="mt-1 text-xs text-muted-foreground">Unique within this journey — the next free number is prefilled.</p>
        @error('step_order')<p id="step-order-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="type">Type</april:label>
        <april:native-select id="type" name="type" required aria-invalid="{{ $errors->has('type') ? 'true' : 'false' }}" aria-describedby="type-error">
            <option value="">Select a type</option>
            @foreach(\App\Models\JourneyStep::labels() as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $step->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </april:native-select>
        @error('type')<p id="type-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="location">Location</april:label>
        <april:input id="location" name="location" type="text" maxlength="120" value="{{ old('location', $step->location ?? '') }}" required aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="location-error" />
        @error('location')<p id="location-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div>
        <april:label for="step_date">Step date</april:label>
        <april:input id="step_date" name="step_date" type="date" value="{{ old('step_date', isset($step) ? $step->step_date?->format('Y-m-d') : '') }}" required aria-invalid="{{ $errors->has('step_date') ? 'true' : 'false' }}" aria-describedby="step-date-error" />
        @error('step_date')<p id="step-date-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <april:label for="description">Description</april:label>
        <x-textarea-field id="description" name="description" rows="4" maxlength="255"
            class="mt-1"
            :value="old('description', $step->description ?? '')"
            describedby="description-error"
            :aria-invalid="$errors->has('description') ? 'true' : 'false'" />
        @error('description')<p id="description-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex gap-3">
    <april:button type="submit">{{ $submitLabel }}</april:button>
    <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="outline">Cancel</april:button-link>
</div>

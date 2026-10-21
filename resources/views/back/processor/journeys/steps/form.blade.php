<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <april:label for="step_order">{{ __('Step order') }}</april:label>
        <april:input id="step_order" name="step_order" type="number" min="1" value="{{ old('step_order', $step->step_order ?? '') }}" required aria-invalid="{{ $errors->has('step_order') ? 'true' : 'false' }}" aria-describedby="step-order-error step-order-hint" />
        <p id="step-order-hint" class="mt-1 text-xs text-muted-foreground">{{ __('Unique within this journey — the next free number is prefilled.') }}</p>
        <p id="step-order-error" role="alert" class="mt-1 text-sm text-destructive">@error('step_order'){{ $message }}@enderror</p>
    </div>

    <div>
        <april:label for="type">{{ __('Type') }}</april:label>
        <april:native-select id="type" name="type" required aria-invalid="{{ $errors->has('type') ? 'true' : 'false' }}" aria-describedby="type-error">
            <option value="">{{ __('Select a type') }}</option>
            @foreach(\App\Models\JourneyStep::labels() as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $step->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </april:native-select>
        <p id="type-error" role="alert" class="mt-1 text-sm text-destructive">@error('type'){{ $message }}@enderror</p>
    </div>

    <div>
        <april:label for="location">{{ __('City') }}</april:label>
        <april:input id="location" name="location" type="text" list="city-options" maxlength="120" value="{{ old('location', $step->location ?? '') }}" required aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="location-error" />
        <p id="location-error" role="alert" class="mt-1 text-sm text-destructive">@error('location'){{ $message }}@enderror</p>
        <datalist id="city-options">
            @foreach ($cities as $city)
                <option value="{{ $city->name }}" label="{{ $city->country_code }}"></option>
            @endforeach
        </datalist>
    </div>

    <div>
        <april:label for="country_code">{{ __('Country') }}</april:label>
        <april:native-select id="country_code" name="country_code" aria-invalid="{{ $errors->has('country_code') ? 'true' : 'false' }}" aria-describedby="country-code-error">
            <option value="">{{ __('Select a country (optional)') }}</option>
            @foreach ($countryCodes as $countryCode)
                <option value="{{ $countryCode }}" @selected(old('country_code', $step->country_code ?? '') === $countryCode)>
                    {{ $countryCode }}
                </option>
            @endforeach
        </april:native-select>
        <p id="country-code-error" role="alert" class="mt-1 text-sm text-destructive">@error('country_code'){{ $message }}@enderror</p>
    </div>

    <div>
        <april:label for="farm_id">{{ __('Farm') }}</april:label>
        <april:native-select id="farm_id" name="farm_id" aria-invalid="{{ $errors->has('farm_id') ? 'true' : 'false' }}" aria-describedby="farm-error">
            <option value="">{{ __('No farm selected') }}</option>
            @foreach ($farms as $farm)
                <option
                    value="{{ $farm->id }}"
                    @selected((string) old('farm_id', $step->farm_id ?? '') === (string) $farm->id)
                >
                    {{ $farm->name }} — {{ $farm->address }}
                </option>
            @endforeach
        </april:native-select>
        <p id="farm-error" role="alert" class="mt-1 text-sm text-destructive">@error('farm_id'){{ $message }}@enderror</p>
    </div>

    <div>
        <april:label for="step_date">{{ __('Step date') }}</april:label>
        <april:input id="step_date" name="step_date" type="date" value="{{ old('step_date', isset($step) ? $step->step_date?->format('Y-m-d') : '') }}" required aria-invalid="{{ $errors->has('step_date') ? 'true' : 'false' }}" aria-describedby="step-date-error" />
        <p id="step-date-error" role="alert" class="mt-1 text-sm text-destructive">@error('step_date'){{ $message }}@enderror</p>
    </div>

    <div class="sm:col-span-2">
        <april:label for="description">{{ __('Description') }}</april:label>
        <x-textarea-field id="description" name="description" rows="4" maxlength="255"
            class="mt-1"
            :value="old('description', $step->description ?? '')"
            describedby="description-error"
            :aria-invalid="$errors->has('description') ? 'true' : 'false'" />
        <p id="description-error" role="alert" class="mt-1 text-sm text-destructive">@error('description'){{ $message }}@enderror</p>
    </div>
</div>

<div class="mt-6 flex gap-3">
    <april:button type="submit">{{ $submitLabel }}</april:button>
    <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="outline">{{ __('Cancel') }}</april:button-link>
</div>

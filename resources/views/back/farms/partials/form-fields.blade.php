{{--
    The farm fields, shared by the create and edit forms so the two cannot
    drift apart.

    Expects:
      $farm      ?Farm    the farm being edited, or null when creating
      $regions   Collection<int, AgriculturalRegion>
--}}
@php
    $regions ??= \App\Models\AgriculturalRegion::orderBy('name')->get();
    $value = fn (string $field, mixed $fallback = null) => old($field, $farm?->{$field} ?? $fallback);
    $errorFor = fn (string $field) => $errors->get($field);
@endphp

<div class="space-y-2">
    <april:label for="agricultural_region_id">
        {{ __('Agricultural Region') }} <span class="text-destructive">*</span>
    </april:label>
    <april:native-select
        id="agricultural_region_id"
        name="agricultural_region_id"
        required
        @class(['border-destructive' => $errorFor('agricultural_region_id')])
        @if($errorFor('agricultural_region_id')) aria-invalid="true" aria-describedby="agricultural_region_id-error" @endif
    >
        <option value="">{{ __('-- Select a region --') }}</option>
        @foreach($regions as $region)
            <option value="{{ $region->id }}" @selected((int) $value('agricultural_region_id', request('region_id')) === $region->id)>
                {{ $region->name }} ({{ $region->code }})
            </option>
        @endforeach
    </april:native-select>
    <p class="text-xs text-muted-foreground">{{ __('Choose a region created by an administrator.') }}</p>
    @error('agricultural_region_id')
        <p id="agricultural_region_id-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>

<div class="space-y-2">
    <april:label for="name">
        {{ __('Farm Name') }} <span class="text-destructive">*</span>
    </april:label>
    <april:input
        id="name"
        name="name"
        value="{{ $value('name') }}"
        required
            :placeholder="__('Example: Ahmed Farm')"
        @class(['border-destructive' => $errorFor('name')])
        @if($errorFor('name')) aria-invalid="true" aria-describedby="name-error" @endif
    />
    @error('name')
        <p id="name-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="producer_name">{{ __('Producer / Operator Name') }}</april:label>
        <april:input
            id="producer_name"
            name="producer_name"
            value="{{ $value('producer_name', auth()->user()->name) }}"
            :placeholder="__('Example: Ahmed')"
            @class(['border-destructive' => $errorFor('producer_name')])
            @if($errorFor('producer_name')) aria-invalid="true" aria-describedby="producer_name-error" @endif
        />
        @error('producer_name')
            <p id="producer_name-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <april:label for="soil_type">{{ __('Soil Type') }}</april:label>
        <april:input
            id="soil_type"
            name="soil_type"
            value="{{ $value('soil_type') }}"
            :placeholder="__('Example: Clay soil, sandy soil, etc.')"
            @class(['border-destructive' => $errorFor('soil_type')])
            @if($errorFor('soil_type')) aria-invalid="true" aria-describedby="soil_type-error" @endif
        />
        @error('soil_type')
            <p id="soil_type-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="address">
            {{ __('Address / Location') }} <span class="text-destructive">*</span>
        </april:label>
        <april:input
            id="address"
            name="address"
            value="{{ $value('address') }}"
            required
            :placeholder="__('Example: Bizerte, Mateur Road km 3')"
            @class(['border-destructive' => $errorFor('address')])
            @if($errorFor('address')) aria-invalid="true" aria-describedby="address-error" @endif
        />
        @error('address')
            <p id="address-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <april:label for="phone">{{ __('Phone Number') }}</april:label>
        <april:input
            id="phone"
            name="phone"
            value="{{ $value('phone') }}"
            :placeholder="__('Example: +216 72 100 200')"
            @class(['border-destructive' => $errorFor('phone')])
            @if($errorFor('phone')) aria-invalid="true" aria-describedby="phone-error" @endif
        />
        @error('phone')
            <p id="phone-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="surface_hectares">
            {{ __('Area (Hectares)') }} <span class="text-destructive">*</span>
        </april:label>
        <april:input
            id="surface_hectares"
            name="surface_hectares"
            type="number"
            step="0.01"
            min="0"
            value="{{ $value('surface_hectares', '0.00') }}"
            required
            @class(['border-destructive' => $errorFor('surface_hectares')])
            @if($errorFor('surface_hectares')) aria-invalid="true" aria-describedby="surface_hectares-error" @endif
        />
        @error('surface_hectares')
            <p id="surface_hectares-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        @php
            $farmingType = $value('farming_type', 'Organic');
        @endphp
        <april:label for="farming_type">
            {{ __('Farming Type') }} <span class="text-destructive">*</span>
        </april:label>
        <april:native-select
            id="farming_type"
            name="farming_type"
            required
            @class(['border-destructive' => $errorFor('farming_type')])
            @if($errorFor('farming_type')) aria-invalid="true" aria-describedby="farming_type-error" @endif
        >
            @foreach(\App\Models\Farm::FARMING_TYPES as $type)
                <option value="{{ $type }}" @selected($farmingType === $type)>{{ \App\Models\Farm::farmingTypeLabel($type) }}</option>
            @endforeach
        </april:native-select>
        @error('farming_type')
            <p id="farming_type-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="space-y-2">
    <april:label for="description">{{ __('Description') }}</april:label>
        <x-textarea-field
            id="description"
            name="description"
            rows="3"
            :value="$value('description')"
            :placeholder="__('Example: Fertile soil suitable for crops')"
            describedby="description-error"
            :aria-invalid="$errorFor('description') ? 'true' : 'false'"
        />
        @error('description')
            <p id="description-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
</div>
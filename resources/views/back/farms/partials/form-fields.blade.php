{{--
    The farm fields, shared by the create and edit forms so the two cannot
    drift apart.

    Expects:
      $farm      ?Farm    the farm being edited, or null when creating
      $regions   Collection<int, AgriculturalRegion>
      $isAdmin   bool
--}}
@php
    $value = fn (string $field, mixed $fallback = null) => old($field, $farm?->{$field} ?? $fallback);
    $errorFor = fn (string $field) => $errors->get($field);
@endphp

<div class="space-y-2">
    <april:label for="agricultural_region_id">
        Agricultural Region <span class="text-destructive">*</span>
    </april:label>
    <april:native-select
        id="agricultural_region_id"
        name="agricultural_region_id"
        required
        @class(['border-destructive' => $errorFor('agricultural_region_id')])
        @if($errorFor('agricultural_region_id')) aria-invalid="true" aria-describedby="agricultural_region_id-error" @endif
    >
        <option value="">-- Select a region --</option>
        @foreach($regions as $region)
            <option value="{{ $region->id }}" @selected((int) $value('agricultural_region_id', request('region_id')) === $region->id)>
                {{ $region->name }} ({{ $region->code }})
            </option>
        @endforeach
    </april:native-select>
    <p class="text-xs text-muted-foreground">Choose a region created by an administrator.</p>
    @error('agricultural_region_id')
        <p id="agricultural_region_id-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>

<div class="space-y-2">
    <april:label for="name">
        Farm Name <span class="text-destructive">*</span>
    </april:label>
    <april:input
        id="name"
        name="name"
        value="{{ $value('name') }}"
        required
            placeholder="Example: Ahmed Farm"
        @class(['border-destructive' => $errorFor('name')])
        @if($errorFor('name')) aria-invalid="true" aria-describedby="name-error" @endif
    />
    @error('name')
        <p id="name-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="producer_name">Producer / Operator Name</april:label>
        <april:input
            id="producer_name"
            name="producer_name"
            value="{{ $value('producer_name', auth()->user()->name) }}"
            placeholder="Example: Ahmed"
        />
        @error('producer_name')
            <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <april:label for="soil_type">Soil Type</april:label>
        <april:input
            id="soil_type"
            name="soil_type"
            value="{{ $value('soil_type') }}"
            placeholder="Example: Clay soil, sandy soil, etc."
        />
        @error('soil_type')
            <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="address">
            Address / Location <span class="text-destructive">*</span>
        </april:label>
        <april:input
            id="address"
            name="address"
            value="{{ $value('address') }}"
            required
            placeholder="Example: Bizerte, Mateur Road km 3"
            @class(['border-destructive' => $errorFor('address')])
            @if($errorFor('address')) aria-invalid="true" aria-describedby="address-error" @endif
        />
        @error('address')
            <p id="address-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <april:label for="phone">Phone Number</april:label>
        <april:input id="phone" name="phone" value="{{ $value('phone') }}" placeholder="Example: +216 72 100 200" />
        @error('phone')
            <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <april:label for="surface_hectares">
            Area (Hectares) <span class="text-destructive">*</span>
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
            $farmingType = match ($value('farming_type', 'Organic')) {
                'Biologique' => 'Organic',
                'Raisonné' => 'Sustainable',
                'Traditionnel' => 'Traditional',
                'Biodynamique' => 'Biodynamic',
                default => $value('farming_type', 'Organic'),
            };
        @endphp
        <april:label for="farming_type">
            Farming Type <span class="text-destructive">*</span>
        </april:label>
        <april:native-select id="farming_type" name="farming_type" required>
            @foreach(['Organic', 'Sustainable', 'Traditional', 'Biodynamic'] as $type)
                <option value="{{ $type }}" @selected($farmingType === $type)>{{ $type }}</option>
            @endforeach
        </april:native-select>
        @error('farming_type')
            <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="space-y-2">
    <april:label for="description">Description</april:label>
    <april:textarea
        id="description"
        name="description"
        rows="3"
        placeholder="Example: Fertile soil suitable for crops"
    >{{ $value('description') }}</april:textarea>
    @error('description')
        <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>
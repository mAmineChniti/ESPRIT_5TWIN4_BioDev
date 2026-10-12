<div class="space-y-2">
    <april:label for="name">{{ __('Name *') }}</april:label>
    <april:input id="name" type="text" name="name" value="{{ old('name', $warehouse->name) }}" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />
    <span id="name-error" role="alert" class="text-destructive text-sm">@error('name'){{ $message }}@enderror</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="city">{{ __('City *') }}</april:label>
        <april:input id="city" type="text" name="city" value="{{ old('city', $warehouse->city) }}" required aria-invalid="{{ $errors->has('city') ? 'true' : 'false' }}" aria-describedby="city-error" />
        <span id="city-error" role="alert" class="text-destructive text-sm">@error('city'){{ $message }}@enderror</span>
    </div>
    <div class="space-y-2">
        <april:label for="country">{{ __('Country *') }}</april:label>
        <april:input id="country" type="text" name="country" value="{{ old('country', $warehouse->country ?? 'Tunisia') }}" required aria-invalid="{{ $errors->has('country') ? 'true' : 'false' }}" aria-describedby="country-error" />
        <span id="country-error" role="alert" class="text-destructive text-sm">@error('country'){{ $message }}@enderror</span>
    </div>
</div>

<div class="space-y-2">
    <april:label for="address">{{ __('Address') }}</april:label>
    <april:input id="address" type="text" name="address" value="{{ old('address', $warehouse->address) }}" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="address-error" />
    <span id="address-error" role="alert" class="text-destructive text-sm">@error('address'){{ $message }}@enderror</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="capacity_m2">{{ __('Capacity (m²) *') }}</april:label>
        <april:input id="capacity_m2" type="number" min="1" name="capacity_m2" value="{{ old('capacity_m2', $warehouse->capacity_m2) }}" required aria-invalid="{{ $errors->has('capacity_m2') ? 'true' : 'false' }}" aria-describedby="capacity-error" />
        <span id="capacity-error" role="alert" class="text-destructive text-sm">@error('capacity_m2'){{ $message }}@enderror</span>
    </div>
    <div class="flex items-end pb-2">
        <april:label for="is_refrigerated" class="flex items-center gap-2">
            <april:input id="is_refrigerated" type="checkbox" name="is_refrigerated" value="1" @checked(old('is_refrigerated', $warehouse->is_refrigerated)) />
            {{ __('Refrigerated') }}
        </april:label>
    </div>
</div>
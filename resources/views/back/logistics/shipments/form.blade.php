<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="reference">{{ __('Reference *') }}</april:label>
        <april:input id="reference" name="reference" value="{{ old('reference', $shipment->reference) }}" placeholder="SHP-000123" required aria-invalid="{{ $errors->has('reference') ? 'true' : 'false' }}" aria-describedby="reference-error" />
        <span id="reference-error" role="alert" class="text-destructive text-sm">@error('reference'){{ $message }}@enderror</span>
    </div>
    <div class="space-y-2">
        <april:label for="shipped_on">{{ __('Shipping date *') }}</april:label>
        <april:input id="shipped_on" type="date" name="shipped_on" value="{{ old('shipped_on', $shipment->shipped_on?->format('Y-m-d')) }}" required aria-invalid="{{ $errors->has('shipped_on') ? 'true' : 'false' }}" aria-describedby="shipped-on-error" />
        <span id="shipped-on-error" role="alert" class="text-destructive text-sm">@error('shipped_on'){{ $message }}@enderror</span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="warehouse_id">{{ __('Departure warehouse *') }}</april:label>
        <april:native-select id="warehouse_id" name="warehouse_id" required aria-invalid="{{ $errors->has('warehouse_id') ? 'true' : 'false' }}" aria-describedby="warehouse-error">
            <option value="">{{ __('Select a warehouse...') }}</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $shipment->warehouse_id) == $warehouse->id)>
                    {{ $warehouse->name }} ({{ $warehouse->city }})
                </option>
            @endforeach
        </april:native-select>
        <span id="warehouse-error" role="alert" class="text-destructive text-sm">@error('warehouse_id'){{ $message }}@enderror</span>
    </div>
    <div class="space-y-2">
        <april:label for="food_id">{{ __('Product carried') }}</april:label>
        <april:native-select id="food_id" name="food_id" aria-invalid="{{ $errors->has('food_id') ? 'true' : 'false' }}" aria-describedby="food-error">
            <option value="">{{ __('None') }}</option>
            @foreach($foods as $food)
                <option value="{{ $food->id }}" @selected(old('food_id', $shipment->food_id) == $food->id)>{{ $food->name }}</option>
            @endforeach
        </april:native-select>
        <span id="food-error" role="alert" class="text-destructive text-sm">@error('food_id'){{ $message }}@enderror</span>
    </div>
</div>

<div class="space-y-2">
    <april:label for="destination">{{ __('Destination *') }}</april:label>
    <april:input id="destination" type="text" name="destination" value="{{ old('destination', $shipment->destination) }}" required aria-invalid="{{ $errors->has('destination') ? 'true' : 'false' }}" aria-describedby="destination-error" />
    <span id="destination-error" role="alert" class="text-destructive text-sm">@error('destination'){{ $message }}@enderror</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="distance_km">{{ __('Distance (km) *') }}</april:label>
        <april:input id="distance_km" type="number" step="0.1" min="0.1" name="distance_km" value="{{ old('distance_km', $shipment->distance_km) }}" required aria-invalid="{{ $errors->has('distance_km') ? 'true' : 'false' }}" aria-describedby="distance-error" />
        <span id="distance-error" role="alert" class="text-destructive text-sm">@error('distance_km'){{ $message }}@enderror</span>
    </div>
    <div class="space-y-2">
        <april:label for="weight_kg">{{ __('Weight (kg) *') }}</april:label>
        <april:input id="weight_kg" type="number" step="0.01" min="0.01" name="weight_kg" value="{{ old('weight_kg', $shipment->weight_kg) }}" required aria-invalid="{{ $errors->has('weight_kg') ? 'true' : 'false' }}" aria-describedby="weight-error" />
        <span id="weight-error" role="alert" class="text-destructive text-sm">@error('weight_kg'){{ $message }}@enderror</span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <april:label for="transport_mode">{{ __('Transport mode *') }}</april:label>
        <april:native-select id="transport_mode" name="transport_mode" required aria-invalid="{{ $errors->has('transport_mode') ? 'true' : 'false' }}" aria-describedby="transport-error">
            <option value="">{{ __('Select a mode...') }}</option>
            @foreach($modes as $mode)
                <option value="{{ $mode->value }}" @selected(old('transport_mode', $shipment->transport_mode?->value) === $mode->value)>{{ $mode->label() }}</option>
            @endforeach
        </april:native-select>
        <span id="transport-error" role="alert" class="text-destructive text-sm">@error('transport_mode'){{ $message }}@enderror</span>
    </div>
    <div class="space-y-2">
        <april:label for="status">{{ __('Status *') }}</april:label>
        <april:native-select id="status" name="status" required aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}" aria-describedby="status-error">
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $shipment->status?->value ?? 'preparing') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </april:native-select>
        <span id="status-error" role="alert" class="text-destructive text-sm">@error('status'){{ $message }}@enderror</span>
    </div>
</div>

<p class="text-xs text-muted-foreground">{{ __('The carbon footprint is calculated automatically when you save.') }}</p>

<x-transport-advisor />
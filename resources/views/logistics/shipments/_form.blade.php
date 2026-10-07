@php
    $input = 'w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Reference *</label>
        <input type="text" name="reference" value="{{ old('reference', $shipment->reference) }}" placeholder="SHP-000123" required class="{{ $input }}">
        @error('reference') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Shipping date *</label>
        <input type="date" name="shipped_on" value="{{ old('shipped_on', $shipment->shipped_on?->format('Y-m-d')) }}" required class="{{ $input }}">
        @error('shipped_on') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Departure warehouse *</label>
        <select name="warehouse_id" required class="{{ $input }}">
            <option value="">Select a warehouse...</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $shipment->warehouse_id) == $warehouse->id)>
                    {{ $warehouse->name }} ({{ $warehouse->city }})
                </option>
            @endforeach
        </select>
        @error('warehouse_id') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Product carried</label>
        <select name="food_id" class="{{ $input }}">
            <option value="">None</option>
            @foreach($foods as $food)
                <option value="{{ $food->id }}" @selected(old('food_id', $shipment->food_id) == $food->id)>{{ $food->name }}</option>
            @endforeach
        </select>
        @error('food_id') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<div class="space-y-2">
    <label class="block text-sm font-medium text-foreground">Destination *</label>
    <input type="text" name="destination" value="{{ old('destination', $shipment->destination) }}" required class="{{ $input }}">
    @error('destination') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Distance (km) *</label>
        <input type="number" step="0.1" min="0.1" name="distance_km" value="{{ old('distance_km', $shipment->distance_km) }}" required class="{{ $input }}">
        @error('distance_km') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Weight (kg) *</label>
        <input type="number" step="0.01" min="0.01" name="weight_kg" value="{{ old('weight_kg', $shipment->weight_kg) }}" required class="{{ $input }}">
        @error('weight_kg') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Transport mode *</label>
        <select name="transport_mode" required class="{{ $input }}">
            <option value="">Select a mode...</option>
            @foreach($modes as $mode)
                <option value="{{ $mode->value }}" @selected(old('transport_mode', $shipment->transport_mode?->value) === $mode->value)>{{ $mode->label() }}</option>
            @endforeach
        </select>
        @error('transport_mode') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Status *</label>
        <select name="status" required class="{{ $input }}">
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $shipment->status?->value ?? 'preparing') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<p class="text-xs text-muted-foreground">The carbon footprint is calculated automatically when you save.</p>
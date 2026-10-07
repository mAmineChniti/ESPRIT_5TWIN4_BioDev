@php
    $input = 'w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-2">
    <label class="block text-sm font-medium text-foreground">Name *</label>
    <input type="text" name="name" value="{{ old('name', $warehouse->name) }}" required class="{{ $input }}">
    @error('name') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">City *</label>
        <input type="text" name="city" value="{{ old('city', $warehouse->city) }}" required class="{{ $input }}">
        @error('city') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Country *</label>
        <input type="text" name="country" value="{{ old('country', $warehouse->country ?? 'Tunisia') }}" required class="{{ $input }}">
        @error('country') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<div class="space-y-2">
    <label class="block text-sm font-medium text-foreground">Address</label>
    <input type="text" name="address" value="{{ old('address', $warehouse->address) }}" class="{{ $input }}">
    @error('address') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-foreground">Capacity (m²) *</label>
        <input type="number" min="1" name="capacity_m2" value="{{ old('capacity_m2', $warehouse->capacity_m2) }}" required class="{{ $input }}">
        @error('capacity_m2') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="flex items-end pb-2">
        <label class="flex items-center gap-2 text-sm font-medium text-foreground">
            <input type="checkbox" name="is_refrigerated" value="1" @checked(old('is_refrigerated', $warehouse->is_refrigerated))>
            Refrigerated
        </label>
    </div>
</div>
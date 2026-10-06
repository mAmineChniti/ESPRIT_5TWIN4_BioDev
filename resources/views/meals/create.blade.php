@extends('layouts.back')

@section('title', 'Log a Meal')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('meals.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
        <h1 class="text-2xl font-bold">Log a Meal</h1>
    </div>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl">
    <form action="{{ route('meals.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="space-y-2">
            <label class="block text-sm font-medium text-foreground">Meal name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
            @error('name') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Type *</label>
                <select name="type" required class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                @error('type') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Date</label>
                <input type="date" name="consumed_on" value="{{ old('consumed_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
                @error('consumed_on') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-foreground">Products *</label>
            <p class="text-xs text-muted-foreground">Select each product you ate and enter the quantity in grams.</p>
            <div class="max-h-64 overflow-y-auto border border-border rounded-md divide-y divide-border">
                @foreach($foods as $food)
                    <label class="flex items-center justify-between gap-3 p-3">
                        <span class="flex items-center gap-2">
                            <input type="checkbox" name="foods[]" value="{{ $food->id }}"
                                   @checked(in_array($food->id, old('foods', [])))>
                            <span class="text-sm text-foreground">{{ $food->name }}</span>
                            <span class="text-xs text-muted-foreground">{{ $food->category->name ?? '' }}</span>
                        </span>
                        <input type="number" step="1" min="0" name="quantities[{{ $food->id }}]"
                               value="{{ old('quantities')[$food->id] ?? 100 }}"
                               class="w-24 rounded-md border-input shadow-sm text-sm">g
                    </label>
                @endforeach
            </div>
            @error('foods') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-foreground">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-md border-input shadow-sm">{{ old('notes') }}</textarea>
            @error('notes') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-6 rounded-md">
                Save meal
            </button>
        </div>
    </form>
</div>
@endsection
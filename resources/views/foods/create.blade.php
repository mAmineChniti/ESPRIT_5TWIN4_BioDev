@extends('layouts.back')

@section('title', 'Add Food')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('foods.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
        <h1 class="text-2xl font-bold">Add a product</h1>
    </div>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl">
    <form action="{{ route('foods.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="space-y-2">
            <label class="block text-sm font-medium text-foreground">Nom du produit *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
            @error('name') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-foreground">Category *</label>
            <select name="category_id" required class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
                <option value="">Select a category...</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Origin</label>
                <input type="text" name="origin" value="{{ old('origin') }}" placeholder="ex: France" class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
                @error('origin') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
            </div>
            
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Score Environnemental</label>
                <select name="environmental_score" class="w-full rounded-md border-input shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">Not set</option>
                    @foreach(\App\Enums\EnvironmentalScore::cases() as $score)
                        <option value="{{ $score->value }}" {{ old('environmental_score') == $score->value ? 'selected' : '' }}>
                            {{ $score->value }} — {{ $score->label() }}
                        </option>
                    @endforeach
                </select>
                @error('environmental_score') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <fieldset class="space-y-2">
            <legend class="block text-sm font-medium text-foreground">Certifications (verified)</legend>
            <p class="text-xs text-muted-foreground">Only certifications recorded with an issuing body and a validity date can be selected.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                @forelse($certifications as $certification)
                    <label class="flex items-start gap-2 rounded-md border border-border p-3">
                        <input type="checkbox" name="certifications[]" value="{{ $certification->id }}"
                               @checked(in_array($certification->id, old('certifications', []))) class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ $certification->name }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $certification->issuer }}</span>
                            @if($certification->isExpired())
                                <span class="block text-xs text-destructive">Expired</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-muted-foreground">No certifications recorded yet.</p>
                @endforelse
            </div>
            @error('certifications') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
        </fieldset>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Calories</label>
                <input type="number" name="calories" value="{{ old('calories', 0) }}" min="0" required class="w-full rounded-md border-input shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Protein (g)</label>
                <input type="number" step="0.01" name="protein" value="{{ old('protein', 0) }}" min="0" required class="w-full rounded-md border-input shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Carbs (g)</label>
                <input type="number" step="0.01" name="carbs" value="{{ old('carbs', 0) }}" min="0" required class="w-full rounded-md border-input shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-foreground">Fat (g)</label>
                <input type="number" step="0.01" name="fat" value="{{ old('fat', 0) }}" min="0" required class="w-full rounded-md border-input shadow-sm">
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-6 rounded-md">
                Save
            </button>
        </div>
    </form>
</div>
@endsection

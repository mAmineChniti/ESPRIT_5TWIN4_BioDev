@extends('layouts.back')

@section('title', 'Edit Food')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('foods.index') }}" class="text-gray-500 hover:text-gray-700">← Retour</a>
        <h1 class="text-2xl font-bold">Modifier le Produit</h1>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form action="{{ route('foods.update', $food) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Nom du produit *</label>
            <input type="text" name="name" value="{{ old('name', $food->name) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Catégorie * (Jointure)</label>
            <select name="category_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Sélectionnez une catégorie...</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $food->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Origine</label>
                <input type="text" name="origin" value="{{ old('origin', $food->origin) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Score Environnemental</label>
                <select name="environmental_score" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Non défini</option>
                    @foreach(['A', 'B', 'C', 'D', 'E'] as $score)
                        <option value="{{ $score }}" {{ old('environmental_score', $food->environmental_score) == $score ? 'selected' : '' }}>{{ $score }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Certifications</label>
            <input type="text" name="certifications" value="{{ old('certifications', $food->certifications) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Calories</label>
                <input type="number" name="calories" value="{{ old('calories', $food->calories) }}" min="0" required class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Protéines (g)</label>
                <input type="number" step="0.01" name="protein" value="{{ old('protein', $food->protein) }}" min="0" required class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Glucides (g)</label>
                <input type="number" step="0.01" name="carbs" value="{{ old('carbs', $food->carbs) }}" min="0" required class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Lipides (g)</label>
                <input type="number" step="0.01" name="fat" value="{{ old('fat', $food->fat) }}" min="0" required class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-md">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection

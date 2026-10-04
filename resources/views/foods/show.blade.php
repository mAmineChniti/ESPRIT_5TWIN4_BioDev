@extends('layouts.back')

@section('title', 'Food Details')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('foods.index') }}" class="text-gray-500 hover:text-gray-700">← Retour</a>
            <h1 class="text-2xl font-bold">Détails du Produit</h1>
        </div>
        <div class="space-x-2">
            <a href="{{ route('foods.edit', $food) }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md">
                Modifier
            </a>
            <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                    Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden max-w-3xl">
    <div class="px-6 py-5 border-b border-gray-200">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Informations de Traçabilité</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">De la ferme à l'assiette.</p>
    </div>
    <div class="px-6 py-5">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Nom du produit</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $food->name }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $food->category->name ?? 'Aucune' }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Origine</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $food->origin ?? 'Inconnue' }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Score Environnemental</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <span class="px-2 py-1 text-xs font-bold rounded bg-green-100 text-green-800">{{ $food->environmental_score ?? 'N/A' }}</span>
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Certifications</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $food->certifications ?? 'Aucune certification' }}</dd>
            </div>
        </dl>
    </div>

    <div class="px-6 py-5 border-t border-gray-200">
        <h3 class="text-md font-medium leading-6 text-gray-900 mb-4">Valeurs Nutritionnelles (pour 100g)</h3>
        <div class="grid grid-cols-4 text-center gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="block text-2xl font-bold text-indigo-600">{{ $food->calories }}</span>
                <span class="text-xs text-gray-500 uppercase font-semibold">Calories</span>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="block text-2xl font-bold text-blue-600">{{ $food->protein }}g</span>
                <span class="text-xs text-gray-500 uppercase font-semibold">Protéines</span>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="block text-2xl font-bold text-yellow-600">{{ $food->carbs }}g</span>
                <span class="text-xs text-gray-500 uppercase font-semibold">Glucides</span>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="block text-2xl font-bold text-red-600">{{ $food->fat }}g</span>
                <span class="text-xs text-gray-500 uppercase font-semibold">Lipides</span>
            </div>
        </div>
    </div>
</div>
@endsection

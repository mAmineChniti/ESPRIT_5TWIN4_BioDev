@extends('layouts.back')

@section('title', 'Food List')

@section('content')
@php $canManage = in_array(auth()->user()?->role, ['producer', 'processor', 'distributor']); @endphp

@if(session('success'))
    <div class="mb-4 p-3 rounded-md bg-green-900/30 border border-green-700 text-green-300 text-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-3 rounded-md bg-red-900/30 border border-red-700 text-red-300 text-sm">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-3 rounded-md bg-red-900/30 border border-red-700 text-red-300 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Products</h1>
    @if($canManage)
        <div class="flex items-center gap-3">
            <form action="{{ route('foods.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <label for="csv_file" class="cursor-pointer bg-muted hover:bg-muted/80 text-foreground font-medium py-2 px-4 rounded-md border border-border text-sm transition">
                    📥 Import CSV
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" class="hidden"
                       onchange="this.form.submit()">
            </form>
            <a href="{{ route('foods.create') }}" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
                + Add a product
            </a>
        </div>
    @endif
</div>

<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Origin</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Eco score</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-card divide-y divide-border">
            @forelse($foods as $food)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $food->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $food->category->name ?? 'Not set' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $food->origin ?? '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('foods.show', $food) }}" class="text-primary hover:underline">View</a>
                    @if($canManage)
                        <a href="{{ route('foods.edit', $food) }}" class="text-primary hover:underline">Edit</a>
                        <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-destructive hover:underline">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-center">No products found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($foods->hasPages())
        <div class="px-6 py-4 border-t border-border bg-card">
            {{ $foods->links() }}
        </div>
    @endif
</div>
@endsection

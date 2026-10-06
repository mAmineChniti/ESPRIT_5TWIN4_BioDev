@extends('layouts.back')

@section('title', 'Food List')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Products</h1>
    <a href="{{ route('foods.create') }}" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
        + Add a product
    </a>
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
                    <a href="{{ route('foods.edit', $food) }}" class="text-primary hover:underline">Edit</a>
                    <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Delete this product?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-center">No products found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

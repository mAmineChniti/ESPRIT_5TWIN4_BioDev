@extends('layouts.back')

@section('title', 'Warehouses')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Warehouses</h1>
    <a href="{{ route('logistics.warehouses.create') }}" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
        + Add a warehouse
    </a>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">City</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Capacity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Refrigerated</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Shipments</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-card divide-y divide-border">
            @forelse($warehouses as $warehouse)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $warehouse->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $warehouse->city }}, {{ $warehouse->country }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ number_format($warehouse->capacity_m2) }} m²</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $warehouse->is_refrigerated ? 'Yes' : 'No' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $warehouse->shipments_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('logistics.warehouses.show', $warehouse) }}" class="text-primary hover:underline">View</a>
                    <a href="{{ route('logistics.warehouses.edit', $warehouse) }}" class="text-primary hover:underline">Edit</a>
                    <form action="{{ route('logistics.warehouses.destroy', $warehouse) }}" method="POST" class="inline"
                          onsubmit="return confirm('Delete this warehouse and its {{ $warehouse->shipments_count }} shipment(s)?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-sm text-muted-foreground text-center">No warehouses found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $warehouses->links() }}</div>
@endsection
@extends('layouts.back')

@section('title', 'Warehouses')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Warehouses</h1>
    <april:button-link href="{{ route('logistics.warehouses.create') }}">
        <x-lucide-plus class="size-4" />
        Add a warehouse
    </april:button-link>
</div>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Warehouses</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">Name</th>
                        <th scope="col" class="px-6 py-3">City</th>
                        <th scope="col" class="px-6 py-3">Capacity</th>
                        <th scope="col" class="px-6 py-3">Refrigerated</th>
                        <th scope="col" class="px-6 py-3">Shipments</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
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
                            <x-confirm-action
                                :action="route('logistics.warehouses.destroy', $warehouse)"
                                title="Delete this warehouse?"
                                description="This warehouse and its {{ $warehouse->shipments_count }} shipment(s) will be permanently deleted."
                                triggerVariant="ghost"
                                triggerSize="sm"
                            >Delete</x-confirm-action>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-sm text-muted-foreground">No warehouses found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-4">{{ $warehouses->links() }}</div>
@endsection
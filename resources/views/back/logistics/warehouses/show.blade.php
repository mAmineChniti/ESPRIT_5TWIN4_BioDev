@extends('layouts.back')

@section('title', $warehouse->name)

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.warehouses.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <h1 class="text-2xl font-bold">{{ $warehouse->name }}</h1>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl mb-6">
    <dl class="grid grid-cols-2 gap-4 text-sm">
        <div><dt class="text-muted-foreground">Location</dt><dd class="font-medium">{{ $warehouse->city }}, {{ $warehouse->country }}</dd></div>
        <div><dt class="text-muted-foreground">Address</dt><dd class="font-medium">{{ $warehouse->address ?? '-' }}</dd></div>
        <div><dt class="text-muted-foreground">Capacity</dt><dd class="font-medium">{{ number_format($warehouse->capacity_m2) }} m²</dd></div>
        <div><dt class="text-muted-foreground">Refrigerated</dt><dd class="font-medium">{{ $warehouse->is_refrigerated ? 'Yes' : 'No' }}</dd></div>
    </dl>
</div>

<h2 class="text-lg font-semibold mb-3">Shipments from this warehouse ({{ $shipments->total() }})</h2>
<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Shipments from this warehouse</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">Reference</th>
                        <th scope="col" class="px-6 py-3">Destination</th>
                        <th scope="col" class="px-6 py-3">Mode</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3 text-right">CO₂ (kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($shipments as $shipment)
                    <tr>
                        <td class="px-6 py-4 text-sm"><a href="{{ route('logistics.shipments.show', $shipment) }}" class="text-primary hover:underline">{{ $shipment->reference }}</a></td>
                        <td class="px-6 py-4 text-sm text-muted-foreground">{{ $shipment->destination }}</td>
                        <td class="px-6 py-4 text-sm text-muted-foreground">{{ $shipment->transport_mode->label() }}</td>
                        <td class="px-6 py-4 text-sm text-muted-foreground">{{ $shipment->status->label() }}</td>
                        <td class="px-6 py-4 text-right text-sm tabular-nums">{{ number_format((float) $shipment->carbon_footprint_kg, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-sm text-muted-foreground">No shipments yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
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

<h2 class="text-lg font-semibold mb-3">Shipments from this warehouse ({{ $warehouse->shipments->count() }})</h2>
<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Reference</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Destination</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Mode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">CO₂ (kg)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($warehouse->shipments as $shipment)
            <tr>
                <td class="px-6 py-3 text-sm">{{ $shipment->reference }}</td>
                <td class="px-6 py-3 text-sm text-muted-foreground">{{ $shipment->destination }}</td>
                <td class="px-6 py-3 text-sm text-muted-foreground">{{ $shipment->transport_mode->label() }}</td>
                <td class="px-6 py-3 text-sm text-muted-foreground">{{ $shipment->status->label() }}</td>
                <td class="px-6 py-3 text-sm text-right">{{ number_format((float) $shipment->carbon_footprint_kg, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-4 text-sm text-muted-foreground text-center">No shipments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
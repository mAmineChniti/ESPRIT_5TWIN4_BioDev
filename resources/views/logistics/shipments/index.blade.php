@extends('layouts.back')

@section('title', 'Shipments')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Shipments</h1>
    <a href="{{ route('logistics.shipments.create') }}" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
        + Add a shipment
    </a>
</div>

{{-- Carbon footprint overview --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-card rounded-lg shadow p-5">
        <p class="text-sm text-muted-foreground">Total footprint</p>
        <p class="text-3xl font-bold">{{ number_format($totalCo2, 1) }} <span class="text-base font-normal text-muted-foreground">kg CO₂e</span></p>
    </div>
    <div class="bg-card rounded-lg shadow p-5 md:col-span-2">
        <p class="text-sm text-muted-foreground mb-3">Footprint by transport mode</p>
        @php $max = max((float) $byMode->max('co2'), 1); @endphp
        <div class="space-y-2">
            @foreach($byMode as $row)
                <div>
                    <div class="flex justify-between text-xs">
                        <span>{{ $row->transport_mode->label() }} ({{ $row->shipments_count }})</span>
                        <span>{{ number_format((float) $row->co2, 1) }} kg</span>
                    </div>
                                        @php $pct = round(((float) $row->co2 / $max) * 100); @endphp
                    <div class="h-2 rounded bg-muted">
                        <div class="h-2 rounded bg-primary" @style(["width: {$pct}%"])></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4">
    <select name="status" class="rounded-md border-input shadow-sm text-sm">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select name="mode" class="rounded-md border-input shadow-sm text-sm">
        <option value="">All transport modes</option>
        @foreach($modes as $mode)
            <option value="{{ $mode->value }}" @selected($filters['mode'] === $mode->value)>{{ $mode->label() }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-primary text-primary-foreground text-sm font-medium px-4 rounded-md">Filter</button>
    <a href="{{ route('logistics.shipments.index') }}" class="text-sm text-muted-foreground self-center hover:underline">Reset</a>
</form>

<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Reference</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Warehouse</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Destination</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Mode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">CO₂ (kg)</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-card divide-y divide-border">
            @forelse($shipments as $shipment)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $shipment->reference }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->warehouse->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->destination }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->transport_mode->label() }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->status->label() }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ number_format((float) $shipment->carbon_footprint_kg, 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('logistics.shipments.show', $shipment) }}" class="text-primary hover:underline">View</a>
                    <a href="{{ route('logistics.shipments.edit', $shipment) }}" class="text-primary hover:underline">Edit</a>
                    <form action="{{ route('logistics.shipments.destroy', $shipment) }}" method="POST" class="inline"
                          onsubmit="return confirm('Delete shipment {{ $shipment->reference }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-4 text-sm text-muted-foreground text-center">No shipments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
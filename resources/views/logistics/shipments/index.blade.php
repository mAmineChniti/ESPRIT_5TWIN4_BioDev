@extends('layouts.back')

@section('title', 'Shipments')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Shipments</h1>
    <april:button-link href="{{ route('logistics.shipments.create') }}">
        <x-lucide-plus class="size-4" />
        Add a shipment
    </april:button-link>
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
                    <div class="h-2 rounded-md bg-muted">
                        <div class="h-2 rounded-md bg-primary" @style(["width: {$pct}%"])></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4">
    <april:label for="shipment-status-filter" class="sr-only">Filter by status</april:label>
    <april:native-select id="shipment-status-filter" name="status" class="text-sm">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </april:native-select>
    <april:label for="shipment-mode-filter" class="sr-only">Filter by transport mode</april:label>
    <april:native-select id="shipment-mode-filter" name="mode" class="text-sm">
        <option value="">All transport modes</option>
        @foreach($modes as $mode)
            <option value="{{ $mode->value }}" @selected($filters['mode'] === $mode->value)>{{ $mode->label() }}</option>
        @endforeach
    </april:native-select>
    <april:button type="submit">Filter</april:button>
    <a href="{{ route('logistics.shipments.index') }}" class="text-sm text-muted-foreground self-center hover:underline">Reset</a>
</form>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Shipments</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">Reference</th>
                        <th scope="col" class="px-6 py-3">Warehouse</th>
                        <th scope="col" class="px-6 py-3">Destination</th>
                        <th scope="col" class="px-6 py-3">Mode</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3 text-right">CO₂ (kg)</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($shipments as $shipment)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $shipment->reference }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->warehouse->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->destination }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->transport_mode->label() }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $shipment->status->label() }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums">{{ number_format((float) $shipment->carbon_footprint_kg, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('logistics.shipments.show', $shipment) }}" class="text-primary hover:underline">View</a>
                            <a href="{{ route('logistics.shipments.edit', $shipment) }}" class="text-primary hover:underline">Edit</a>
                            <x-confirm-action
                                :action="route('logistics.shipments.destroy', $shipment)"
                                title="Delete this shipment?"
                                description="Shipment {{ $shipment->reference }} will be permanently deleted."
                                triggerVariant="ghost"
                                triggerSize="sm"
                            >Delete</x-confirm-action>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-sm text-muted-foreground">No shipments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
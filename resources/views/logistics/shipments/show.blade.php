@extends('layouts.back')

@section('title', $shipment->reference)

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.shipments.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <h1 class="text-2xl font-bold">Shipment {{ $shipment->reference }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-card rounded-lg shadow p-6">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-muted-foreground">Warehouse</dt>
                <dd class="font-medium"><a href="{{ route('logistics.warehouses.show', $shipment->warehouse) }}" class="text-primary hover:underline">{{ $shipment->warehouse->name }}</a></dd></div>
            <div><dt class="text-muted-foreground">Destination</dt><dd class="font-medium">{{ $shipment->destination }}</dd></div>
            <div><dt class="text-muted-foreground">Product</dt><dd class="font-medium">{{ $shipment->food?->name ?? '-' }}</dd></div>
            <div><dt class="text-muted-foreground">Status</dt><dd class="font-medium">{{ $shipment->status->label() }}</dd></div>
            <div><dt class="text-muted-foreground">Distance</dt><dd class="font-medium">{{ number_format((float) $shipment->distance_km, 1) }} km</dd></div>
            <div><dt class="text-muted-foreground">Weight</dt><dd class="font-medium">{{ number_format((float) $shipment->weight_kg, 2) }} kg</dd></div>
            <div><dt class="text-muted-foreground">Transport</dt><dd class="font-medium">{{ $shipment->transport_mode->label() }}</dd></div>
            <div><dt class="text-muted-foreground">Shipping date</dt><dd class="font-medium">{{ $shipment->shipped_on->format('d M Y') }}</dd></div>
            <div class="col-span-2"><dt class="text-muted-foreground">Carbon footprint</dt>
                <dd class="text-2xl font-bold">{{ number_format((float) $shipment->carbon_footprint_kg, 2) }} <span class="text-base font-normal text-muted-foreground">kg CO₂e</span></dd></div>
        </dl>
    </div>

    <div class="bg-card rounded-lg shadow p-6">
        <h2 class="font-semibold mb-3">Same load, other transport modes</h2>
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Carbon footprint of the same load by transport mode</caption>
            <tbody class="divide-y divide-border">
                @foreach($comparison as $row)
                <tr class="{{ $row['mode'] === $shipment->transport_mode ? 'font-semibold' : '' }}">
                    <td class="py-2">{{ $row['mode']->label() }} @if($row['mode'] === $shipment->transport_mode)<span class="text-xs text-muted-foreground">(current)</span>@endif</td>
                    <td class="py-2 text-right">{{ number_format($row['kg'], 2) }} kg</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-muted-foreground mt-3">Indicative factors, for comparison only.</p>
    </div>
</div>
@endsection
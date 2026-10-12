@extends('layouts.back')

@section('title', __('Shipments'))

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">{{ __('Shipments') }}</h1>
    <april:button-link href="{{ route('logistics.shipments.create') }}">
        <x-lucide-plus class="size-4" />
        {{ __('Add a shipment') }}
    </april:button-link>
</div>

{{-- Carbon footprint overview --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <april:card>
        <x-slot:content>
            <p class="text-sm text-muted-foreground">{{ __('Total footprint') }}</p>
            <p class="text-3xl font-bold">{{ \App\LocalizedNumber::format($totalCo2, 1) }} <span class="text-base font-normal text-muted-foreground">kg CO₂e</span></p>
        </x-slot:content>
    </april:card>
    <april:card class="md:col-span-2">
        <x-slot:content>
            <p class="text-sm text-muted-foreground mb-3">{{ __('Footprint by transport mode') }}</p>
            @php $max = max((float) $byMode->max('co2'), 1); @endphp
            <div class="space-y-2">
                @foreach($byMode as $row)
                    <div>
                        <div class="flex justify-between text-xs">
                            <span>{{ $row->transport_mode->label() }} ({{ $row->shipments_count }})</span>
                            <span>{{ \App\LocalizedNumber::format($row->co2, 1) }} kg</span>
                        </div>
                        @php $pct = round(((float) $row->co2 / $max) * 100); @endphp
                        <div class="h-2 rounded-md bg-muted">
                            <div class="h-2 rounded-md bg-primary" @style(["width: {$pct}%"])></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-slot:content>
    </april:card>
</div>

{{-- Filters. The selects need an explicit width: april:native-select ships
     w-full, which inside a row flex resolves against the whole row, so each
     select would claim the full width and push the Filter button onto a
     wrapped line. --}}
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3" novalidate>
    <april:label for="shipment-status-filter" class="sr-only">{{ __('Filter by status') }}</april:label>
    <april:native-select id="shipment-status-filter" name="status" class="w-48 text-sm">
        <option value="">{{ __('All statuses') }}</option>
        @foreach($statuses as $status)
            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </april:native-select>
    <april:label for="shipment-mode-filter" class="sr-only">{{ __('Filter by transport mode') }}</april:label>
    <april:native-select id="shipment-mode-filter" name="mode" class="w-56 text-sm">
        <option value="">{{ __('All transport modes') }}</option>
        @foreach($modes as $mode)
            <option value="{{ $mode->value }}" @selected($filters['mode'] === $mode->value)>{{ $mode->label() }}</option>
        @endforeach
    </april:native-select>
    <april:button type="submit">{{ __('Filter') }}</april:button>
    <april:button-link href="{{ route('logistics.shipments.index') }}" variant="ghost" size="sm">{{ __('Reset') }}</april:button-link>
</form>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ __('Shipments') }}</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('Reference') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Warehouse') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Destination') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Mode') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-3 text-right">{{ __('CO₂ (kg)') }}</th>
                        <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums">{{ \App\LocalizedNumber::format($shipment->carbon_footprint_kg, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <april:button-link href="{{ route('logistics.shipments.show', $shipment) }}" variant="link" size="sm">
                                {{ __('View') }}
                            </april:button-link>
                            <april:button-link href="{{ route('logistics.shipments.edit', $shipment) }}" variant="link" size="sm">
                                {{ __('Edit') }}
                            </april:button-link>
                            <x-confirm-action
                                :action="route('logistics.shipments.destroy', $shipment)"
                                :title="__('Delete this shipment?')"
                                :description="__('Shipment :reference will be permanently deleted.', ['reference' => $shipment->reference])"
                                triggerVariant="ghost"
                                triggerSize="sm"
                            >{{ __('Delete') }}</x-confirm-action>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-sm text-muted-foreground">
                            {{ __('No shipments found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
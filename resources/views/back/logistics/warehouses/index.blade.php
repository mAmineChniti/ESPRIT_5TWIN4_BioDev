@extends('layouts.back')

@section('title', __('Warehouses'))

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">{{ __('Warehouses') }}</h1>
    <april:button-link href="{{ route('logistics.warehouses.create') }}">
        <x-lucide-plus class="size-4" />
        {{ __('Add a warehouse') }}
    </april:button-link>
</div>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ __('Warehouses') }}</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('Name') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('City') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Capacity') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Refrigerated') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Shipments') }}</th>
                        <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($warehouses as $warehouse)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $warehouse->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $warehouse->city }}, {{ $warehouse->country }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ \App\LocalizedNumber::format($warehouse->capacity_m2) }} m²</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $warehouse->is_refrigerated ? __('Yes') : __('No') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ trans_choice('{0} :count shipment|{1} :count shipment|[2,*] :count shipments', $warehouse->shipments_count) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('logistics.warehouses.show', $warehouse) }}" class="text-primary hover:underline">{{ __('View') }}</a>
                            <a href="{{ route('logistics.warehouses.edit', $warehouse) }}" class="text-primary hover:underline">{{ __('Edit') }}</a>
                            @if(auth()->user()?->isAdmin())
                                <x-confirm-action
                                    :action="route('logistics.warehouses.destroy', $warehouse)"
                                    :title="__('Delete this warehouse?')"
                                    :description="__('This warehouse and its :count shipment(s) will be permanently deleted.', ['count' => $warehouse->shipments_count])"
                                    triggerVariant="ghost"
                                    triggerSize="sm"
                                >{{ __('Delete') }}</x-confirm-action>
                            @else
                                {{-- Deleting cascades to every shipment in the warehouse,
                                     so only an administrator is offered the action. --}}
                                <april:tooltip>
                                    <x-slot:trigger>
                                        <span class="cursor-not-allowed text-sm text-muted-foreground opacity-50"
                                              aria-disabled="true">{{ __('Delete') }}</span>
                                    </x-slot:trigger>
                                    <x-slot:content>{{ __('Only an administrator can delete a warehouse.') }}</x-slot:content>
                                </april:tooltip>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-sm text-muted-foreground">
                            {{ __('No warehouses found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-4">{{ $warehouses->links() }}</div>
@endsection
@extends('layouts.back')

@section('title', __('Journeys'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">{{ __('Traceability journeys') }}</h1>
    <p class="mt-1 text-muted-foreground">{{ __('Review the journeys generated for products.') }}</p>
</div>

<form method="GET" action="{{ route('processor.journeys.index') }}" novalidate class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-4">
    <div class="min-w-48 flex-1 space-y-1">
        <april:label for="journey-search">{{ __('Search products or locations') }}</april:label>
        <april:input id="journey-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="120" />
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="journey-type">{{ __('Step type') }}</april:label>
        <april:native-select id="journey-type" name="type">
            <option value="">{{ __('All step types') }}</option>
            @foreach(\App\Models\JourneyStep::labels() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </april:native-select>
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="journey-date-from">{{ __('From date') }}</april:label>
        <april:input id="journey-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" />
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="journey-date-to">{{ __('To date') }}</april:label>
        <april:input id="journey-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" />
    </div>
    <div class="flex gap-2">
        <april:button type="submit">{{ __('Filter') }}</april:button>
        @if ($hasFilters)
            <april:button-link href="{{ route('processor.journeys.index') }}" variant="outline">{{ __('Clear') }}</april:button-link>
        @endif
    </div>
</form>

@if ($errors->any())
    <p role="alert" class="mb-4 text-sm text-destructive">{{ $errors->first() }}</p>
@endif

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ __('Traceability journeys') }}</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('Product') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Score') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Steps') }}</th>
                        <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($journeys as $journey)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-foreground">
                            {{ $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id]) }}
                        </td>
                        <td class="px-6 py-4 text-sm tabular-nums text-muted-foreground">{{ $journey->environmental_score }}/100</td>
                        <td class="px-6 py-4 text-sm tabular-nums text-muted-foreground">{{ $journey->steps_count }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <april:button-link
                                    href="{{ route('processor.journeys.steps.index', $journey) }}"
                                    variant="ghost"
                                    size="sm"
                                    aria-label="{{ __('Manage steps for :name', ['name' => $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id])]) }}"
                                >
                                    <x-lucide-route class="size-4" />
                                </april:button-link>
                                <april:button-link
                                    href="{{ route('processor.journeys.show', $journey) }}"
                                    variant="ghost"
                                    size="sm"
                                    aria-label="{{ __('View :name', ['name' => $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id])]) }}"
                                >
                                    <x-lucide-eye class="size-4" />
                                </april:button-link>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-6 text-center text-sm text-muted-foreground">
                            {{ $hasFilters ? __('No journeys match your filters.') : __('No journeys generated.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-6">{{ $journeys->links() }}</div>
@endsection

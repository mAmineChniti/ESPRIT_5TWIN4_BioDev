@extends('layouts.back')

@section('title', __('Journey steps'))

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <april:button-link href="{{ route('processor.journeys.show', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            {{ __('Back to journey') }}
        </april:button-link>
        <h1 class="mt-2 text-2xl font-bold">{{ __('Journey workflow') }}</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id]) }}</p>
    </div>
    <april:button-link href="{{ route('processor.journeys.steps.create', $journey) }}">
        <x-lucide-plus class="size-4" />
        {{ __('Add a step') }}
    </april:button-link>
</div>

<form method="GET" action="{{ route('processor.journeys.steps.index', $journey) }}" novalidate class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-4">
    <div class="min-w-48 flex-1 space-y-1">
        <april:label for="step-search">{{ __('Search by location') }}</april:label>
        <april:input id="step-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="120" />
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="step-type">{{ __('Step type') }}</april:label>
        <april:native-select id="step-type" name="type">
            <option value="">{{ __('All step types') }}</option>
            @foreach(\App\Models\JourneyStep::labels() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </april:native-select>
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="step-date-from">{{ __('From date') }}</april:label>
        <april:input id="step-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" />
    </div>
    <div class="min-w-36 space-y-1">
        <april:label for="step-date-to">{{ __('To date') }}</april:label>
        <april:input id="step-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" />
    </div>
    <div class="flex gap-2">
        <april:button type="submit">{{ __('Filter') }}</april:button>
        @if ($hasFilters)
            <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="outline">{{ __('Clear') }}</april:button-link>
        @endif
    </div>
</form>

@if ($errors->any())
    <p role="alert" class="mb-4 text-sm text-destructive">{{ $errors->first() }}</p>
@endif

<april:card class="max-w-3xl">
    <x-slot:content>
        @if(session('success'))
            <april:alert class="mb-4" aria-live="polite">
                <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
                <x-slot:description>{{ session('success') }}</x-slot:description>
            </april:alert>
        @endif

        <x-journey-workflow :journey="$journey" :display-steps="$steps" :has-filters="$hasFilters" manage />
    </x-slot:content>
</april:card>

<div class="mt-6">{{ $steps->links() }}</div>
@endsection

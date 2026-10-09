@extends('layouts.back')

@section('title', 'Journey details')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <april:button-link href="{{ route('processor.journeys.index') }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            Back
        </april:button-link>
        <div>
            <h1 class="text-2xl font-bold">Journey details</h1>
            <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <april:button-link href="{{ route('processor.journeys.steps.create', $journey) }}">
            <x-lucide-plus class="size-4" />
            Add a step
        </april:button-link>
        <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="outline">
            <x-lucide-route class="size-4" />
            Manage steps
        </april:button-link>
        @if($journey->qr_code)
            <april:button-link href="{{ route('processor.journeys.qr', $journey) }}" variant="outline">
                View QR code
            </april:button-link>
        @endif
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <april:card>
        <x-slot:content>
            <p class="text-sm text-muted-foreground">Environmental score</p>
            <p class="mt-1 text-2xl font-bold text-primary">{{ $journey->environmental_score }}/100</p>
        </x-slot:content>
    </april:card>
    <april:card>
        <x-slot:content>
            <p class="text-sm text-muted-foreground">Total distance</p>
            <p class="mt-1 text-2xl font-bold">{{ $journey->total_distance_km }} km</p>
        </x-slot:content>
    </april:card>
    <april:card>
        <x-slot:content>
            <p class="text-sm text-muted-foreground">QR code</p>
            <p class="mt-1 break-all text-sm font-medium">{{ $journey->qr_code ?? 'Not generated' }}</p>
        </x-slot:content>
    </april:card>
</div>

<april:card class="max-w-3xl">
    <x-slot:title class="text-lg">Workflow</x-slot:title>
    <x-slot:description>Where this product stands on its way from origin to sale.</x-slot:description>
    <x-slot:content>
        <x-journey-workflow :journey="$journey" />
    </x-slot:content>
</april:card>
@endsection

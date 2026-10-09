@extends('layouts.back')

@section('title', 'Journey steps')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <april:button-link href="{{ route('processor.journeys.show', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            Back to journey
        </april:button-link>
        <h1 class="mt-2 text-2xl font-bold">Journey workflow</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
    </div>
    <april:button-link href="{{ route('processor.journeys.steps.create', $journey) }}">
        <x-lucide-plus class="size-4" />
        Add a step
    </april:button-link>
</div>

<april:card class="max-w-3xl">
    <x-slot:content>
        @if(session('success'))
            <april:alert class="mb-4" aria-live="polite">
                <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
                <x-slot:description>{{ session('success') }}</x-slot:description>
            </april:alert>
        @endif

        <x-journey-workflow :journey="$journey" manage />
    </x-slot:content>
</april:card>
@endsection

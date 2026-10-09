@extends('layouts.back')

@section('title', 'Add a step')

@section('content')
<div class="mb-6">
    <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back to workflow
    </april:button-link>
    <h1 class="mt-2 text-2xl font-bold">Add a step</h1>
    <p class="mt-1 text-muted-foreground">
        Step {{ $step->step_order }} · {{ $step->label() }} for {{ $journey->product?->name ?? 'Product #'.$journey->product_id }}
    </p>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ route('processor.journeys.steps.store', $journey) }}" method="POST" novalidate>
            @csrf
            @include('back.processor.journeys.steps.form', ['submitLabel' => 'Add step'])
        </form>
    </x-slot:content>
</april:card>
@endsection

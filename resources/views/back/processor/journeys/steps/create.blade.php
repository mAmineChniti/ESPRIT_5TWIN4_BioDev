@extends('layouts.back')

@section('title', __('Add a step'))

@section('content')
<div class="mb-6">
    <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        {{ __('Back to workflow') }}
    </april:button-link>
    <h1 class="mt-2 text-2xl font-bold">{{ __('Add a step') }}</h1>
    <p class="mt-1 text-muted-foreground">
        {{ __('Step :order · :label for :name', ['order' => $step->step_order, 'label' => $step->label(), 'name' => $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id])]) }}
    </p>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ route('processor.journeys.steps.store', $journey) }}" method="POST" novalidate>
            @csrf
            @include('back.processor.journeys.steps.form', ['submitLabel' => __('Add step')])
        </form>
    </x-slot:content>
</april:card>
@endsection

@extends('layouts.back')

@section('title', 'Edit a step')

@section('content')
<div class="mb-6">
    <april:button-link href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back to details
    </april:button-link>
    <h1 class="mt-2 text-2xl font-bold">Edit step #{{ $step->step_order }}</h1>
    <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ route('processor.journeys.steps.update', [$journey, $step]) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            @include('back.processor.journeys.steps.form', ['submitLabel' => 'Save changes'])
        </form>
    </x-slot:content>
</april:card>
@endsection

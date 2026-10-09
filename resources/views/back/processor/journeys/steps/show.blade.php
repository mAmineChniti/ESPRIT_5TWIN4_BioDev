@extends('layouts.back')

@section('title', 'Step details')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            Back to workflow
        </april:button-link>
        <h1 class="mt-2 text-2xl font-bold">Step #{{ $step->step_order }} · {{ $step->label() }}</h1>
    </div>
    <div class="flex gap-2">
        <april:button-link href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" variant="outline">Edit</april:button-link>
        <x-confirm-action
            :action="route('processor.journeys.steps.destroy', [$journey, $step])"
            label="Delete step"
            title="Delete this step?"
            description="Step {{ $step->step_order }} ({{ $step->label() }}) will be permanently deleted from this journey."
            triggerVariant="destructive"
            triggerSize="sm"
        >Delete</x-confirm-action>
    </div>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-muted-foreground">Order</dt>
                <dd class="mt-1 text-sm tabular-nums text-foreground">{{ $step->step_order }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">Type</dt>
                <dd class="mt-1 text-sm text-foreground"><april:badge variant="secondary">{{ $step->label() }}</april:badge></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">Location</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->location }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">Date</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->step_date?->format('Y-m-d') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">Description</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->description ?: 'No description.' }}</dd>
            </div>
        </dl>
    </x-slot:content>
</april:card>
@endsection

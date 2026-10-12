@extends('layouts.back')

@section('title', __('Step details'))

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <april:button-link href="{{ route('processor.journeys.steps.index', $journey) }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            {{ __('Back to workflow') }}
        </april:button-link>
        <h1 class="mt-2 text-2xl font-bold">{{ __('Step #:order · :label', ['order' => $step->step_order, 'label' => $step->label()]) }}</h1>
    </div>
    <div class="flex gap-2">
        <april:button-link href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" variant="outline">{{ __('Edit') }}</april:button-link>
        <x-confirm-action
            :action="route('processor.journeys.steps.destroy', [$journey, $step])"
            :label="__('Delete step')"
            :title="__('Delete this step?')"
            :description="__('Step :order (:label) will be permanently deleted from this journey.', ['order' => $step->step_order, 'label' => $step->label()])"
            triggerVariant="destructive"
            triggerSize="sm"
        >{{ __('Delete') }}</x-confirm-action>
    </div>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Order') }}</dt>
                <dd class="mt-1 text-sm tabular-nums text-foreground">{{ $step->step_order }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Type') }}</dt>
                <dd class="mt-1 text-sm text-foreground"><april:badge variant="secondary">{{ $step->label() }}</april:badge></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Location') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->location }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Date') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->step_date?->format('Y-m-d') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Description') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $step->description ?: __('No description.') }}</dd>
            </div>
        </dl>
    </x-slot:content>
</april:card>
@endsection

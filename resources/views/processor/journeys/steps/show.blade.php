@extends('layouts.back')

@section('title', 'Step details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('processor.journeys.steps.index', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to steps</a>
        <h1 class="mt-2 text-2xl font-bold">Step details #{{ $step->step_order }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">Edit</a>
        <x-confirm-action
            :action="route('processor.journeys.steps.destroy', [$journey, $step])"
            title="Delete this step?"
            description="This step will be permanently deleted from the journey."
            triggerVariant="destructive"
            triggerSize="sm"
        >Delete</x-confirm-action>
    </div>
</div>

<dl class="grid grid-cols-1 gap-6 rounded-lg bg-card p-6 shadow sm:grid-cols-2">
    <div><dt class="text-sm text-muted-foreground">Order</dt><dd class="mt-1 font-medium">{{ $step->step_order }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Type</dt><dd class="mt-1 font-medium">{{ ucfirst($step->type) }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Location</dt><dd class="mt-1 font-medium">{{ $step->location }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Date</dt><dd class="mt-1 font-medium">{{ $step->step_date?->format('Y-m-d') }}</dd></div>
    <div class="sm:col-span-2"><dt class="text-sm text-muted-foreground">Description</dt><dd class="mt-1">{{ $step->description ?: 'No description.' }}</dd></div>
</dl>
@endsection

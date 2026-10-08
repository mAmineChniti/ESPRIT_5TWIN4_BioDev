@extends('layouts.back')

@section('title', 'Journey details')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('processor.journeys.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <div>
        <h1 class="text-2xl font-bold">Journey details</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-lg bg-card p-5 shadow"><p class="text-sm text-muted-foreground">Environmental score</p><p class="mt-1 text-2xl font-bold text-primary">{{ $journey->environmental_score }}/100</p></div>
    <div class="rounded-lg bg-card p-5 shadow"><p class="text-sm text-muted-foreground">Total distance</p><p class="mt-1 text-2xl font-bold">{{ $journey->total_distance_km }} km</p></div>
    <div class="rounded-lg bg-card p-5 shadow">
        <p class="text-sm text-muted-foreground">QR code</p>
        <p class="mt-1 break-all text-sm font-medium">{{ $journey->qr_code ?? 'Not generated' }}</p>
        @if($journey->qr_code)
            <a href="{{ route('processor.journeys.qr', $journey) }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">View QR code</a>
        @endif
    </div>
</div>

<div class="rounded-lg bg-card p-6 shadow">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Journey steps</h2>
        <april:button-link href="{{ route('processor.journeys.steps.create', $journey) }}">
            <x-lucide-plus class="size-4" />
            Add a step
        </april:button-link>
    </div>
    <ol class="space-y-4">
        @forelse($journey->steps as $step)
            <li class="flex items-start justify-between border-l-2 border-primary pl-4">
                <div>
                    <div class="flex flex-wrap items-baseline gap-2"><span class="font-semibold">{{ $step->step_order }}. {{ ucfirst($step->type) }}</span><span class="text-sm text-muted-foreground">{{ $step->step_date?->format('Y-m-d') }}</span></div>
                    <p class="text-sm">{{ $step->location }}</p>
                    @if($step->description)<p class="text-sm text-muted-foreground">{{ $step->description }}</p>@endif
                </div>
                <div class="ml-4 flex shrink-0 gap-3 text-sm">
                    <a href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}" class="text-primary hover:underline">Details</a>
                    <a href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" class="text-primary hover:underline">Edit</a>
                    <x-confirm-action
                        :action="route('processor.journeys.steps.destroy', [$journey, $step])"
                        title="Delete this step?"
                        description="This step will be permanently deleted from the journey."
                        triggerVariant="ghost"
                        triggerSize="sm"
                    >Delete</x-confirm-action>
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">No steps recorded.</li>
        @endforelse
    </ol>
</div>
@endsection

@extends('layouts.back')

@section('title', 'Journey steps')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('processor.journeys.show', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to journey</a>
        <h1 class="mt-2 text-2xl font-bold">Journey steps</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
    </div>
    <a href="{{ route('processor.journeys.steps.create', $journey) }}" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">Add a step</a>
</div>

<div class="overflow-hidden rounded-lg bg-card shadow">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Order</th>
                <th scope="col" class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Type</th>
                <th scope="col" class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Location</th>
                <th scope="col" class="px-6 py-3 text-left text-xs uppercase text-muted-foreground">Date</th>
                <th scope="col" class="px-6 py-3 text-right text-xs uppercase text-muted-foreground">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($journey->steps as $step)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">{{ $step->step_order }}</td>
                    <td class="px-6 py-4 text-sm">{{ ucfirst($step->type) }}</td>
                    <td class="px-6 py-4 text-sm">{{ $step->location }}</td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $step->step_date?->format('Y-m-d') }}</td>
                    <td class="space-x-3 px-6 py-4 text-right text-sm">
                        <a href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}" class="text-primary hover:underline">Details</a>
                        <a href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" class="text-primary hover:underline">Edit</a>
                        <x-confirm-action
                            :action="route('processor.journeys.steps.destroy', [$journey, $step])"
                            title="Delete this step?"
                            description="This step will be permanently deleted from the journey."
                            triggerVariant="ghost"
                            triggerSize="sm"
                        >Delete</x-confirm-action>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-6 text-center text-sm text-muted-foreground">No steps recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

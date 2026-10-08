@extends('layouts.back')

@section('title', 'Journey steps')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('processor.journeys.show', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to journey</a>
        <h1 class="mt-2 text-2xl font-bold">Journey steps</h1>
        <p class="mt-1 text-muted-foreground">{{ $journey->product?->name ?? 'Product #'.$journey->product_id }}</p>
    </div>
    <april:button-link href="{{ route('processor.journeys.steps.create', $journey) }}">
        <x-lucide-plus class="size-4" />
        Add a step
    </april:button-link>
</div>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Steps recorded for this journey</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">Order</th>
                        <th scope="col" class="px-6 py-3">Type</th>
                        <th scope="col" class="px-6 py-3">Location</th>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($journey->steps as $step)
                    <tr>
                        <td class="px-6 py-4 tabular-nums text-sm font-medium">{{ $step->step_order }}</td>
                        <td class="px-6 py-4 text-sm">{{ ucfirst($step->type) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $step->location }}</td>
                        <td class="px-6 py-4 tabular-nums text-sm text-muted-foreground">{{ $step->step_date?->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}" class="mr-3 text-primary hover:underline">Details</a>
                            <a href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}" class="mr-3 text-primary hover:underline">Edit</a>
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
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-sm text-muted-foreground">No steps recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>
@endsection

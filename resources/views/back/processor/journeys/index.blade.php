@extends('layouts.back')

@section('title', 'Journeys')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Traceability journeys</h1>
    <p class="mt-1 text-muted-foreground">Review the journeys generated for products.</p>
</div>

<april:card class="overflow-hidden">
    <x-slot:content>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Traceability journeys</caption>
                <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-6 py-3">Product</th>
                        <th scope="col" class="px-6 py-3">Score</th>
                        <th scope="col" class="px-6 py-3">Steps</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($journeys as $journey)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-foreground">
                            {{ $journey->product?->name ?? 'Product #'.$journey->product_id }}
                        </td>
                        <td class="px-6 py-4 text-sm tabular-nums text-muted-foreground">{{ $journey->environmental_score }}/100</td>
                        <td class="px-6 py-4 text-sm tabular-nums text-muted-foreground">{{ $journey->steps_count }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <april:button-link
                                    href="{{ route('processor.journeys.steps.index', $journey) }}"
                                    variant="ghost"
                                    size="sm"
                                    aria-label="Manage steps for {{ $journey->product?->name ?? 'Product #'.$journey->product_id }}"
                                >
                                    <x-lucide-route class="size-4" />
                                </april:button-link>
                                <april:button-link
                                    href="{{ route('processor.journeys.show', $journey) }}"
                                    variant="ghost"
                                    size="sm"
                                    aria-label="View {{ $journey->product?->name ?? 'Product #'.$journey->product_id }}"
                                >
                                    <x-lucide-eye class="size-4" />
                                </april:button-link>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-6 text-center text-sm text-muted-foreground">No journeys generated.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-6">{{ $journeys->links() }}</div>
@endsection

@extends('layouts.back')

@section('title', 'Journeys')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Traceability journeys</h1>
    <p class="mt-1 text-muted-foreground">Review the journeys generated for products.</p>
</div>

<div class="overflow-hidden rounded-lg bg-card shadow">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Product</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Score</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Steps</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border bg-card">
            @forelse($journeys as $journey)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-foreground">
                        {{ $journey->product?->name ?? 'Product #'.$journey->product_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $journey->environmental_score }}/100</td>
                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ $journey->steps_count }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('processor.journeys.show', $journey) }}" class="text-primary hover:underline">Details</a>
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

<div class="mt-6">{{ $journeys->links() }}</div>
@endsection

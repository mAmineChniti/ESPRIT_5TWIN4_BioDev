@extends('layouts.back')

@section('title', 'Analysis Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Analysis reports</h1>
    <p class="mt-1 text-sm text-muted-foreground">
        What users believe the AI detector got wrong about a product. Upholding a report means our
        analysis is wrong; it never changes the product&rsquo;s score.
    </p>
</div>

@if ($disputes->isEmpty())
    <april:card>
        <x-slot:content>
            <div class="p-8 text-center text-muted-foreground">
                <x-lucide-check-circle class="mx-auto mb-3 size-12" />
                <p>No analysis reports{{ $status ? ' with that status' : '' }}.</p>
            </div>
        </x-slot:content>
    </april:card>
@else
    {{-- Status tabs. The counts come from SQL, so they stay right on every page. --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <april:button-link href="{{ route('admin.analysis-disputes.index') }}" size="sm"
            variant="{{ $status === null ? 'default' : 'secondary' }}">All</april:button-link>
        @foreach ($statuses as $case)
            <april:button-link
                href="{{ route('admin.analysis-disputes.index', ['status' => $case->value]) }}"
                size="sm"
                variant="{{ $status === $case->value ? 'default' : 'secondary' }}">
                {{ $case->label() }}
                <span class="ml-1 tabular-nums opacity-70">{{ $counts[$case->value] ?? 0 }}</span>
            </april:button-link>
        @endforeach
    </div>

    {{-- The same shape as the greenwashing reports queue, because they are the same
         job: an admin reading a list of user claims and deciding on each one. --}}
    <april:data-table>
        <x-slot:caption>Reports that the analysis is wrong</x-slot:caption>

        <x-slot:header>
            <tr>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Product</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Reported by</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Reason</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Comment</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Decision</th>
            </tr>
        </x-slot:header>

        <x-slot:body>
            @foreach ($disputes as $dispute)
                <tr class="align-top">
                    <td class="p-4 align-middle font-medium">
                        <a href="{{ route('foods.show', $dispute->food_id) }}" class="text-primary hover:underline">
                            {{ $dispute->food?->name ?? 'Deleted product' }}
                        </a>
                    </td>
                    <td class="p-4 align-middle">
                        {{ $dispute->user?->name ?? 'Deleted account' }}
                        <span class="block text-xs text-muted-foreground">{{ $dispute->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="p-4 align-middle">
                        {{ $dispute->reason->label() }}
                        @if ($dispute->targetsAFinding())
                            <span class="block text-xs text-muted-foreground">
                                About: {{ $dispute->finding_category->reportLabel() }}
                            </span>
                        @endif
                    </td>
                    <td class="max-w-sm p-4 align-middle text-muted-foreground">{{ $dispute->comment }}</td>
                    <td class="p-4 align-middle">
                        @php [$variant, $tone] = $dispute->status->badgeClasses(); @endphp
                        <april:badge variant="{{ $variant }}" class="{{ $tone }}">{{ $dispute->status->label() }}</april:badge>
                    </td>
                    <td class="p-4 text-right align-middle">
                        @if ($dispute->isPending())
                            <x-analysis-dispute-decide :dispute="$dispute" />
                        @else
                            <span class="text-xs text-muted-foreground">
                                @if ($dispute->resolution_note)
                                    <span class="block max-w-[18rem] text-foreground">{{ $dispute->resolution_note }}</span>
                                @endif
                                {{ $dispute->reviewer?->name ?? 'An administrator' }},
                                {{ $dispute->reviewed_at?->diffForHumans() }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:body>
    </april:data-table>

    <div class="mt-6">{{ $disputes->links() }}</div>
@endif
@endsection
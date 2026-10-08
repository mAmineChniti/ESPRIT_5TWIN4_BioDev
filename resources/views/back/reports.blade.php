@extends('layouts.back')

@section('title', 'Greenwashing Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Greenwashing Reports</h1>
    <p class="mt-1 text-sm text-muted-foreground">Review misleading claims reported by consumers.</p>
</div>

@if($reports->isEmpty())
    <april:card>
        <x-slot:content>
            <div class="p-8 text-center text-muted-foreground">
                <x-lucide-check-circle class="mx-auto mb-3 size-12 text-muted-foreground" />
                <p>No reports found. All good!</p>
            </div>
        </x-slot:content>
    </april:card>
@else
    <april:data-table>
        <x-slot:header>
            <tr>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Product</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Reported by</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Reason</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Details</th>
                <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
            </tr>
        </x-slot:header>

        <x-slot:body>
            @foreach($reports as $report)
                <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                    <td class="p-4 align-middle font-medium">
                        <a href="{{ route('foods.show', $report->food_id) }}" class="text-primary hover:underline">
                            {{ $report->food?->name ?? 'Deleted Product' }}
                        </a>
                    </td>
                    <td class="p-4 align-middle">
                        {{ $report->user?->name ?? 'Unknown' }}<br>
                        <span class="text-xs text-muted-foreground">{{ $report->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="p-4 align-middle">{{ $report->reason->label() }}</td>
                    <td class="max-w-xs truncate p-4 align-middle text-muted-foreground" title="{{ $report->details }}">
                        {{ $report->details ?? '—' }}
                    </td>
                    <td class="p-4 align-middle">
                        @php
                            // Theme tokens only. April's badge has no "success"
                            // variant, so a dismissed report reads as secondary.
                            [$variant, $label] = match ($report->status->value) {
                                'pending' => ['outline', 'Pending'],
                                'upheld' => ['destructive', 'Upheld (Guilty)'],
                                default => ['secondary', 'Dismissed'],
                            };
                        @endphp
                        <april:badge variant="{{ $variant }}">{{ $label }}</april:badge>
                    </td>
                    <td class="p-4 text-right align-middle">
                        @if($report->status->value === 'pending')
                            {{-- Both decisions change a product's public trust
                                 score, so each is confirmed rather than
                                 applied on a single click. --}}
                            <div class="inline-flex items-center gap-2">
                                <april:alert-dialog>
                                    <x-slot:trigger>
                                        <april:button type="button" size="sm" variant="destructive">
                                            Uphold
                                        </april:button>
                                    </x-slot:trigger>
                                    <x-slot:content>
                                        <div>
                                            <h2 class="text-lg font-semibold" x-bind="title">Uphold this report?</h2>
                                            <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                                                This records
                                                <strong>{{ $report->food?->name ?? 'the product' }}</strong>
                                                as guilty and lowers its transparency score by 15 points,
                                                up to a cap of 40.
                                            </p>
                                        </div>
                                        <april:alert-dialog-footer>
                                            <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                                            <form action="{{ route('reports.update', $report) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="upheld">
                                                <april:button type="submit" variant="destructive" x-bind="action">
                                                    Uphold report
                                                </april:button>
                                            </form>
                                        </april:alert-dialog-footer>
                                    </x-slot:content>
                                </april:alert-dialog>

                                <april:alert-dialog>
                                    <x-slot:trigger>
                                        <april:button type="button" size="sm" variant="outline">
                                            Dismiss
                                        </april:button>
                                    </x-slot:trigger>
                                    <x-slot:content>
                                        <div>
                                            <h2 class="text-lg font-semibold" x-bind="title">Dismiss this report?</h2>
                                            <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                                                This records the report against
                                                <strong>{{ $report->food?->name ?? 'the product' }}</strong>
                                                as a false alarm, and the product keeps its current score.
                                            </p>
                                        </div>
                                        <april:alert-dialog-footer>
                                            <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                                            <form action="{{ route('reports.update', $report) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="dismissed">
                                                <april:button type="submit" variant="outline" x-bind="action">
                                                    Dismiss report
                                                </april:button>
                                            </form>
                                        </april:alert-dialog-footer>
                                    </x-slot:content>
                                </april:alert-dialog>
                            </div>
                        @else
                            <span class="text-xs text-muted-foreground">
                                Reviewed {{ $report->reviewed_at?->diffForHumans() }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:body>
    </april:data-table>

    <div class="mt-6">{{ $reports->links() }}</div>
@endif
@endsection
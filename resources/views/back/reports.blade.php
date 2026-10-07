@extends('layouts.back')

@section('title', 'Greenwashing Reports')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Greenwashing Reports</h1>
    </div>
    <p class="text-muted-foreground text-sm mt-1">Review misleading claims reported by consumers.</p>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden border border-border">
    @if($reports->isEmpty())
        <div class="p-8 text-center text-muted-foreground">
            <x-lucide-check-circle class="size-12 mx-auto text-muted mb-3" />
            <p>No reports found. All good!</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted-foreground uppercase bg-muted/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 font-medium">Product</th>
                        <th scope="col" class="px-6 py-3 font-medium">Reported By</th>
                        <th scope="col" class="px-6 py-3 font-medium">Reason</th>
                        <th scope="col" class="px-6 py-3 font-medium">Details</th>
                        <th scope="col" class="px-6 py-3 font-medium">Status</th>
                        <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($reports as $report)
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4 font-medium">
                                <a href="{{ route('foods.show', $report->food_id) }}" class="text-primary hover:underline">
                                    {{ $report->food?->name ?? 'Deleted Product' }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                {{ $report->user?->name ?? 'Unknown' }}<br>
                                <span class="text-xs text-muted-foreground">{{ $report->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $report->reason->label() }}
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate text-muted-foreground" title="{{ $report->details }}">
                                {{ $report->details ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($report->status->value === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-500">
                                        Pending
                                    </span>
                                @elseif($report->status->value === 'upheld')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-500">
                                        Upheld (Guilty)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-500">
                                        Dismissed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($report->status->value === 'pending')
                                    <form action="{{ route('reports.update', $report) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="upheld">
                                        <button type="submit" class="text-xs bg-destructive text-destructive-foreground px-2 py-1 rounded hover:bg-destructive/90" title="Confirm Fraud">
                                            Uphold
                                        </button>
                                    </form>
                                    <form action="{{ route('reports.update', $report) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="dismissed">
                                        <button type="submit" class="text-xs bg-muted text-foreground px-2 py-1 rounded hover:bg-muted/80 border border-border" title="False Alarm">
                                            Dismiss
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-muted-foreground">Reviewed</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

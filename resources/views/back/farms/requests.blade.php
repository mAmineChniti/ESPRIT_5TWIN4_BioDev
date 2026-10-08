@extends('layouts.back')

@section('title', 'Farm Requests')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">Farm Requests</h1>
                <april:badge variant="secondary">
                    {{ $requests->total() }} pending
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                A farm is published publicly only after approval.
            </p>
        </div>
        <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
            Back to list
        </april:button-link>
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Farms awaiting approval</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Farm</th>
                            <th scope="col" class="px-6 py-3">Producer</th>
                            <th scope="col" class="px-6 py-3">Region</th>
                            <th scope="col" class="px-6 py-3">Area</th>
                            <th scope="col" class="px-6 py-3">Submitted</th>
                            <th scope="col" class="px-6 py-3 text-right">Decision</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($requests as $farm)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    <div>{{ $farm->name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $farm->address }}</div>
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">
                                    {{ $farm->producer_name ?? ($farm->user?->name ?? 'Not specified') }}
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $farm->region?->name ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ number_format((float) $farm->surface_hectares, 1) }} ha</td>
                                <td class="px-6 py-4 font-mono text-xs text-muted-foreground">
                                    {{ $farm->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('back.farms.approve', $farm) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <april:button type="submit" size="sm">
                                                <x-lucide-check class="mr-1 size-3.5" />
                                                Approve
                                            </april:button>
                                        </form>

                                        <x-confirm-action
                                            :action="route('back.farms.reject', $farm)"
                                            method="PATCH"
                                            label="Confirm rejection"
                                            title="Reject this request?"
                                            description="The reason will be shared with the producer. The farm will not be published."
                                            trigger-variant="destructive"

                                        >
                                            <x-lucide-x class="mr-1 size-3.5" />
                                            Reject
                                        </x-confirm-action>

                                        <april:button-link
                                            href="{{ route('back.farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="View details for {{ $farm->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                    No pending requests.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($requests->hasPages())
            <x-slot:footer>
                {{ $requests->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
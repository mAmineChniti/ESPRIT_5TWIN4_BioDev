@extends('layouts.back')

@section('title', __('Farm Requests'))

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">{{ __('Farm Requests') }}</h1>
                <april:badge variant="secondary">
                    {{ $requests->total() }} {{ __('pending') }}
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ __('A farm is published publicly only after approval.') }}
            </p>
        </div>
        <april:button-link href="{{ route('farms.index') }}" variant="outline">
            {{ __('Back to list') }}
        </april:button-link>
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Farms awaiting approval') }}</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('Farm') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Producer') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Region') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Area') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Submitted') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">{{ __('Decision') }}</th>
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
                                    {{ $farm->producer_name ?? ($farm->user?->name ?? __('Not specified')) }}
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $farm->region?->name ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ \App\LocalizedNumber::format($farm->surface_hectares, 1) }} ha</td>
                                <td class="px-6 py-4 font-mono text-xs text-muted-foreground">
                                    {{ $farm->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('farms.approve', $farm) }}" class="inline" novalidate>
                                            @csrf
                                            @method('PATCH')
                                            <april:button type="submit" size="sm">
                                                <x-lucide-check class="mr-1 size-3.5" />
                                                {{ __('Approve') }}
                                            </april:button>
                                        </form>

                                        <x-farm-reject-action
                                            :action="route('farms.reject', $farm)"
                                            :farm-name="$farm->name"
                                            :farm-id="$farm->id"
                                        />

                                        <april:button-link
                                            href="{{ route('farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="{{ __('View details for :name', ['name' => $farm->name]) }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                    {{ __('No pending requests.') }}
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
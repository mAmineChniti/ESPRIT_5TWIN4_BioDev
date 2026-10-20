@extends('layouts.back')

@section('title', __('Farm Management'))

@section('content')
    @php
        use App\Enums\FarmStatus;

        $isAdmin = auth()->user()->isAdmin();
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">{{ __('Farms') }}</h1>
                <april:badge variant="{{ $isAdmin ? 'default' : 'secondary' }}">
                    {{ $isAdmin ? __('Admin oversight') : __('Producer area') }}
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                @if($isAdmin)
                    {{ __('Oversee all agricultural farms in the country.') }}
                @else
                    {{ __('Manage your production farms and track approval requests.') }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <april:button-link href="{{ route('farms.export.csv', request()->query()) }}" variant="outline">
                <x-lucide-file-spreadsheet class="mr-2 size-4" />
                {{ __('Export Excel') }}
            </april:button-link>

            <april:button-link href="{{ route('farms.export.pdf', request()->query()) }}" variant="outline">
                <x-lucide-file-text class="mr-2 size-4" />
                {{ __('Export PDF') }}
            </april:button-link>

            @if($isAdmin && $pendingCount > 0)
                <april:button-link href="{{ route('farms.requests') }}">
                    <x-lucide-clipboard-check class="mr-2 size-4" />
                    {{ __('Pending requests (:count)', ['count' => $pendingCount]) }}
                </april:button-link>
            @endif

            <april:button-link href="{{ route('farms.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                {{ __('Add a farm') }}
            </april:button-link>
        </div>
    </div>

    {{-- ===== KPI statistics ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Total farms') }}</span>
                <span class="text-3xl font-bold text-foreground">{{ $stats['total'] }}</span>
                <span class="text-xs text-muted-foreground">{{ __('registered farms') }}</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-primary">{{ __('Approved') }}</span>
                <span class="text-3xl font-bold text-primary">{{ $stats['approved'] }}</span>
                <span class="text-xs text-primary/70">
                    {{ __(':percent% of total', ['percent' => $stats['total'] > 0 ? round($stats['approved'] / $stats['total'] * 100) : 0]) }}
                </span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">{{ __('Pending') }}</span>
                <span class="text-3xl font-bold text-foreground">{{ $stats['pending'] }}</span>
                <span class="text-xs text-muted-foreground">{{ __('requests to review') }}</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-destructive">{{ __('Rejected') }}</span>
                <span class="text-3xl font-bold text-destructive">{{ $stats['rejected'] }}</span>
                <span class="text-xs text-muted-foreground">{{ __('rejected requests') }}</span>
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:content>
                <span class="text-xs font-semibold uppercase tracking-wide text-primary">{{ __('Approved area') }}</span>
                <span class="text-3xl font-bold text-primary">{{ \App\LocalizedNumber::format($stats['surface'], 1) }}</span>
                <span class="text-xs text-muted-foreground">{{ __('hectares') }}</span>
            </x-slot:content>
        </april:card>
    </div>

    {{-- ===== Filter by status ===== --}}
    <div class="mb-4 flex items-center gap-2 overflow-x-auto border-b border-border pb-3">
        <span class="mr-2 shrink-0 text-xs font-semibold uppercase text-muted-foreground">{{ __('Filter by status:') }}</span>

        <april:button-link
            href="{{ route('farms.index') }}"
            size="sm"
            variant="{{ $statusFilter === null ? 'default' : 'ghost' }}"
        >
            {{ __('All farms') }}
        </april:button-link>

        @foreach(FarmStatus::cases() as $status)
            <april:button-link
                href="{{ route('farms.index', ['status' => $status->value]) }}"
                size="sm"
                variant="{{ $statusFilter === $status ? 'default' : 'ghost' }}"
                @class([
                    'font-semibold' => $statusFilter === $status,
                ])
            >
                {{ $status->label() }}
            </april:button-link>
        @endforeach
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Farms and their validation status') }}</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('Farm name') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Agricultural region') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Producer') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Soil type') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Area') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($farms as $farm)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    <div>{{ $farm->name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $farm->address }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($farm->region)
                                        <a href="{{ route('regions.show', $farm->region) }}" class="font-medium text-primary hover:underline">
                                            {{ $farm->region->name }}
                                        </a>
                                    @else
                                        <span class="text-xs text-muted-foreground">{{ __('None') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? __('Not specified')) }}</td>
                                <td class="px-6 py-4 text-muted-foreground">
                                    {{ $farm->soil_type ?? ($farm->region?->soil_type ?? __('Not specified')) }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">{{ \App\LocalizedNumber::format($farm->surface_hectares, 1) }} ha</td>
                                <td class="px-6 py-4">
                                    <x-farm-status :status="$farm->status" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Approve and reject are admin-only and
                                             only offered on an open request. --}}
                                        @if($isAdmin && $farm->isPending())
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
                                        @endif

                                        <april:button-link
                                            href="{{ route('farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="{{ __('View details for :name', ['name' => $farm->name]) }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>

                                        @if($farm->isApproved())
                                            <april:button-link
                                                href="{{ route('farms.certificate', $farm) }}"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="{{ __('Certificate for :name', ['name' => $farm->name]) }}"
                                                title="{{ __('Official Certificate (PDF)') }}"
                                            >
                                                <x-lucide-file-text class="size-4" />
                                            </april:button-link>
                                        @endif

                                        @if($farm->canBeEdited())
                                            <april:button-link
                                                href="{{ route('farms.edit', $farm) }}"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="{{ __('Edit :name', ['name' => $farm->name]) }}"
                                            >
                                                <x-lucide-pencil class="size-4" />
                                            </april:button-link>
                                        @endif

                                        @if($isAdmin || ! $farm->isApproved())
                                            <x-confirm-action
                                                :action="route('farms.destroy', $farm)"
                                                :label="__('Delete farm')"
                                                :title="__('Delete this farm?')"
                                                :description="__(':name will be permanently deleted and any certificate QR code will stop verifying. This action cannot be undone.', ['name' => $farm->name])"
                                            >
                                                <x-lucide-trash-2 class="size-4" />
                                                <span class="sr-only">{{ __('Delete :name', ['name' => $farm->name]) }}</span>
                                            </x-confirm-action>
                                        @else
                                            <april:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                disabled
                                                title="{{ __('Approved farms can only be removed by an administrator.') }}"
                                                aria-label="{{ __('Approved farms can only be removed by an administrator.') }}"
                                            >
                                                <x-lucide-trash-2 class="size-4" />
                                            </april:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                                    @unless($isAdmin)
                                        <p class="text-base font-semibold text-foreground">{{ __('You do not have any farms yet.') }}</p>
                                        <p class="mx-auto mt-1 max-w-sm text-xs text-muted-foreground">
                                            {{ __('You have not created a farm yet. Use the button below to add your first farm.') }}
                                        </p>
                                        <div class="mt-4">
                                            <april:button-link href="{{ route('farms.create') }}">
                                                <x-lucide-plus class="mr-2 size-4" />
                                                {{ __('Add a farm') }}
                                            </april:button-link>
                                        </div>
                                    @else
                                        <p class="font-medium text-foreground">{{ __('No farms recorded yet.') }}</p>
                                    @endunless
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($farms->hasPages())
            <x-slot:footer>
                {{ $farms->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
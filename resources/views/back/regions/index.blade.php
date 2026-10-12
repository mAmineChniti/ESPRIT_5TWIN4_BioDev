@extends('layouts.back')

@section('title', __('Agricultural Regions'))

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight">{{ __('Agricultural Regions') }}</h1>
                <april:badge variant="{{ $isAdmin ? 'default' : 'secondary' }}">
                    {{ $isAdmin ? __('Admin oversight') : __('Producer area') }}
                </april:badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                    {{ __("Oversight and creation of agricultural production regions.") }}
                </p>
        </div>

        {{-- Creating a region is admin-only; the route enforces it too. --}}
        @if($isAdmin)
            <april:button-link href="{{ route('regions.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                {{ __('Add a region') }}
            </april:button-link>
        @endif
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Agricultural regions') }}</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('Code') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Region name') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Climate') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Soil type') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Linked farms') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($regions as $region)
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-primary">{{ $region->code }}</td>
                                <td class="px-6 py-4 font-medium text-foreground">{{ $region->name }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $region->climate ?? __('Not specified') }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ $region->soil_type ?? __('Not specified') }}</td>
                                <td class="px-6 py-4">
                                    <april:badge variant="none" class="bg-primary/10 text-primary">
                                        {{ trans_choice('{0} :count farm|{1} :count farm|[2,*] :count farms', $region->farms_count) }}
                                    </april:badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <april:button-link
                                            href="{{ route('regions.show', $region) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="{{ __('View :name', ['name' => $region->name]) }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>

                                        @if($isAdmin)
                                            <april:button-link
                                                href="{{ route('regions.edit', $region) }}"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="{{ __('Edit :name', ['name' => $region->name]) }}"
                                            >
                                                <x-lucide-pencil class="size-4" />
                                            </april:button-link>

                                            <x-confirm-action
                                                :action="route('regions.destroy', $region)"
                                                :label="__('Delete region')"
                                                :title="__('Delete this region?')"
                                                :description="trans_choice('{0} :name and its :count farm will be permanently deleted. This action cannot be undone.|{1} :name and its :count farm will be permanently deleted. This action cannot be undone.|[2,*] :name and its :count farms will be permanently deleted. This action cannot be undone.', $region->farms_count, ['name' => $region->name, 'count' => $region->farms_count])"
                                            >
                                                <x-lucide-trash-2 class="size-4" />
                                                <span class="sr-only">{{ __('Delete :name', ['name' => $region->name]) }}</span>
                                            </x-confirm-action>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                    {{ __('No agricultural regions recorded.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($regions->hasPages())
            <x-slot:footer>
                {{ $regions->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
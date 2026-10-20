@extends('layouts.back')

@section('title', $region->name)

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $region->name }}</h1>
                    <april:badge variant="none" class="font-mono">{{ $region->code }}</april:badge>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('Agricultural region details and linked farms.') }}</p>
            </div>
            <div class="flex gap-2">
                @if($region->farms->isNotEmpty())
                    <april:button-link href="{{ route('regions.farms.export.csv', $region) }}" variant="outline">
                        <x-lucide-download class="mr-2 size-4" />
                        {{ __('Export Region Farms') }}
                    </april:button-link>
                @endif
                @if($isAdmin)
                    <april:button-link href="{{ route('regions.edit', $region) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        {{ __('Edit') }}
                    </april:button-link>
                @endif
                <april:button-link href="{{ route('regions.index') }}" variant="ghost">
                    {{ __('Back') }}
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Climate') }}</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->climate ?? __('Not specified') }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Soil type') }}</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->soil_type ?? __('Not specified') }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Linked farms') }}</span>
                    <p class="mt-1 text-lg font-bold text-primary">{{ trans_choice('{0} :count farm|{1} :count farm|[2,*] :count farms', $region->farms->count()) }}</p>
                </x-slot:content>
            </april:card>
        </div>

        @if($region->description)
            <april:card>
                <x-slot:title>{{ __('Description') }}</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $region->description }}</p>
                </x-slot:content>
            </april:card>
        @endif

        <april:card>
            <x-slot:title>{{ __('Farms in this Region') }}</x-slot:title>
            {{-- The controller scopes this to the viewer's own farms unless
                 the viewer is an admin, so a producer never sees another's. --}}
            <x-slot:content>
                <div class="mb-4">
                    <april:button-link href="{{ route('farms.create') }}?region_id={{ $region->id }}" size="sm">
                        <x-lucide-plus class="mr-2 size-4" />
                        {{ __('Add a farm') }}
                    </april:button-link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">{{ __('Farms linked to :name', ['name' => $region->name]) }}</caption>
                        <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Farm name') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Producer') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Area') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($region->farms as $farm)
                                <tr class="transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium text-foreground">{{ $farm->name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $farm->producer_name ?? __('N/A') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ \App\LocalizedNumber::format($farm->surface_hectares, 1) }} ha</td>
                                    <td class="px-4 py-3">
                                        <x-farm-status :status="$farm->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <april:button-link
                                            href="{{ route('farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="{{ __('View :name', ['name' => $farm->name]) }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        {{ __('No farms recorded in this region.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-slot:content>
        </april:card>
    </div>
@endsection
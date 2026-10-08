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
                <p class="mt-1 text-sm text-muted-foreground">Agricultural region details and linked farms.</p>
            </div>
            <div class="flex gap-2">
                @if($isAdmin)
                    <april:button-link href="{{ route('back.agricultural-regions.edit', $region) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        Edit
                    </april:button-link>
                @endif
                <april:button-link href="{{ route('back.agricultural-regions.index') }}" variant="ghost">
                    Back
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Climate</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->climate ?? 'Not specified' }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Soil type</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $region->soil_type ?? 'Not specified' }}</p>
                </x-slot:content>
            </april:card>
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Linked farms</span>
                    <p class="mt-1 text-lg font-bold text-primary">{{ $region->farms->count() }} exploitation(s)</p>
                </x-slot:content>
            </april:card>
        </div>

        @if($region->description)
            <april:card>
                <x-slot:title>Description</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $region->description }}</p>
                </x-slot:content>
            </april:card>
        @endif

        <april:card>
            <x-slot:title>Farms in this Region</x-slot:title>
            {{-- The controller scopes this to the viewer's own farms unless
                 the viewer is an admin, so a producer never sees another's. --}}
            <x-slot:content>
                <div class="mb-4">
                    <april:button-link href="{{ route('back.farms.create') }}?region_id={{ $region->id }}" size="sm">
                        <x-lucide-plus class="mr-2 size-4" />
                        Add a farm
                    </april:button-link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Farms linked to {{ $region->name }}</caption>
                        <thead class="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th scope="col" class="px-4 py-3">Farm name</th>
                                <th scope="col" class="px-4 py-3">Producer</th>
                                <th scope="col" class="px-4 py-3">Area</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($region->farms as $farm)
                                <tr class="transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium text-foreground">{{ $farm->name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $farm->producer_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ number_format((float) $farm->surface_hectares, 1) }} ha</td>
                                    <td class="px-4 py-3">
                                        <x-farm-status :status="$farm->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <april:button-link
                                            href="{{ route('back.farms.show', $farm) }}"
                                            variant="ghost"
                                            size="sm"
                                            aria-label="View {{ $farm->name }}"
                                        >
                                            <x-lucide-eye class="size-4" />
                                        </april:button-link>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        No farms recorded in this region.
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
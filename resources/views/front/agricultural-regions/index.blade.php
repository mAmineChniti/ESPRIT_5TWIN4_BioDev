@extends('layouts.front')

@section('title', 'Agricultural Regions & Farms')

@section('content')
<div class="space-y-8">
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
            Farm-to-table traceability
        </span>
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
            Explore our agricultural regions
        </h1>
        <p class="text-muted-foreground text-sm sm:text-base">
            Explore production areas and the farms behind your food for complete transparency.
        </p>
    </div>

    @if($regions->isEmpty())
        <april:card>
            <x-slot:content>
                <p class="py-10 text-center text-sm text-muted-foreground">
                    No agricultural regions are published yet.
                </p>
            </x-slot:content>
        </april:card>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        @foreach($regions as $region)
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold rounded-md bg-muted px-2 py-0.5 text-muted-foreground">
                            {{ $region->code }}
                        </span>
                        <span class="text-xs font-semibold text-primary">
                            {{ $region->approved_farms_count }} farm(s)
                        </span>
                    </div>

                    <h2 class="text-xl font-bold tracking-tight text-foreground">
                        {{ $region->name }}
                    </h2>

                    @if($region->climate)
                        <p class="text-xs text-muted-foreground">
                            <strong>Climate:</strong> {{ $region->climate }}
                        </p>
                    @endif

                    @if($region->description)
                        <p class="text-sm text-muted-foreground line-clamp-3">
                            {{ $region->description }}
                        </p>
                    @endif
                </div>

                <div class="pt-4 border-t border-border">
                    <april:button-link href="{{ route('agricultural-regions.show', $region) }}" variant="outline" class="w-full justify-between">
                        <span>View region and farms</span>
                        <x-lucide-arrow-right class="size-4" />
                    </april:button-link>
                </div>
            </div>
        @endforeach
        </div>

        @if($regions->hasPages())
            <div class="flex justify-center">
                {{ $regions->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

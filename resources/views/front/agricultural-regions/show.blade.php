@extends('layouts.front')

@section('title', $region->name)

@section('content')
<div class="space-y-8">
    <div class="space-y-3">
        <april:button-link href="{{ route('front.agricultural-regions.index') }}" variant="ghost" class="pl-0 text-muted-foreground hover:text-foreground">
            <x-lucide-arrow-left class="mr-2 size-4" /> Back to regions
        </april:button-link>

        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-extrabold tracking-tight">{{ $region->name }}</h1>
            <span class="font-mono text-xs font-bold rounded-md bg-primary/10 px-2.5 py-1 text-primary">{{ $region->code }}</span>
        </div>

        <p class="text-muted-foreground text-base max-w-3xl leading-relaxed">
            {{ $region->description }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Regional climate</span>
            <p class="text-lg font-medium text-foreground mt-1">{{ $region->climate ?? 'Not specified' }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <span class="text-xs font-semibold text-muted-foreground uppercase">Dominant soil type</span>
            <p class="text-lg font-medium text-foreground mt-1">{{ $region->soil_type ?? 'Not specified' }}</p>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-2xl font-bold tracking-tight">Farms of origin in this region</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($farms as $farm)
                <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-foreground">{{ $farm->name }}</h3>
                            <p class="text-xs text-muted-foreground mt-0.5">Operator: {{ $farm->producer_name ?? 'Local producer' }}</p>
                        </div>
                        <april:badge variant="secondary">
                            {{ $farm->farming_type }}
                        </april:badge>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ $farm->description }}
                    </p>

                    <div class="pt-3 border-t border-border flex items-center justify-between text-xs text-muted-foreground">
                        <span>📍 {{ $farm->address }}</span>
                        <span class="font-mono font-semibold">{{ number_format($farm->surface_hectares, 1) }} ha</span>
                    </div>
                </div>
            @empty
                <div class="col-span-2 rounded-xl border border-border bg-card p-8 text-center text-muted-foreground">
                    No farms recorded in this region yet.
                </div>
            @endforelse
        </div>

        @if($farms->hasPages())
            <div class="flex justify-center">
                {{ $farms->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

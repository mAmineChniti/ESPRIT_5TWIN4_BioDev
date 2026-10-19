@extends('layouts.front')

@section('title', __('Farm Traceability Verification - :name', ['name' => $farm->name]))

@section('content')
<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <april:button-link href="{{ route('agricultural-regions.index') }}" variant="ghost" class="ps-0 text-muted-foreground hover:text-foreground">
            <x-lucide-arrow-left class="me-2 size-4" /> {{ __('Back to agricultural regions') }}
        </april:button-link>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-lucide-shield-check class="size-7" />
                </div>
                <div>
                    <span class="font-mono text-xs font-bold text-primary uppercase">{{ __('NutriTrace Registry Verification') }}</span>
                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground">{{ $farm->name }}</h1>
                </div>
            </div>

            <x-farm-status :status="$farm->status" />
        </div>
    </div>

    {{-- Official Verification Banner --}}
    <div class="rounded-xl border border-primary/20 bg-primary/5 p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                <x-lucide-check class="size-6" />
            </div>
            <div>
                <h2 class="text-lg font-bold text-foreground">
                    {{ __('Authentic Registered Agricultural Holding') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground leading-relaxed">
                    {{ __('This holding is officially indexed in the NutriTrace farm-to-plate traceability registry. The data presented here matches the tamper-evident records approved by the agricultural inspection authority.') }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-mono">
                    <span class="rounded bg-background px-2.5 py-1 border border-border">
                        <strong>{{ __('Ref:') }}</strong> {{ $certificateRef }}
                    </span>
                    <span class="text-muted-foreground">
                        {{ __('Registered on:') }} {{ $farm->created_at?->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Key Specifications Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-1">
            <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Producer / Operator') }}</span>
            <p class="text-lg font-bold text-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? __('Local producer')) }}</p>
            <p class="text-xs text-muted-foreground">{{ $farm->address }}</p>
        </div>

        <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-1">
            <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Farming Practice') }}</span>
            <div class="flex items-center gap-2 pt-1">
                <april:badge variant="secondary">
                    {{ \App\Models\Farm::farmingTypeLabel($farm->farming_type) }}
                </april:badge>
                <span class="font-mono text-sm font-semibold text-foreground">{{ \App\LocalizedNumber::format($farm->surface_hectares, 1) }} ha</span>
            </div>
            <p class="text-xs text-muted-foreground">{{ __('Total certified productive surface area') }}</p>
        </div>

        <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-1">
            <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Agricultural Region') }}</span>
            @if($farm->region)
                <p class="text-lg font-bold text-primary">
                    <a href="{{ route('agricultural-regions.show', $farm->region) }}" class="hover:underline">
                        {{ $farm->region->name }}
                    </a>
                    <span class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
                </p>
                <p class="text-xs text-muted-foreground">{{ __('Climate:') }} {{ $farm->region->climate ?? __('Not specified') }}</p>
            @else
                <p class="text-lg font-medium text-muted-foreground">{{ __('Not assigned') }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-1">
            <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Soil Classification') }}</span>
            <p class="text-lg font-medium text-foreground">{{ $farm->soil_type ?? ($farm->region?->soil_type ?? __('Standard agricultural soil')) }}</p>
            <p class="text-xs text-muted-foreground">{{ __('Verified geo-pedological profile') }}</p>
        </div>
    </div>

    @if($farm->description)
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">{{ __('Operational Overview') }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-foreground">{{ $farm->description }}</p>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- Option 3 – AI Traceability Narrative (shown on QR scan page) --}}
    {{-- ============================================================ --}}
    @if(isset($story))
        <div class="rounded-xl border border-primary/25 bg-primary/5 p-6 shadow-sm">
            {{-- Section header --}}
            <div class="flex items-center gap-3 border-b border-primary/20 pb-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <x-lucide-sparkles class="size-5" />
                </div>
                <div>
                    <span class="block font-mono text-xs font-bold uppercase tracking-wider text-primary">
                        {{ __('AI Traceability Narrative') }}
                    </span>
                    <span class="block text-xs text-muted-foreground">
                        {{ __('Narrative presented to consumers when scanning the QR code') }}
                    </span>
                </div>
            </div>

            {{-- Headline --}}
            <h2 class="mt-4 text-xl font-extrabold tracking-tight text-foreground">
                {{ $story->headline }}
            </h2>

            {{-- Story body --}}
            <div class="mt-3 text-sm leading-relaxed text-muted-foreground space-y-2">
                {!! nl2br(e($story->story)) !!}
            </div>

            {{-- Quote / tagline --}}
            @if(!empty($story->tagline))
                <blockquote class="mt-4 rounded-lg border border-primary/20 bg-background/60 px-5 py-3 text-sm italic text-primary">
                    "{{ $story->tagline }}"
                </blockquote>
            @endif

            {{-- Consumer commitments grid --}}
            @if(!empty($story->commitments))
                <div class="mt-5 grid grid-cols-1 gap-3 border-t border-primary/15 pt-4 sm:grid-cols-3">
                    @foreach($story->commitments as $c)
                        <div class="rounded-lg border border-border/70 bg-background/90 p-4">
                            <span class="block text-sm font-bold text-foreground">{{ $c['title'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-muted-foreground">{{ $c['description'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>
@endsection

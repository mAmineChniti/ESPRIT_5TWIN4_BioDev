@extends('layouts.back')

@section('title', __('Details for :name', ['name' => $farm->name]))

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- The rejection reason the admin recorded, so the producer knows
             what to fix. The back layout already renders session('success'). --}}
        @if($farm->isRejected())
            <april:alert title="{{ __('Request rejected') }}">
                <x-slot:description>
                    @if($farm->rejection_reason)
                        {{ __('Reason provided by the administrator: “:reason”', ['reason' => $farm->rejection_reason]) }}
                    @else
                        {{ __('No reason was provided. Contact an administrator for more information.') }}
                    @endif
                </x-slot:description>
            </april:alert>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $farm->name }}</h1>

                    <april:badge variant="secondary">{{ \App\Models\Farm::farmingTypeLabel($farm->farming_type) }}</april:badge>

                    <x-farm-status :status="$farm->status" />
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{{ $farm->address }}</p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Approving and rejecting are admin-only and offered only
                     while the request is actually open. --}}
                @if($isAdmin && $farm->isPending())
                    <form method="POST" action="{{ route('farms.approve', $farm) }}" novalidate>
                        @csrf
                        @method('PATCH')
                        <april:button type="submit" size="sm">
                            <x-lucide-check class="mr-1 size-4" />
                            {{ __('Approve') }}
                        </april:button>
                    </form>

                    <x-farm-reject-action
                        :action="route('farms.reject', $farm)"
                        :farm-name="$farm->name"
                        :farm-id="$farm->id"
                    />
                @endif

                @if($farm->isApproved())
                    <april:button-link href="{{ route('farms.certificate', $farm) }}" variant="outline">
                        <x-lucide-file-text class="mr-2 size-4" />
                        {{ __('Official Certificate (PDF)') }}
                    </april:button-link>
                @endif

                @if($farm->canBeEdited())
                    <april:button-link href="{{ route('farms.edit', $farm) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        {{ __('Edit') }}
                    </april:button-link>
                @else
                    {{-- State why the action is unavailable instead of hiding it. --}}
                    <april:button
                        type="button"
                        variant="outline"
                        disabled
                        aria-disabled="true"
                        :title="__('This request was rejected and cannot be edited.')"
                    >
                        <x-lucide-pencil class="mr-2 size-4" />
                        {{ __('Edit') }}
                        <span class="sr-only">{{ __(': this request was rejected and cannot be edited.') }}</span>
                    </april:button>
                @endif

                <april:button-link href="{{ route('farms.index') }}" variant="ghost">
                    {{ __('Back') }}
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Agricultural region') }}</span>
                    @if($farm->region)
                        <p class="mt-1 text-lg font-bold text-primary">
                            <a href="{{ route('regions.show', $farm->region) }}" class="hover:underline">
                                {{ $farm->region->name }}
                            </a>
                        </p>
                        <span class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
                    @else
                        <p class="mt-1 text-lg font-medium text-muted-foreground">{{ __('Not assigned') }}</p>
                    @endif
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Producer') }}</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? __('Not provided')) }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Soil type') }}</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->soil_type ?? ($farm->region?->soil_type ?? __('Not provided')) }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Surface') }}</span>
                    <p class="mt-1 font-mono text-lg font-bold text-foreground">{{ \App\LocalizedNumber::format($farm->surface_hectares, 2) }} ha</p>
                </x-slot:content>
            </april:card>
        </div>

        {{-- ========================================== --}}
        {{-- AI Agro-Ecological Diagnostic & Resilience --}}
        {{-- ========================================== --}}
        @if(isset($diagnostic))
            <april:card class="border-primary/25 bg-card">
                <x-slot:title>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <x-lucide-sparkles class="size-4" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-foreground">{{ __('AI Agro-Ecological Diagnostic') }}</h2>
                                <p class="text-xs text-muted-foreground">{{ __('Environmental sustainability, soil vitality and climate resilience audit') }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <april:badge variant="{{ $diagnostic->scoreBadgeVariant() }}" class="font-mono text-xs">
                                <x-lucide-award class="mr-1 size-3.5" />
                                {{ $diagnostic->score }}/100 · {{ $diagnostic->verdict }}
                            </april:badge>

                            <form method="POST" action="{{ route('farms.diagnostic', $farm) }}">
                                @csrf
                                <april:button type="submit" variant="outline" size="sm" title="{{ __('Refresh AI analysis') }}">
                                    <x-lucide-refresh-cw class="mr-1 size-3.5" />
                                    {{ __('Re-analyze') }}
                                </april:button>
                            </form>
                        </div>
                    </div>
                </x-slot:title>

                <x-slot:content>
                    <div class="space-y-6">
                        @if($diagnostic->summary)
                            <div class="rounded-lg border border-border bg-accent/30 p-3.5 text-sm text-foreground leading-relaxed">
                                <p>{{ $diagnostic->summary }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border border-border p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-muted-foreground">{{ __('Water Stress Risk') }}</span>
                                    <x-lucide-droplets class="size-4 text-primary" />
                                </div>
                                <p class="mt-2 text-lg font-bold text-foreground">{{ __($diagnostic->waterRiskLevel) }}</p>
                                <span class="text-[11px] text-muted-foreground">{{ __('Regional climate and soil balance') }}</span>
                            </div>

                            <div class="rounded-lg border border-border p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-muted-foreground">{{ __('Soil Health') }}</span>
                                    <x-lucide-sprout class="size-4 text-primary" />
                                </div>
                                <p class="mt-2 text-lg font-bold text-foreground">{{ __($diagnostic->soilHealthLevel) }}</p>
                                <span class="text-[11px] text-muted-foreground">{{ __('Biological and organic structure') }}</span>
                            </div>

                            <div class="rounded-lg border border-border p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-muted-foreground">{{ __('Analysis Source') }}</span>
                                    <x-lucide-bot class="size-4 text-primary" />
                                </div>
                                <p class="mt-2 text-sm font-bold text-foreground">
                                    {{ $diagnostic->isAiGenerated ? __('NutriTrace AI Model') : __('Agro-Ecological Heuristics') }}
                                </p>
                                <span class="text-[11px] text-muted-foreground">
                                    {{ \Carbon\Carbon::parse($diagnostic->analyzedAt)->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-border bg-card p-4 space-y-2.5">
                                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                                    <x-lucide-check-circle-2 class="size-4" />
                                    {{ __('Agro-Ecological Strengths') }}
                                </div>
                                <ul class="space-y-2 text-xs text-muted-foreground">
                                    @forelse($diagnostic->strengths as $strength)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary"></span>
                                            <span class="leading-relaxed text-foreground">{{ $strength }}</span>
                                        </li>
                                    @empty
                                        <li class="italic">{{ __('No specific strengths recorded.') }}</li>
                                    @endforelse
                                </ul>
                            </div>

                            <div class="rounded-lg border border-border bg-card p-4 space-y-2.5">
                                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    <x-lucide-alert-triangle class="size-4 text-destructive" />
                                    {{ __('Vulnerabilities & Risk Points') }}
                                </div>
                                <ul class="space-y-2 text-xs text-muted-foreground">
                                    @forelse($diagnostic->vulnerabilities as $vuln)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-destructive"></span>
                                            <span class="leading-relaxed text-foreground">{{ $vuln }}</span>
                                        </li>
                                    @empty
                                        <li class="italic">{{ __('No acute vulnerability detected.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4 space-y-3">
                            <div class="flex items-center gap-2">
                                <x-lucide-lightbulb class="size-4 text-primary" />
                                <h3 class="text-sm font-bold text-foreground">{{ __('AI Recommendations for Farm Optimization') }}</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-2.5">
                                @foreach($diagnostic->recommendations as $rec)
                                    <div class="flex items-start gap-2.5 text-xs">
                                        <div class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/20 text-[10px] font-bold text-primary">
                                            {{ $loop->iteration }}
                                        </div>
                                        <p class="leading-relaxed text-foreground">{{ $rec }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-slot:content>
            </april:card>
        @endif

        {{-- =============================================== --}}
        {{-- AI Farm-to-Table Consumer Storytelling Preview  --}}
        {{-- =============================================== --}}
        @if(isset($story))
            <april:card class="border-border bg-card">
                <x-slot:title>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <x-lucide-book-open class="size-4" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-foreground">{{ __('Consumer Storytelling & Terroir') }}</h2>
                                <p class="text-xs text-muted-foreground">{{ __('Narrative presented to consumers when scanning the QR code') }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <april:badge variant="outline" class="text-xs">
                                <x-lucide-bot class="mr-1 size-3.5 text-primary" />
                                {{ $story->isAiGenerated ? __('AI Generated') : __('Heuristic Terroir') }}
                            </april:badge>

                            <form method="POST" action="{{ route('farms.story', $farm) }}">
                                @csrf
                                <april:button type="submit" variant="outline" size="sm" title="{{ __('Regenerate storytelling') }}">
                                    <x-lucide-sparkles class="mr-1 size-3.5" />
                                    {{ __('Regenerate story') }}
                                </april:button>
                            </form>
                        </div>
                    </div>
                </x-slot:title>

                <x-slot:content>
                    <div class="space-y-4">
                        <div class="border-b border-border pb-2">
                            <h3 class="text-sm font-bold text-primary">{{ $story->headline }}</h3>
                        </div>

                        <div class="text-xs leading-relaxed text-muted-foreground space-y-2">
                            {!! nl2br(e($story->story)) !!}
                        </div>

                        @if($story->terroirHighlight)
                            <p class="rounded-lg border border-primary/20 bg-primary/5 p-3 text-xs italic text-primary">
                                “{{ $story->terroirHighlight }}”
                            </p>
                        @endif

                        @if(!empty($story->commitments))
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 pt-1">
                                @foreach($story->commitments as $c)
                                    <div class="rounded-lg border border-border bg-accent/20 p-2.5 space-y-0.5">
                                        <span class="block text-xs font-bold text-foreground">{{ $c['title'] ?? '' }}</span>
                                        <p class="text-[11px] text-muted-foreground">{{ $c['description'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-slot:content>
            </april:card>
        @endif

        <april:card>
            <x-slot:title>{{ __('Contact and creation information') }}</x-slot:title>
            <x-slot:content>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div>
                        <span class="block text-xs text-muted-foreground">{{ __('Phone:') }}</span>
                        <span class="font-medium">{{ $farm->phone ?? __('Not provided') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">{{ __('Full address:') }}</span>
                        <span class="font-medium">{{ $farm->address }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">{{ __('Created:') }}</span>
                        <span class="font-mono text-xs font-medium">{{ $farm->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                </div>
            </x-slot:content>
        </april:card>

        @if($farm->description)
            <april:card>
                <x-slot:title>{{ __('Description and features') }}</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $farm->description }}</p>
                </x-slot:content>
            </april:card>
        @endif
    </div>
@endsection
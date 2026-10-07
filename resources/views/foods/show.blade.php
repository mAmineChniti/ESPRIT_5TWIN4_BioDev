@extends('layouts.back')

@section('title', 'Food Details')

@section('content')
    <div class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4">
                <april:button-link href="{{ route('foods.index') }}" variant="ghost" class="pl-0 text-muted-foreground hover:text-foreground">
                    <x-lucide-arrow-left class="mr-2 size-4" />
                    Back
                </april:button-link>
                <h1 class="text-2xl font-bold">Product details</h1>
            </div>

            {{-- Both actions gated by the same policy the controller runs, so
                 a consumer is never shown controls that would 403. --}}
            <div class="flex items-center gap-2">
                @can('update', $food)
                    <april:button-link href="{{ route('foods.edit', $food) }}" variant="outline">
                        Edit
                    </april:button-link>
                @endcan

                @can('delete', $food)
                    <x-confirm-action
                        :action="route('foods.destroy', $food)"
                        label="Delete product"
                        title="Delete this product?"
                        description="This removes {{ $food->name }} and its entire recorded supply chain. It cannot be undone."
                    >
                        Delete<span class="sr-only"> {{ $food->name }}</span>
                    </x-confirm-action>
                @endcan
            </div>
        </div>
    </div>

    <april:card class="max-w-3xl">
        <x-slot:title>Traceability information</x-slot:title>
        <x-slot:description>From farm to plate.</x-slot:description>

        <x-slot:content>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-muted-foreground">Nom du produit</dt>
                    <dd class="mt-1 text-sm text-foreground">{{ $food->name }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-muted-foreground">Category</dt>
                    <dd class="mt-1 text-sm text-foreground">{{ $food->category->name ?? 'None' }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-muted-foreground">Origin</dt>
                    <dd class="mt-1 text-sm text-foreground">{{ $food->origin ?? 'Unknown' }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-muted-foreground">Score Environnemental</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        <x-eco-score :score="$food->environmental_score" />
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-muted-foreground">Producteur</dt>
                    <dd class="mt-1 text-sm text-foreground">{{ $food->producer?->name ?? 'Unattributed' }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-muted-foreground">Certifications</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        @forelse($food->certifications as $certification)
                            <div class="flex flex-wrap items-center gap-2 py-1">
                                <span class="font-medium">{{ $certification->name }}</span>
                                <span class="text-xs text-muted-foreground">{{ $certification->issuer }}</span>
                                @if($certification->certificate_number)
                                    <span class="text-xs text-muted-foreground">No. {{ $certification->certificate_number }}</span>
                                @endif
                                @if($certification->valid_until)
                                    <span @class(['text-xs', 'text-destructive' => $certification->isExpired(), 'text-muted-foreground' => ! $certification->isExpired()])>
                                        {{ $certification->isExpired() ? 'Expired' : 'Valid until' }} {{ $certification->valid_until->format('Y-m-d') }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <span class="text-muted-foreground">No certification recorded</span>
                        @endforelse
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-muted-foreground">Supply chain</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        @forelse($food->transitions as $transition)
                            <div class="flex items-center gap-3 py-1">
                                <span class="font-medium">{{ $transition->to_stage?->label() }}</span>
                                @if($transition->from_stage)
                                    <span class="text-xs text-muted-foreground">from {{ $transition->from_stage->label() }}</span>
                                @endif
                                <span class="text-xs text-muted-foreground">
                                    {{ $transition->occurred_at->format('Y-m-d') }}
                                    @if($transition->actor) — {{ $transition->actor->name }} @endif
                                </span>
                            </div>
                        @empty
                            <span class="text-muted-foreground">No supply chain steps recorded yet</span>
                        @endforelse
                    </dd>
                </div>
            </dl>

            <h3 class="mb-4 text-lg font-medium leading-6 text-foreground">Nutritional values (per 100g)</h3>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div class="rounded-lg bg-muted p-4">
                    <span class="block text-2xl font-bold text-primary">{{ $food->calories }}</span>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Calories</span>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <span class="block text-2xl font-bold text-primary">{{ $food->protein }}g</span>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Protein</span>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <span class="block text-2xl font-bold text-secondary-foreground">{{ $food->carbs }}g</span>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Carbs</span>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <span class="block text-2xl font-bold text-destructive">{{ $food->fat }}g</span>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Fat</span>
                </div>
            </div>
        </x-slot:content>
    </april:card>
@endsection
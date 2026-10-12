@extends('layouts.back')

@section('title', __('Food Details'))

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            {{ __('Back') }}
        </april:button-link>
        <h1 class="text-2xl font-bold">{{ __('Product details') }}</h1>
    </div>

    {{-- The route is readable by any signed in user, so the actions are
         gated on the policy rather than assumed. --}}
    <div class="flex items-center gap-2">
        <april:button-link href="{{ route('products.show', $food) }}" variant="outline">
            {{ __('Public page') }}
        </april:button-link>
        <april:button-link href="{{ route('foods.transitions.index', $food) }}" variant="outline">
            <x-lucide-route class="size-4" />
            {{ __('Trace record') }}
        </april:button-link>
        @can('update', $food)
            <april:button-link href="{{ route('foods.edit', $food) }}" variant="outline">{{ __('Edit') }}</april:button-link>
        @endcan
        @can('delete', $food)
            <april:alert-dialog>
                <x-slot:trigger>
                    <april:button type="button" variant="destructive">{{ __('Delete') }}</april:button>
                </x-slot:trigger>
                <x-slot:content>
                    <div>
                        <h2 class="text-lg font-semibold" x-bind="title">{{ __('Delete this product?') }}</h2>
                        <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                            {!! __(':name and its recorded supply chain will be removed. This cannot be undone.', ['name' => '<strong>'.e($food->name).'</strong>']) !!}
                        </p>
                    </div>
                    <april:alert-dialog-footer>
                        <april:alert-dialog-cancel>{{ __('Cancel') }}</april:alert-dialog-cancel>
                        <form action="{{ route('foods.destroy', $food) }}" method="POST" novalidate>
                            @csrf
                            @method('DELETE')
                            <april:button type="submit" variant="destructive" x-bind="action">
                                {{ __('Delete product') }}
                            </april:button>
                        </form>
                    </april:alert-dialog-footer>
                </x-slot:content>
            </april:alert-dialog>
        @endcan
    </div>
</div>

<april:card class="max-w-3xl">
    <x-slot:title class="text-lg">{{ __('Traceability information') }}</x-slot:title>
    <x-slot:description>{{ __('From farm to plate.') }}</x-slot:description>
    <x-slot:content>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Product name') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->name }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Category') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->category->name ?? __('None') }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Origin') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->origin ?? __('Unknown') }}</dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Environmental score') }}</dt>
                <dd class="mt-1 text-sm text-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Producer') }}</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $food->producer?->name ?? __('Unattributed') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Certifications') }}</dt>
                <dd class="mt-1 text-sm text-foreground">
                    @forelse($food->certifications as $certification)
                        <div class="flex flex-wrap items-center gap-2 py-1">
                            <span class="font-medium">{{ $certification->name }}</span>
                            <span class="text-muted-foreground text-xs">{{ $certification->issuer }}</span>
                            @if($certification->certificate_number)
                                <span class="text-muted-foreground text-xs">{{ __('No. :number', ['number' => $certification->certificate_number]) }}</span>
                            @endif
                            @if($certification->valid_until)
                                <span class="text-xs {{ $certification->isExpired() ? 'text-destructive' : 'text-muted-foreground' }}">
                                    {{ $certification->isExpired() ? __('Expired') : __('Valid until') }} {{ $certification->valid_until->format('Y-m-d') }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted-foreground">{{ __('No certification recorded') }}</span>
                    @endforelse
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-muted-foreground">{{ __('Supply chain') }}</dt>
                <dd class="mt-1 text-sm text-foreground">
                    @php $current = null; @endphp
                    @forelse($food->transitions as $transition)
                        <div class="flex items-center gap-3 py-1">
                            <span class="font-medium">{{ $transition->to_stage?->label() }}</span>
                            @if($transition->from_stage)
                                <span class="text-muted-foreground text-xs">{{ __('from :label', ['label' => $transition->from_stage->label()]) }}</span>
                            @endif
                            <span class="text-muted-foreground text-xs">
                                {{ $transition->occurred_at->format('Y-m-d') }}
                                @if($transition->actor) — {{ $transition->actor->name }} @endif
                            </span>
                        </div>
                    @empty
                        <span class="text-muted-foreground">{{ __('No supply chain steps recorded yet') }}</span>
                    @endforelse
                </dd>
            </div>
        </dl>

        <april:separator />

        <div class="py-5">
            <x-origin-map :food="$food" />
        </div>

    <april:separator />

    <div class="pt-5">
        <h3 class="mb-4 text-base font-medium leading-6 text-foreground">{{ __('Nutritional values (per 100g)') }}</h3>
        <div class="grid grid-cols-4 text-center gap-4">
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->calories }}</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">{{ __('Calories') }}</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->protein }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">{{ __('Protein') }}</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-secondary-foreground">{{ $food->carbs }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">{{ __('Carbs') }}</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                {{-- Fat is a nutrient, not a fault: it uses the same primary
                     token as the other three rather than destructive. --}}
                <span class="block text-2xl font-bold text-primary">{{ $food->fat }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">{{ __('Fat') }}</span>
            </div>
        </div>
    </div>
    </x-slot:content>
</april:card>
@endsection

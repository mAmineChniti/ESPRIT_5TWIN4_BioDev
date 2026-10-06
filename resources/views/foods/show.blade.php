@extends('layouts.back')

@section('title', 'Food Details')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('foods.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
            <h1 class="text-2xl font-bold">Product details</h1>
        </div>
        <div class="space-x-2">
            <a href="{{ route('foods.edit', $food) }}" class="bg-card border border-input text-foreground hover:bg-muted font-medium py-2 px-4 rounded-md">
                Edit
            </a>
            <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-destructive hover:bg-destructive/90 text-destructive-foreground font-medium py-2 px-4 rounded-md">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden max-w-3xl">
    <div class="px-6 py-5 border-b border-border">
        <h3 class="text-lg font-medium leading-6 text-foreground">Traceability information</h3>
        <p class="mt-1 max-w-2xl text-sm text-muted-foreground">From farm to plate.</p>
    </div>
    <div class="px-6 py-5">
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
                            <span class="text-muted-foreground text-xs">{{ $certification->issuer }}</span>
                            @if($certification->certificate_number)
                                <span class="text-muted-foreground text-xs">No. {{ $certification->certificate_number }}</span>
                            @endif
                            @if($certification->valid_until)
                                <span class="text-xs {{ $certification->isExpired() ? 'text-destructive' : 'text-muted-foreground' }}">
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
                    @php $current = null; @endphp
                    @forelse($food->transitions as $transition)
                        <div class="flex items-center gap-3 py-1">
                            <span class="font-medium">{{ $transition->to_stage?->label() }}</span>
                            @if($transition->from_stage)
                                <span class="text-muted-foreground text-xs">from {{ $transition->from_stage->label() }}</span>
                            @endif
                            <span class="text-muted-foreground text-xs">
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
    </div>

    <div class="px-6 py-5 border-t border-border">
        <h3 class="text-md font-medium leading-6 text-foreground mb-4">Nutritional values (per 100g)</h3>
        <div class="grid grid-cols-4 text-center gap-4">
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->calories }}</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Calories</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-primary">{{ $food->protein }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Protein</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-secondary-foreground">{{ $food->carbs }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Carbs</span>
            </div>
            <div class="bg-muted p-4 rounded-lg">
                <span class="block text-2xl font-bold text-destructive">{{ $food->fat }}g</span>
                <span class="text-xs text-muted-foreground uppercase font-semibold">Fat</span>
            </div>
        </div>
    </div>
</div>
@endsection

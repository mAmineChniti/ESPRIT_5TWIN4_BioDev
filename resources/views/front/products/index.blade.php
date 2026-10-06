@extends('layouts.front')

@section('title', 'Find a product')

@section('content')
{{-- Scan / search entry point --}}
<section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-end">
        <form action="{{ route('products.scan') }}" method="GET" class="flex-1">
            <label for="code" class="block text-sm font-medium text-foreground">Scan or type a product code</label>
            <div class="mt-2 flex gap-2">
                <input type="text" id="code" name="code" value="{{ request('code') }}" autofocus
                       placeholder="e.g. 7 or “organic apple”"
                       class="w-full rounded-lg border border-input bg-background px-4 py-2.5 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                <button type="submit"
                        class="shrink-0 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90">
                    Look up
                </button>
            </div>
            @error('code')
                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
            @enderror
        </form>

        <form action="{{ route('products.index') }}" method="GET" class="flex-1">
            <label for="q" class="block text-sm font-medium text-foreground">Search the catalog</label>
            <div class="mt-2 flex gap-2">
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Name, origin, producer or certification"
                       class="w-full rounded-lg border border-input bg-background px-4 py-2.5 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                <button type="submit"
                        class="shrink-0 rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                    Search
                </button>
            </div>
        </form>
    </div>

    <p class="mt-4 text-xs text-muted-foreground">
        Every product page shows who registered it, which certifications are actually on file, and the full
        recorded journey. If something does not add up,
        <a href="{{ route('greenwashing') }}" class="text-primary underline underline-offset-2">learn how to spot it</a>.
    </p>
</section>

{{-- Filters --}}
<form action="{{ route('products.index') }}" method="GET"
      class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-card p-4">
    @if($filters['q'] ?? null)
        <input type="hidden" name="q" value="{{ $filters['q'] }}">
    @endif

    <div>
        <label for="category" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Category</label>
        <select id="category" name="category"
                class="mt-1 rounded-lg border border-input bg-background px-3 py-2 text-sm">
            <option value="">All</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category'] ?? null) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="grade" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Eco grade</label>
        <select id="grade" name="grade"
                class="mt-1 rounded-lg border border-input bg-background px-3 py-2 text-sm">
            <option value="">Any</option>
            @foreach($grades as $grade)
                <option value="{{ $grade->value }}" @selected(($filters['grade'] ?? null) === $grade->value)>
                    {{ $grade->value }} — {{ $grade->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="certified" value="1" @checked(request()->boolean('certified'))
               class="rounded border-input">
        Certified only
    </label>

    <div class="ml-auto">
        <label for="sort" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Sort</label>
        <select id="sort" name="sort" class="mt-1 rounded-lg border border-input bg-background px-3 py-2 text-sm">
            <option value="recent" @selected($sort === 'recent')>Most recent</option>
            <option value="scanned" @selected($sort === 'scanned')>Most scanned</option>
            <option value="grade" @selected($sort === 'grade')>Eco grade</option>
            <option value="rated" @selected($sort === 'rated')>Best rated</option>
        </select>
    </div>

    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        Apply
    </button>
</form>

{{-- Results --}}
<p class="mt-6 text-sm text-muted-foreground">
    {{ $foods->total() }} {{ Str::plural('product', $foods->total()) }} found
    @if($filters['q'] ?? null) for “{{ $filters['q'] }}” @endif
</p>

@if($foods->isEmpty())
    <div class="mt-4 rounded-xl border border-dashed border-border p-10 text-center">
        <p class="text-sm font-medium">Nothing matched.</p>
        <p class="mt-1 text-sm text-muted-foreground">Try a shorter search term, or clear the filters.</p>
        <a href="{{ route('products.index') }}" class="mt-4 inline-block text-sm text-primary underline">Reset filters</a>
    </div>
@else
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($foods as $food)
            @php
                $verdict = $food->trustVerdict();
                $rating = $food->averageRating();
            @endphp
            <a href="{{ route('products.show', $food) }}"
               class="group flex flex-col rounded-xl border border-border bg-card p-4 shadow-sm transition hover:border-primary/50 hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold text-foreground group-hover:text-primary">{{ $food->name }}</h2>
                    <x-eco-score :score="$food->environmental_score" />
                </div>

                <p class="mt-1 text-xs text-muted-foreground">
                    {{ $food->category->name ?? 'Uncategorised' }}
                    @if($food->origin) · {{ $food->origin }} @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach($food->certifications as $certification)
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                            {{ $certification->name }}
                        </span>
                    @endforeach
                    @if($food->certifications->isEmpty())
                        <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                            No certification on file
                        </span>
                    @endif
                </div>

                <div class="mt-auto pt-4 flex items-center justify-between text-xs">
                    <span class="inline-flex items-center gap-1
                        {{ $verdict['tone'] === 'high' ? 'text-destructive' : ($verdict['tone'] === 'low' ? 'text-primary' : 'text-secondary-foreground') }}">
                        <x-lucide-shield-check class="size-3.5" />
                        {{ $verdict['level'] }}
                    </span>

                    <span class="text-muted-foreground">
                        @if($rating !== null)
                            <span class="inline-flex items-center gap-0.5 text-foreground">
                                <x-lucide-star class="size-3.5 fill-current" /> {{ $rating }}
                            </span>
                            <span class="ml-2">({{ $food->reviews_count }})</span>
                        @else
                            No reviews yet
                        @endif
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-8">{{ $foods->links() }}</div>
@endif
@endsection
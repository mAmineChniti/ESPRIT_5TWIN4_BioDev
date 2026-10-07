@extends('layouts.front')

@section('title', 'Find a product')

@section('content')
{{-- Scan / search entry point --}}
<april:card>
    <x-slot:content>
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <form action="{{ route('products.scan') }}" method="GET" class="flex-1">
                <april:label for="code">Scan or type a product code</april:label>
                <div class="mt-2 flex gap-2">
                    <april:input id="code" name="code" :value="request('code')"
                                 placeholder="e.g. 7 or “organic apple”"
                                 aria-describedby="code-error" />
                    <april:button type="submit">Look up</april:button>
                </div>
                <p id="code-error" class="mt-2 text-sm text-destructive" role="alert" aria-live="polite">
                    @error('code') {{ $message }} @enderror
                </p>
            </form>

            <form action="{{ route('products.index') }}" method="GET" class="flex-1">
                <april:label for="q">Search the catalog</april:label>
                <div class="mt-2 flex gap-2">
                    <april:input id="q" name="q" type="search" :value="$filters['q'] ?? ''"
                                 placeholder="Name, origin, producer or certification" />
                    <april:button type="submit" variant="outline">Search</april:button>
                </div>
            </form>
        </div>

        <p class="mt-4 text-xs text-muted-foreground">
            Every product page shows who registered it, which certifications are actually on file, and the full
            recorded journey. If something does not add up,
            <a href="{{ route('greenwashing') }}" class="text-primary underline underline-offset-2">learn how to spot it</a>.
        </p>
    </x-slot:content>
</april:card>

{{-- Filters --}}
<form action="{{ route('products.index') }}" method="GET"
      class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-card p-4">
    @if($filters['q'] ?? null)
        <input type="hidden" name="q" value="{{ $filters['q'] }}">
    @endif

    <div>
        <april:label for="category" class="text-xs uppercase tracking-wide text-muted-foreground">Category</april:label>
        <april:native-select id="category" name="category" class="mt-1">
            <option value="">All</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category'] ?? null) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </april:native-select>
    </div>

    <div>
        <april:label for="grade" class="text-xs uppercase tracking-wide text-muted-foreground">Eco grade</april:label>
        <april:native-select id="grade" name="grade" class="mt-1">
            <option value="">Any</option>
            @foreach($grades as $grade)
                <option value="{{ $grade->value }}" @selected(($filters['grade'] ?? null) === $grade->value)>
                    {{ $grade->value }} — {{ $grade->label() }}
                </option>
            @endforeach
        </april:native-select>
    </div>

    <div class="flex items-center gap-2 pb-2.5 text-sm">
        <april:checkbox id="certified" name="certified" value="1" :checked="request()->boolean('certified')" />
        <april:label for="certified">Certified only</april:label>
    </div>

    <div class="ml-auto">
        <april:label for="sort" class="text-xs uppercase tracking-wide text-muted-foreground">Sort</april:label>
        <april:native-select id="sort" name="sort" class="mt-1">
            <option value="recent" @selected($sort === 'recent')>Most recent</option>
            <option value="scanned" @selected($sort === 'scanned')>Most scanned</option>
            <option value="grade" @selected($sort === 'grade')>Eco grade</option>
            <option value="rated" @selected($sort === 'rated')>Best rated</option>
        </april:native-select>
    </div>

    <april:button type="submit">Apply</april:button>
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
        <april:button-link href="{{ route('products.index') }}" variant="link" size="sm" class="mt-4">
            Reset filters
        </april:button-link>
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
                        <april:badge variant="none" class="bg-primary/10 text-primary">
                            {{ $certification->name }}
                        </april:badge>
                    @endforeach
                    @if($food->certifications->isEmpty())
                        <april:badge variant="none" class="bg-muted text-muted-foreground">
                            No certification on file
                        </april:badge>
                    @endif
                </div>

                <div class="mt-auto pt-4 flex items-center justify-between text-xs">
                    <span class="inline-flex items-center gap-1 {{ $verdict['tone']->textClasses() }}">
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
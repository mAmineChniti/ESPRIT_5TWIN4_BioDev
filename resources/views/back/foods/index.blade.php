@extends('layouts.back')

@section('title', __('Food List'))

@section('content')
@if(session('success'))
    <april:alert class="mb-4" aria-live="polite">
        <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
        <x-slot:description>{{ session('success') }}</x-slot:description>
    </april:alert>
@endif

@if(session('error'))
    <april:alert variant="destructive" class="mb-4" aria-live="polite">
        <x-slot:icon><x-lucide-circle-alert class="size-4" /></x-slot:icon>
        <x-slot:description>{{ session('error') }}</x-slot:description>
    </april:alert>
@endif

@if($errors->any())
    <april:alert variant="destructive" class="mb-4" aria-live="polite">
        <x-slot:icon><x-lucide-circle-alert class="size-4" /></x-slot:icon>
        <x-slot:description>{{ $errors->first() }}</x-slot:description>
    </april:alert>
@endif

{{-- ─── Header row ──────────────────────────────────────────────────────── --}}
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">{{ __('Products') }}</h1>
    <div class="flex flex-wrap items-center gap-2">
        {{-- Compare button (Option 4) --}}
        <april:button-link href="{{ route('foods.compare') }}" variant="outline">
            <x-lucide-git-compare class="size-4" />
            {{ __('Compare products') }}
        </april:button-link>

        @can('create', App\Models\Food::class)
            {{-- CSV import --}}
            <form action="{{ route('foods.import') }}" method="POST" enctype="multipart/form-data"
                  class="flex flex-wrap items-center gap-2" novalidate>
                @csrf
                <april:label for="csv_file" class="sr-only">{{ __('CSV file to import') }}</april:label>
                <april:input id="csv_file" name="csv_file" type="file" accept=".csv,.txt"
                             class="w-auto" aria-describedby="csv_file-hint csv_file-error"
                             @if($errors->has('csv_file')) aria-invalid="true" @endif />
                <april:button type="submit" variant="outline">
                    <x-lucide-upload class="size-4" />
                    {{ __('Import CSV') }}
                </april:button>
                <span id="csv_file-hint" class="text-xs text-muted-foreground">{{ __('CSV or TXT, up to 2 MB') }}</span>
                @error('csv_file') <span id="csv_file-error" role="alert" class="text-xs text-destructive">{{ $message }}</span> @enderror
            </form>

            <april:button-link href="{{ route('foods.create') }}">
                <x-lucide-plus class="size-4" />
                {{ __('Add a product') }}
            </april:button-link>
        @endcan
    </div>
</div>

{{-- ─── Option 1 : Advanced search & filters ────────────────────────────── --}}
<form method="GET" action="{{ route('foods.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-4">
    <div class="flex-1 min-w-48 space-y-1">
        <label for="search" class="text-xs font-medium text-muted-foreground">{{ __('Search by name') }}</label>
        <div class="relative">
            <x-lucide-search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <april:input id="search" name="search" :value="request('search')"
                         placeholder="{{ __('Tomate, Harissa…') }}"
                         class="pl-9" />
        </div>
    </div>

    <div class="min-w-36 space-y-1">
        <label for="filter_category" class="text-xs font-medium text-muted-foreground">{{ __('Category') }}</label>
        <april:native-select id="filter_category" name="category">
            <option value="">{{ __('All categories') }}</option>
            @foreach(\App\Models\Category::orderBy('name')->get() as $cat)
                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </april:native-select>
    </div>

    <div class="min-w-36 space-y-1">
        <label for="filter_origin" class="text-xs font-medium text-muted-foreground">{{ __('Origin') }}</label>
        <april:input id="filter_origin" name="origin" :value="request('origin')"
                     placeholder="{{ __('Tunisie, France…') }}" />
    </div>

    <div class="flex gap-2">
        <april:button type="submit">
            <x-lucide-filter class="size-4" />
            {{ __('Filter') }}
        </april:button>
        @if(request()->hasAny(['search','category','origin']))
            <april:button-link href="{{ route('foods.index') }}" variant="outline">
                <x-lucide-x class="size-4" />
                {{ __('Clear') }}
            </april:button-link>
        @endif
    </div>
</form>

{{-- ─── Product table ────────────────────────────────────────────────────── --}}
<april:data-table>
    <x-slot:header>
        <tr>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">{{ __('Product') }}</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">{{ __('Category') }}</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">{{ __('Origin') }}</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">{{ __('Eco score') }}</th>
            <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">{{ __('Actions') }}</th>
        </tr>
    </x-slot:header>

    <x-slot:body>
        @forelse($foods as $food)
            <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                <td class="whitespace-nowrap p-4 align-middle text-sm font-medium text-foreground">{{ $food->name }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    {{ $food->category->name ?? __('Not set') }}
                </td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">{{ $food->origin ?? '—' }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </td>
                <td class="whitespace-nowrap p-4 text-right align-middle">
                    <div class="inline-flex items-center gap-2">
                        <april:button-link href="{{ route('foods.show', $food) }}" variant="link" size="sm">
                            {{ __('View') }}
                        </april:button-link>
                        @can('update', $food)
                            <april:button-link href="{{ route('foods.edit', $food) }}" variant="link" size="sm">
                                {{ __('Edit') }}
                            </april:button-link>
                        @endcan

                        @can('delete', $food)
                                <april:alert-dialog>
                                    <x-slot:trigger>
                                        <april:button type="button" variant="link" size="sm"
                                                      class="text-destructive">
                                            {{ __('Delete') }}
                                        </april:button>
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
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-4 text-center align-middle text-sm text-muted-foreground">
                    {{ __('No products found.') }}
                </td>
            </tr>
        @endforelse
    </x-slot:body>
</april:data-table>

@if($foods->hasPages())
    <div class="mt-6">{{ $foods->links() }}</div>
@endif
@endsection
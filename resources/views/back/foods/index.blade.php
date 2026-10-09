@extends('layouts.back')

@section('title', 'Food List')

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

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Products</h1>
    @can('create', App\Models\Food::class)
        {{-- One row, one baseline. The file input, the import button and the add
             button are all h-10 (the button's default size, so it was left off),
             and the hint sits inline rather than stacked under the input, which
             is what used to push the import button below the others. --}}
        <div class="flex flex-wrap items-center gap-3">
            {{-- The file input stays visible rather than hidden or sr-only:
                 a hidden input is out of the tab order, and sr-only competes
                 with the component's own w-full for the cascade. An explicit
                 submit also means a bulk import is never triggered by merely
                 picking a file. --}}
            <form action="{{ route('foods.import') }}" method="POST" enctype="multipart/form-data"
                  class="flex flex-wrap items-center gap-2" novalidate>
                @csrf
                <april:label for="csv_file" class="sr-only">CSV file to import</april:label>
                <april:input id="csv_file" name="csv_file" type="file" accept=".csv,.txt"
                             class="w-auto" aria-describedby="csv_file-hint csv_file-error"
                             @if($errors->has('csv_file')) aria-invalid="true" @endif />
                <april:button type="submit" variant="outline">
                    <x-lucide-upload class="size-4" />
                    Import CSV
                </april:button>
                <span id="csv_file-hint" class="text-xs text-muted-foreground">CSV or TXT, up to 2 MB</span>
                @error('csv_file') <span id="csv_file-error" role="alert" class="text-xs text-destructive">{{ $message }}</span> @enderror
            </form>
            <april:button-link href="{{ route('foods.create') }}">
                <x-lucide-plus class="size-4" />
                Add a product
            </april:button-link>
        </div>
    @endcan
</div>

<april:data-table>
    <x-slot:header>
        <tr>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Product</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Origin</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Eco score</th>
            <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
        </tr>
    </x-slot:header>

    <x-slot:body>
        @forelse($foods as $food)
            <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                <td class="whitespace-nowrap p-4 align-middle text-sm font-medium text-foreground">{{ $food->name }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    {{ $food->category->name ?? 'Not set' }}
                </td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">{{ $food->origin ?? '—' }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    <x-eco-score :score="$food->environmental_score" />
                </td>
                <td class="whitespace-nowrap p-4 text-right align-middle">
                    <div class="inline-flex items-center gap-2">
                        <april:button-link href="{{ route('foods.show', $food) }}" variant="link" size="sm">
                            View
                        </april:button-link>
                        @can('update', $food)
                            <april:button-link href="{{ route('foods.edit', $food) }}" variant="link" size="sm">
                                Edit
                            </april:button-link>
                        @endcan

                        @can('delete', $food)
                                <april:alert-dialog>
                                    <x-slot:trigger>
                                        <april:button type="button" variant="link" size="sm"
                                                      class="text-destructive">
                                            Delete
                                        </april:button>
                                    </x-slot:trigger>
                                    <x-slot:content>
                                        <div>
                                            <h2 class="text-lg font-semibold" x-bind="title">Delete this product?</h2>
                                            <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                                                <strong>{{ $food->name }}</strong> and its recorded supply chain
                                                will be removed. This cannot be undone.
                                            </p>
                                        </div>
                                        <april:alert-dialog-footer>
                                            <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                                            <form action="{{ route('foods.destroy', $food) }}" method="POST" novalidate>
                                                @csrf
                                                @method('DELETE')
                                                <april:button type="submit" variant="destructive" x-bind="action">
                                                    Delete product
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
                    No products found.
                </td>
            </tr>
        @endforelse
    </x-slot:body>
</april:data-table>

@if($foods->hasPages())
    <div class="mt-6">{{ $foods->links() }}</div>
@endif
@endsection
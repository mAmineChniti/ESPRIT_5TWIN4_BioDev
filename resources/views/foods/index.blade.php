@extends('layouts.back')

@section('title', 'Food List')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Products</h1>
        {{-- Guarded by the same rule the controller enforces, so a consumer or
             an admin is never offered a control that would 403. --}}
        @can('create', App\Models\Food::class)
            <april:button-link href="{{ route('foods.create') }}">
                <x-lucide-plus class="mr-2 size-4" />
                Add a product
            </april:button-link>
        @endcan
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <caption class="sr-only">Registered products</caption>
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Nom</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Origin</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Eco score</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($foods as $food)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ $food->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ $food->category->name ?? 'Not set' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ $food->origin ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">
                                    <x-eco-score :score="$food->environmental_score" />
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <april:button-link href="{{ route('foods.show', $food) }}" variant="ghost" size="sm">
                                            View
                                        </april:button-link>

                                        @can('update', $food)
                                            <april:button-link href="{{ route('foods.edit', $food) }}" variant="ghost" size="sm">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="whitespace-nowrap px-6 py-12 text-center text-sm text-muted-foreground">
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>
    </april:card>
@endsection
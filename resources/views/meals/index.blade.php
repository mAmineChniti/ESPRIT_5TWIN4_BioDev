@extends('layouts.back')

@section('title', 'My Meals')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold">My Meals</h1>
    <april:button-link href="{{ route('meals.create') }}">
        <x-lucide-plus class="size-4" />
        Log a meal
    </april:button-link>
</div>

<april:data-table>
    <x-slot:header>
        <tr>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Meal</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Products</th>
            <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
        </tr>
    </x-slot:header>

    <x-slot:body>
        @forelse($meals as $meal)
            <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                <td class="whitespace-nowrap p-4 align-middle text-sm font-medium text-foreground">{{ $meal->name }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">{{ ucfirst($meal->type) }}</td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    {{ $meal->consumed_on?->format('Y-m-d') }}
                </td>
                <td class="p-4 align-middle text-sm text-muted-foreground">
                    {{ $meal->foods->pluck('name')->join(', ') ?: '—' }}
                </td>
                <td class="whitespace-nowrap p-4 text-right align-middle">
                    <div class="inline-flex items-center gap-2">
                        <april:button-link href="{{ route('meals.show', $meal) }}" variant="link" size="sm">
                            View
                        </april:button-link>

                        <april:alert-dialog>
                            <x-slot:trigger>
                                <april:button type="button" variant="link" size="sm" class="text-destructive">
                                    Delete
                                </april:button>
                            </x-slot:trigger>
                            <x-slot:content>
                                <div>
                                    <h2 class="text-lg font-semibold" x-bind="title">Delete this meal?</h2>
                                    <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                                        <strong>{{ $meal->name }}</strong> and the nutrition it contributed
                                        to your dashboard will be removed.
                                    </p>
                                </div>
                                <april:alert-dialog-footer>
                                    <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                                    <form action="{{ route('meals.destroy', $meal) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <april:button type="submit" variant="destructive" x-bind="action">
                                            Delete meal
                                        </april:button>
                                    </form>
                                </april:alert-dialog-footer>
                            </x-slot:content>
                        </april:alert-dialog>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-4 text-center align-middle text-sm text-muted-foreground">
                    No meals logged yet.
                    <april:button-link href="{{ route('meals.create') }}" variant="link" size="sm">
                        Log your first meal
                    </april:button-link>
                </td>
            </tr>
        @endforelse
    </x-slot:body>
</april:data-table>
@endsection

{{-- The controller paginates, so without these links every meal past the
     first page would be unreachable. --}}
@if(method_exists($meals, 'hasPages') && $meals->hasPages())
    <div class="mt-6">{{ $meals->links() }}</div>
@endif
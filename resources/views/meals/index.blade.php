@extends('layouts.back')

@section('title', 'My Meals')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">My Meals</h1>
        <april:button-link href="{{ route('meals.create') }}">
            <x-lucide-plus class="mr-2 size-4" />
            Log a meal
        </april:button-link>
    </div>

    <april:card class="overflow-hidden">
        <x-slot:content>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <caption class="sr-only">Meals you have logged</caption>
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Meal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Products</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($meals as $meal)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ $meal->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ ucfirst($meal->type) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ $meal->consumed_on?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ $meal->foods->pluck('name')->join(', ') ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <april:button-link href="{{ route('meals.show', $meal) }}" variant="ghost" size="sm">
                                            View
                                        </april:button-link>

                                        <x-confirm-action
                                            :action="route('meals.destroy', $meal)"
                                            label="Delete meal"
                                            title="Delete this meal?"
                                            description="« {{ $meal->name }} » and the products recorded in it will be removed. This cannot be undone."
                                        >
                                            Delete<span class="sr-only"> {{ $meal->name }}</span>
                                        </x-confirm-action>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                    No meals logged yet.
                                    <april:button-link href="{{ route('meals.create') }}" variant="link" size="sm">
                                        Log your first meal
                                    </april:button-link>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot:content>

        @if($meals->hasPages())
            <x-slot:footer>
                {{ $meals->links() }}
            </x-slot:footer>
        @endif
    </april:card>
@endsection
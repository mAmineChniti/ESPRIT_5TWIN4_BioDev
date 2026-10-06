@extends('layouts.back')

@section('title', 'My Meals')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">My Meals</h1>
    <a href="{{ route('meals.create') }}" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
        + Log a meal
    </a>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Meal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Products</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-card divide-y divide-border">
            @forelse($meals as $meal)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $meal->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ ucfirst($meal->type) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $meal->consumed_on?->format('Y-m-d') }}</td>
                <td class="px-6 py-4 text-sm text-muted-foreground">
                    {{ $meal->foods->pluck('name')->join(', ') ?: '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('meals.show', $meal) }}" class="text-primary hover:underline">View</a>
                    <form action="{{ route('meals.destroy', $meal) }}" method="POST" class="inline" onsubmit="return confirm('Delete this meal?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-4 text-sm text-muted-foreground text-center">
                    No meals logged yet. <a href="{{ route('meals.create') }}" class="text-primary">Log your first meal</a>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
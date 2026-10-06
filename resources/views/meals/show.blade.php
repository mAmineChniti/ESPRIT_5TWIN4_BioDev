@extends('layouts.back')

@section('title', 'Meal')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('meals.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
        <h1 class="text-2xl font-bold">{{ $meal->name }}</h1>
    </div>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl">
    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <dt class="text-sm font-medium text-muted-foreground">Type</dt>
            <dd class="mt-1 text-sm text-foreground">{{ ucfirst($meal->type) }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-muted-foreground">Date</dt>
            <dd class="mt-1 text-sm text-foreground">{{ $meal->consumed_on?->format('Y-m-d') }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-muted-foreground">Estimated calories</dt>
            <dd class="mt-1 text-sm text-foreground">{{ $meal->totalCalories() }} kcal</dd>
        </div>
        @if($meal->notes)
            <div class="sm:col-span-3">
                <dt class="text-sm font-medium text-muted-foreground">Notes</dt>
                <dd class="mt-1 text-sm text-foreground">{{ $meal->notes }}</dd>
            </div>
        @endif
    </dl>

    <h2 class="mt-6 text-md font-medium text-foreground">Products in this meal</h2>
    <div class="mt-2 overflow-hidden border border-border rounded-md">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Product</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Category</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase">Quantity</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase">Eco score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($meal->foods as $food)
                    <tr>
                        <td class="px-4 py-2 text-sm text-foreground">
                            <a href="{{ route('foods.show', $food) }}" class="text-primary hover:underline">{{ $food->name }}</a>
                        </td>
                        <td class="px-4 py-2 text-sm text-muted-foreground">{{ $food->category->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-muted-foreground text-right">{{ $meal->quantityFor($food) }} g</td>
                        <td class="px-4 py-2 text-right"><x-eco-score :score="$food->environmental_score?->value" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
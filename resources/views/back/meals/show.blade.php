@extends('layouts.back')

@section('title', 'Meal')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <april:button-link href="{{ route('meals.index') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back
    </april:button-link>
    <h1 class="text-2xl font-bold">{{ $meal->name }}</h1>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
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

    <h2 class="mt-6 text-base font-medium text-foreground">Products in this meal</h2>
        <april:data-table class="mt-2">
            <x-slot:header>
                <tr>
                    <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Product</th>
                    <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
                    <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Quantity</th>
                    <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Eco score</th>
                </tr>
            </x-slot:header>
            <x-slot:body>
                @foreach($meal->foods as $food)
                    <tr class="border-b transition-colors last:border-0">
                        <td class="p-4 align-middle text-sm text-foreground">
                            <april:button-link href="{{ route('foods.show', $food) }}" variant="link" size="sm">
                                {{ $food->name }}
                            </april:button-link>
                        </td>
                        <td class="p-4 align-middle text-sm text-muted-foreground">{{ $food->category->name ?? '—' }}</td>
                        <td class="p-4 text-right align-middle text-sm text-muted-foreground">
                            {{ $meal->quantityFor($food) }} g
                        </td>
                        <td class="p-4 text-right align-middle">
                            <x-eco-score :score="$food->environmental_score?->value" />
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>
        </april:data-table>
    </x-slot:content>
</april:card>
@endsection
@extends('layouts.front')

@section('title', __('Compare Products'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-foreground">{{ __('Compare Products') }}</h1>
        <p class="text-sm text-muted-foreground">{{ __('Select up to 3 products to compare nutritional values and eco-impact side by side.') }}</p>
    </div>

    @if(($comparedFoods ?? collect())->count() >= 2)
        <april:card>
            <x-slot:content>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="p-2 text-left">{{ __('Attribute') }}</th>
                                @foreach($comparedFoods as $food)
                                    <th scope="col" class="p-2 text-left">{{ $food->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row" class="p-2 text-left">{{ __('Calories') }}</th>
                                @foreach($comparedFoods as $food)
                                    <td class="p-2">{{ $food->calories }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="p-2 text-left">{{ __('Protein') }}</th>
                                @foreach($comparedFoods as $food)
                                    <td class="p-2">{{ $food->protein }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="p-2 text-left">{{ __('Carbs') }}</th>
                                @foreach($comparedFoods as $food)
                                    <td class="p-2">{{ $food->carbs }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="p-2 text-left">{{ __('Fat') }}</th>
                                @foreach($comparedFoods as $food)
                                    <td class="p-2">{{ $food->fat }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th scope="row" class="p-2 text-left">{{ __('Origin') }}</th>
                                @foreach($comparedFoods as $food)
                                    <td class="p-2">{{ $food->origin }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-slot:content>
        </april:card>
    @endif

    <april:card>
        <x-slot:content>
            <p class="text-sm text-muted-foreground">{{ __('Browse the catalog to pick products, then compare them side by side.') }}</p>
            <div class="pt-2">
                <april:button-link href="{{ route('products.index') }}" variant="outline">
                    {{ __('Browse products') }}
                </april:button-link>
            </div>
        </x-slot:content>
    </april:card>
</div>
@endsection

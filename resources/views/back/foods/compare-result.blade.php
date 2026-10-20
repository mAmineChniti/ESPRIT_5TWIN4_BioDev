@extends('layouts.back')

@section('title', __('Product Comparison'))

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <april:button-link href="{{ route('foods.compare') }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            {{ __('New comparison') }}
        </april:button-link>
        <h1 class="text-2xl font-bold text-foreground">{{ __('Product Comparison') }}</h1>
    </div>
    <april:button-link href="{{ route('foods.index') }}" variant="outline" size="sm">
        {{ __('Product catalog') }}
    </april:button-link>
</div>

@if($products->isEmpty())
    <april:alert variant="secondary" title="{{ __('No products selected') }}">
        <x-slot:description>{{ __('Please select at least two products to compare.') }}</x-slot:description>
    </april:alert>
@else
    <april:card class="overflow-x-auto">
        <x-slot:content>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th scope="col" class="py-3 px-4 font-semibold text-muted-foreground w-1/4">{{ __('Metric / Attribute') }}</th>
                        @foreach($products as $product)
                            <th scope="col" class="py-3 px-4 font-bold text-foreground text-base">
                                <a href="{{ route('foods.show', $product) }}" class="hover:underline text-primary">
                                    {{ $product->name }}
                                </a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Category') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 text-foreground">{{ $product->category?->name ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Origin') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 text-foreground">{{ $product->origin ?: '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Producer') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 text-foreground">{{ $product->producer?->name ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Eco Grade') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4">
                                <x-eco-score :score="$product->environmental_score" />
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Calories') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 tabular-nums text-foreground font-semibold">{{ $product->calories }} <span class="text-xs font-normal text-muted-foreground">kcal</span></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Protein') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 tabular-nums text-foreground">{{ $product->protein }} <span class="text-xs text-muted-foreground">g</span></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Carbs') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 tabular-nums text-foreground">{{ $product->carbs }} <span class="text-xs text-muted-foreground">g</span></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Fat') }}</td>
                        @foreach($products as $product)
                            <td class="py-3 px-4 tabular-nums text-foreground">{{ $product->fat }} <span class="text-xs text-muted-foreground">g</span></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Nutri-Score Estimate') }}</td>
                        @foreach($products as $product)
                            @php
                                $nutri = \App\Services\Nutrition\NutriScoreCalculator::calculate($product);
                            @endphp
                            <td class="py-3 px-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold text-white shadow-sm"
                                      style="background-color: {{ $nutri['color'] }}">
                                    {{ $nutri['grade'] }}
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">{{ $nutri['label'] }}</span>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-medium text-muted-foreground">{{ __('Carbon Footprint (CO₂)') }}</td>
                        @foreach($products as $product)
                            @php
                                $nutri = \App\Services\Nutrition\NutriScoreCalculator::calculate($product);
                            @endphp
                            <td class="py-3 px-4 text-xs text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ $nutri['carbon_kg'] }}</span> kg CO₂/kg
                                <span class="block text-xs">({{ $nutri['carbon_rating'] }})</span>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </x-slot:content>
    </april:card>
@endif
@endsection

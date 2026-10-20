@extends('layouts.back')

@section('title', __('Compare Products'))

@section('content')
<div class="mb-6 flex items-center gap-4">
    <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        {{ __('Back') }}
    </april:button-link>
    <div>
        <h1 class="text-2xl font-bold text-foreground">{{ __('Compare Products') }}</h1>
        <p class="text-sm text-muted-foreground">{{ __('Select up to 3 products to compare nutritional values and eco-impact side by side.') }}</p>
    </div>
</div>

@if($errors->any())
    <april:alert variant="destructive" class="mb-6 max-w-2xl" aria-live="polite">
        <x-slot:icon><x-lucide-circle-alert class="size-4" /></x-slot:icon>
        <x-slot:description>{{ $errors->first() }}</x-slot:description>
    </april:alert>
@endif

<april:card class="max-w-2xl">
    <x-slot:content>
        <form method="POST" action="{{ route('foods.compare-result') }}" class="space-y-6">
            @csrf

            <div class="space-y-2">
                <april:label for="food1">{{ __('Product 1') }} <span aria-hidden="true">*</span></april:label>
                <april:native-select id="food1" name="food_ids[]" required>
                    <option value="" disabled @selected(!old('food_ids.0'))>{{ __('Choose the first product…') }}</option>
                    @foreach($foods as $food)
                        <option value="{{ $food->id }}" @selected(old('food_ids.0') == $food->id)>
                            {{ $food->name }} ({{ $food->category?->name ?? '—' }})
                        </option>
                    @endforeach
                </april:native-select>
            </div>

            <div class="space-y-2">
                <april:label for="food2">{{ __('Product 2') }} <span aria-hidden="true">*</span></april:label>
                <april:native-select id="food2" name="food_ids[]" required>
                    <option value="" disabled @selected(!old('food_ids.1'))>{{ __('Choose the second product…') }}</option>
                    @foreach($foods as $food)
                        <option value="{{ $food->id }}" @selected(old('food_ids.1') == $food->id)>
                            {{ $food->name }} ({{ $food->category?->name ?? '—' }})
                        </option>
                    @endforeach
                </april:native-select>
            </div>

            <div class="space-y-2">
                <april:label for="food3">{{ __('Product 3 (optional)') }}</april:label>
                <april:native-select id="food3" name="food_ids[]">
                    <option value="" @selected(!old('food_ids.2'))>{{ __('None (compare 2 products)') }}</option>
                    @foreach($foods as $food)
                        <option value="{{ $food->id }}" @selected(old('food_ids.2') == $food->id)>
                            {{ $food->name }} ({{ $food->category?->name ?? '—' }})
                        </option>
                    @endforeach
                </april:native-select>
            </div>

            <div class="flex justify-end pt-2">
                <april:button type="submit">
                    <x-lucide-arrow-left-right class="size-4" />
                    {{ __('Compare products') }}
                </april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
@endsection

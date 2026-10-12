@extends('layouts.back')

@section('title', __('Log a Meal'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <april:button-link href="{{ route('meals.index') }}" variant="link" size="sm" class="text-muted-foreground">
            <x-lucide-arrow-left class="size-4" />
            {{ __('Back') }}
        </april:button-link>
        <h1 class="text-2xl font-bold">{{ __('Log a Meal') }}</h1>
    </div>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        {{-- The picker is capped, so it carries its own search rather than
             silently hiding the rest of the catalog. --}}
        <form action="{{ route('meals.create') }}" method="GET" class="mb-6 flex items-end gap-2">
            <div class="flex-1">
                <april:label for="q">{{ __('Find a product') }}</april:label>
                <april:input id="q" name="q" type="search" :value="$search"
                             :placeholder="__('Name, origin or category')"
                             class="mt-1" />
            </div>
            <april:button type="submit" variant="outline">{{ __('Search') }}</april:button>
            @if($search !== '')
                <april:button-link href="{{ route('meals.create') }}" variant="link" size="sm">
                    {{ __('Clear') }}
                </april:button-link>
            @endif
        </form>

        <form action="{{ route('meals.store') }}" method="POST" class="space-y-6" novalidate>
            @csrf

            <div class="space-y-2">
                <april:label for="name">{{ __('Meal name') }} <span aria-hidden="true">*</span></april:label>
                <april:input id="name" name="name" :value="old('name')" required
                             aria-describedby="name-error"
                             :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
                <p id="name-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('name') {{ $message }} @enderror
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <april:label for="type">{{ __('Type') }} <span aria-hidden="true">*</span></april:label>
                    <april:native-select id="type" name="type" required aria-describedby="type-error"
                                           :aria-invalid="$errors->has('type') ? 'true' : 'false'">
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ \App\Models\Meal::typeLabel($type) }}</option>
                        @endforeach
                    </april:native-select>
                    <p id="type-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('type') {{ $message }} @enderror
                    </p>
                </div>
                <div class="space-y-2">
                    <april:label for="consumed_on">{{ __('Date') }}</april:label>
                    <april:input id="consumed_on" name="consumed_on" type="date"
                                 :value="old('consumed_on', now()->toDateString())"
                                 :max="now()->toDateString()" aria-describedby="consumed_on-error"
                                 :aria-invalid="$errors->has('consumed_on') ? 'true' : 'false'" />
                    <p id="consumed_on-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('consumed_on') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            {{-- A fieldset/legend groups the product checkboxes, and the quantity
                 input sits outside the checkbox's label so activating it does not
                 toggle the checkbox and it does not inherit the label as its name. --}}
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-foreground">
                    {{ __('Products') }} <span aria-hidden="true">*</span>
                </legend>
                <p id="foods-hint" class="text-xs text-muted-foreground">
                    {{ __('Select each product you ate and enter the quantity in grams.') }}
                </p>
                @if($foods->isEmpty())
                    <div class="rounded-md border border-dashed border-border p-6 text-center">
                        <p class="text-sm font-medium">{{ __('No product matched.') }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            @if($search !== '')
                                {{ __('Try a shorter search term, or clear it to see the first products.') }}
                            @else
                                {{ __('No products are registered yet.') }}
                            @endif
                        </p>
                    </div>
                @endif

                <div class="{{ $foods->isEmpty() ? '' : 'max-h-64 overflow-y-auto' }} divide-y divide-border rounded-md border border-border">
                    @foreach($foods as $food)
                        <div class="flex items-center justify-between gap-3 p-3">
                            <div class="flex items-center gap-2">
                                <april:checkbox id="food-{{ $food->id }}" name="foods[]"
                                                :value="$food->id"
                                                :checked="in_array($food->id, old('foods', []))"
                                                aria-describedby="foods-hint" />
                                <april:label for="food-{{ $food->id }}" class="font-normal">
                                    <span class="text-sm text-foreground">{{ $food->name }}</span>
                                    <span class="ml-1 text-xs text-muted-foreground">{{ $food->category->name }}</span>
                                </april:label>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <april:label for="quantity-{{ $food->id }}" class="sr-only">
                                    {{ __('Quantity in grams for :name', ['name' => $food->name]) }}
                                </april:label>
                                <april:input id="quantity-{{ $food->id }}" type="number"
                                             name="quantities[{{ $food->id }}]"
                                             :value="old('quantities')[$food->id] ?? 100"
                                             step="1" min="0" class="w-24" />
                                <span class="text-sm text-muted-foreground">g</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($pickerTruncated)
                    <p class="text-xs text-muted-foreground">
                        {{ trans_choice('{1} Showing the first :count product by name. Use the search above to reach anything else.|[2,*] Showing the first :count products by name. Use the search above to reach anything else.', $foods->count(), ['count' => $foods->count()]) }}
                    </p>
                @endif

                <p class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('foods') {{ $message }} @enderror
                </p>
            </fieldset>

            <div class="space-y-2">
                <april:label for="notes">{{ __('Notes') }}</april:label>
                <x-textarea-field id="notes" name="notes" rows="2"
                    :value="old('notes')" aria-describedby="notes-error" />
                <p id="notes-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('notes') {{ $message }} @enderror
                </p>
            </div>

            <div class="flex justify-end pt-4">
                <april:button type="submit">{{ __('Save meal') }}</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
@endsection
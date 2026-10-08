@props([
    'food' => null,
    'selectedCertifications' => [],
])

@php
    // Shared markup for the add and edit product forms, so labels, ids and error
    // wiring exist in exactly one place.
    $editing = $food !== null;
    $action = $editing ? route('foods.update', $food) : route('foods.store');
    $categories = \App\Models\Category::orderBy('name')->get();
    $certifications = \App\Models\Certification::orderBy('name')->get();
@endphp

<div class="mb-6 flex items-center gap-4">
    <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back
    </april:button-link>
    <h1 class="text-2xl font-bold">{{ $editing ? 'Edit product' : 'Add a product' }}</h1>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ $action }}" method="POST" class="space-y-6">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="space-y-2">
                <april:label for="name">Product name <span aria-hidden="true">*</span></april:label>
                <april:input id="name" name="name" :value="old('name', $food?->name)" required
                             aria-describedby="name-error"
                             :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
                <p id="name-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('name') {{ $message }} @enderror
                </p>
            </div>

            <div class="space-y-2">
                <april:label for="category_id">Category <span aria-hidden="true">*</span></april:label>
                <april:native-select id="category_id" name="category_id" required
                                       aria-describedby="category_id-error"
                                       :aria-invalid="$errors->has('category_id') ? 'true' : 'false'">
                    <option value="">Select a category…</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected((string) old('category_id', $food?->category_id) === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </april:native-select>
                <p id="category_id-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('category_id') {{ $message }} @enderror
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <april:label for="origin">Origin</april:label>
                    <april:input id="origin" name="origin" :value="old('origin', $food?->origin)"
                                 placeholder="ex: France" aria-describedby="origin-error"
                                 :aria-invalid="$errors->has('origin') ? 'true' : 'false'" />
                    <p id="origin-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('origin') {{ $message }} @enderror
                    </p>
                </div>

                <div class="space-y-2">
                    <april:label for="environmental_score">Environmental grade</april:label>
                    <april:native-select id="environmental_score" name="environmental_score"
                                           aria-describedby="environmental_score-error"
                                           :aria-invalid="$errors->has('environmental_score') ? 'true' : 'false'">
                        <option value="">Not set</option>
                        @foreach(\App\Enums\EnvironmentalScore::cases() as $score)
                            <option value="{{ $score->value }}"
                                @selected(old('environmental_score', $food?->environmental_score?->value) === $score->value)>
                                {{ $score->value }} — {{ $score->label() }}
                            </option>
                        @endforeach
                    </april:native-select>
                    <p id="environmental_score-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('environmental_score') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-foreground">Certifications (verified)</legend>
                <p id="certifications-hint" class="text-xs text-muted-foreground">
                    Only certifications recorded with an issuing body and a validity date can be selected.
                    Clearing every box removes them all on save.
                </p>
                <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-3">
                    @forelse($certifications as $certification)
                        <div class="flex items-start gap-2 rounded-md border border-border p-3">
                            <april:checkbox id="certification-{{ $certification->id }}"
                                            name="certifications[]"
                                            :value="$certification->id"
                                            :checked="in_array($certification->id, old('certifications', $selectedCertifications))"
                                            aria-describedby="certifications-hint"
                                            class="mt-0.5" />
                            <april:label for="certification-{{ $certification->id }}" class="font-normal">
                                <span class="block text-sm font-medium text-foreground">{{ $certification->name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $certification->issuer }}</span>
                                @if($certification->isExpired())
                                    <span class="block text-xs text-destructive">Expired</span>
                                @endif
                            </april:label>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">No certifications recorded yet.</p>
                    @endforelse
                </div>
                <p class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('certifications') {{ $message }} @enderror
                    @error('certifications.*') {{ $message }} @enderror
                </p>
            </fieldset>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach([
                    'calories' => ['Calories', 1],
                    'protein' => ['Protein (g)', '0.01'],
                    'carbs' => ['Carbs (g)', '0.01'],
                    'fat' => ['Fat (g)', '0.01'],
                ] as $field => [$label, $step])
                    <div class="space-y-2">
                        <april:label for="{{ $field }}">{{ $label }}</april:label>
                        <april:input id="{{ $field }}" name="{{ $field }}" type="number"
                                     :value="old($field, $food?->{$field} ?? 0)"
                                     @if($step !== 1) step="{{ $step }}" @endif
                                     min="0" required
                                     aria-describedby="{{ $field }}-error"
                                     :aria-invalid="$errors->has($field) ? 'true' : 'false'" />
                        <p id="{{ $field }}-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                            @error($field) {{ $message }} @enderror
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end pt-4">
                <april:button type="submit">{{ $editing ? 'Save changes' : 'Save' }}</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
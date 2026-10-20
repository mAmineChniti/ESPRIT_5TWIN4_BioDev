@php
    $editing = isset($food) && $food->exists;
    $food ??= null;
    $selectedCertifications ??= [];
    $action = $editing ? route('foods.update', $food) : route('foods.store');
    $categories = \App\Models\Category::orderBy('name')->get();
    $certifications = \App\Models\Certification::orderBy('name')->get();
@endphp

<div class="mb-6 flex items-center gap-4">
    <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        {{ __('Back') }}
    </april:button-link>
    <h1 class="text-2xl font-bold">{{ $editing ? __('Edit product') : __('Add a product') }}</h1>
</div>

{{-- ─── IA 1 : Smart AI Auto-fill banner ───────────────────────────────── --}}
@if(!$editing)
<div class="mb-4 rounded-lg border border-primary/30 bg-primary/5 p-4" x-data="aiFill()">
    <div class="flex flex-wrap items-center gap-3">
        <x-lucide-sparkles class="size-5 text-primary" />
        <div class="flex-1">
            <p class="text-sm font-semibold text-foreground">{{ __('AI Auto-fill') }}</p>
            <p class="text-xs text-muted-foreground">{{ __('Type the product name above then click "Fill with AI" to auto-complete category, nutritional values and eco-score.') }}</p>
        </div>
        <april:button type="button" variant="outline" size="sm"
                      x-on:click="fill()"
                      x-bind:disabled="loading"
                      id="ai-fill-btn">
            <x-lucide-sparkles class="size-4" />
            <span x-text="loading ? '{{ __('Filling…') }}' : '{{ __('Fill with AI') }}'">{{ __('Fill with AI') }}</span>
        </april:button>
    </div>
    <p x-show="error" x-text="error" class="mt-2 text-xs text-destructive" role="alert" aria-live="polite"></p>
    <p x-show="explanation" x-text="explanation" class="mt-2 text-xs text-muted-foreground italic" aria-live="polite"></p>
</div>
@endif

{{-- ─── Option 3 : Live Nutri-Score panel ──────────────────────────────── --}}
<div class="mb-4 rounded-lg border border-border bg-card p-4" x-data="nutriScore()">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <x-lucide-activity class="size-5 text-primary" />
            <span class="text-sm font-semibold">{{ __('Live Nutri-Score') }}</span>
        </div>
        <div class="flex items-center gap-3 flex-1 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">{{ __('Grade') }}:</span>
                <span x-text="grade"
                      x-bind:style="color ? ('background-color:' + color + '; color: #ffffff;') : ''"
                      x-bind:class="{ 'bg-muted text-muted-foreground border border-border': !color || grade==='—' }"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold shadow-sm transition-all duration-200">
                    —
                </span>
            </div>
            <div class="text-xs font-medium text-foreground" x-show="label">
                <span x-text="label"></span>
            </div>
            <div class="flex items-center gap-1 text-xs text-muted-foreground" x-show="carbon_kg">
                <x-lucide-leaf class="size-3 text-primary" />
                <span>CO₂ ≈ <span x-text="carbon_kg" class="font-medium"></span> kg/kg</span>
                <span x-text="'('+carbon_rating+')'"></span>
            </div>
            <april:button type="button" variant="ghost" size="sm" x-on:click="recalculate()" x-bind:disabled="loading">
                <x-lucide-refresh-cw class="size-4" x-bind:class="{ 'animate-spin': loading }" />
                <span x-text="loading ? '{{ __('Calculating…') }}' : '{{ __('Calculate') }}'">{{ __('Calculate') }}</span>
            </april:button>
        </div>
    </div>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ $action }}" method="POST" class="space-y-6" novalidate
              x-data x-ref="foodForm">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="space-y-2">
                <april:label for="name">{{ __('Product name') }} <span aria-hidden="true">*</span></april:label>
                <april:input id="name" name="name" :value="old('name', $food?->name)" required
                             aria-describedby="name-error"
                             :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
                <p id="name-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('name') {{ $message }} @enderror
                </p>
            </div>

            <div class="space-y-2">
                <april:label for="category_id">{{ __('Category') }} <span aria-hidden="true">*</span></april:label>
                <april:native-select id="category_id" name="category_id" required
                                       aria-describedby="category_id-error"
                                       :aria-invalid="$errors->has('category_id') ? 'true' : 'false'">
                    <option value="">{{ __('Select a category…') }}</option>
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
                    <april:label for="origin">{{ __('Origin') }}</april:label>
                    <april:input id="origin" name="origin" :value="old('origin', $food?->origin)"
                         placeholder="{{ __('e.g. France') }}" aria-describedby="origin-error"
                                 :aria-invalid="$errors->has('origin') ? 'true' : 'false'" />
                    <p id="origin-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('origin') {{ $message }} @enderror
                    </p>
                </div>

                <div class="space-y-2">
                    <april:label for="environmental_score">{{ __('Environmental grade') }}</april:label>
                    <april:native-select id="environmental_score" name="environmental_score"
                                           aria-describedby="environmental_score-error"
                                           :aria-invalid="$errors->has('environmental_score') ? 'true' : 'false'">
                        <option value="">{{ __('Not set') }}</option>
                        @foreach(\App\Enums\EnvironmentalScore::cases() as $score)
                            <option value="{{ $score->value }}"
                                @selected(old('environmental_score', $food?->environmental_score?->value) === $score->value)>
                                {{ $score->value }} – {{ $score->label() }}
                            </option>
                        @endforeach
                    </april:native-select>
                    <p id="environmental_score-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('environmental_score') {{ $message }} @enderror
                    </p>
                </div>
            </div>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-foreground">{{ __('Certifications (verified)') }}</legend>
                <p id="certifications-hint" class="text-xs text-muted-foreground">
                    {{ __('Only certifications recorded with an issuing body and a validity date can be selected. Clearing every box removes them all on save.') }}
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
                                    <span class="block text-xs text-destructive">{{ __('Expired') }}</span>
                                @endif
                            </april:label>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">{{ __('No certifications recorded yet.') }}</p>
                    @endforelse
                </div>
                <p class="text-sm text-destructive" role="alert" aria-live="polite">
                    @error('certifications') {{ $message }} @enderror
                    @error('certifications.*') {{ $message }} @enderror
                </p>
            </fieldset>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach([
                    'calories' => [__('Calories'), 1],
                    'protein'  => [__('Protein (g)'), '0.01'],
                    'carbs'    => [__('Carbs (g)'), '0.01'],
                    'fat'      => [__('Fat (g)'), '0.01'],
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
                <april:button type="submit">{{ $editing ? __('Save changes') : __('Save') }}</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>

@push('scripts')
<script>
// ─── IA 1 : Smart AI Auto-fill ────────────────────────────────────────────
function aiFill() {
    return {
        loading: false,
        error: '',
        explanation: '',

        async fill() {
            const name = document.getElementById('name')?.value?.trim();
            if (!name) { this.error = '{{ __("Please enter a product name first.") }}'; return; }
            this.loading = true; this.error = ''; this.explanation = '';
            try {
                const res = await fetch('{{ route('foods.ai-suggest') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name, origin: document.getElementById('origin')?.value || null }),
                });
                if (!res.ok) throw new Error(await res.text());
                const data = await res.json();

                // Fill category
                const catSelect = document.getElementById('category_id');
                if (catSelect && data.category_id) {
                    catSelect.value = data.category_id;
                    // fallback: try matching by name
                    if (!catSelect.value && data.category_name) {
                        for (const opt of catSelect.options) {
                            if (opt.text.toLowerCase() === data.category_name.toLowerCase()) {
                                catSelect.value = opt.value; break;
                            }
                        }
                    }
                }

                // Fill origin
                if (data.origin) {
                    const origInput = document.getElementById('origin');
                    if (origInput && !origInput.value) origInput.value = data.origin;
                }

                // Fill eco score
                if (data.environmental_score) {
                    const ecoSelect = document.getElementById('environmental_score');
                    if (ecoSelect) ecoSelect.value = data.environmental_score;
                }

                // Fill nutrition fields
                ['calories', 'protein', 'carbs', 'fat'].forEach(f => {
                    const el = document.getElementById(f);
                    if (el && data[f] !== undefined) el.value = data[f];
                });

                this.explanation = data.explanation || '';

                // Trigger Nutri-Score refresh
                window.dispatchEvent(new Event('nutrition-updated'));

            } catch (e) {
                this.error = '{{ __("AI fill failed. Please fill manually.") }}';
                console.error(e);
            } finally {
                this.loading = false;
            }
        }
    };
}

// ─── Option 3 : Live Nutri-Score ─────────────────────────────────────────
function nutriScore() {
    return {
        grade: '—', label: '', carbon_kg: '', carbon_rating: '', color: '',
        loading: false,

        init() {
            // Recalculate whenever fields change or receive input
            const fields = ['calories', 'protein', 'carbs', 'fat', 'category_id', 'origin', 'environmental_score'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => this.recalculate());
                    el.addEventListener('change', () => this.recalculate());
                }
            });

            // Also recalculate when AI fills the form
            window.addEventListener('nutrition-updated', () => this.recalculate());

            // Initial calculation if values already present (edit mode or old input)
            this.$nextTick(() => {
                const cal = document.getElementById('calories')?.value;
                if (cal !== undefined && cal !== '') {
                    this.recalculate();
                }
            });
        },

        async recalculate() {
            const get = id => {
                const val = document.getElementById(id)?.value;
                return (val === '' || isNaN(val)) ? 0 : parseFloat(val);
            };
            const catEl = document.getElementById('category_id');
            const catName = catEl?.options[catEl.selectedIndex]?.text || '';
            const origin = document.getElementById('origin')?.value || '';
            const ecoEl = document.getElementById('environmental_score');
            const ecoScore = ecoEl?.value || 'C';

            this.loading = true;
            try {
                const res = await fetch('{{ route('foods.live-nutri-score') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        calories: get('calories'),
                        protein: get('protein'),
                        carbs: get('carbs'),
                        fat: get('fat'),
                        category: catName,
                        origin: origin,
                        environmental_score: ecoScore,
                    }),
                });
                if (!res.ok) {
                    console.error('Nutri-Score calculation error:', await res.text());
                    return;
                }
                const d = await res.json();
                this.grade = d.grade || '—';
                this.label = d.label || '';
                this.color = d.color || '';
                this.carbon_kg = d.carbon_kg || '';
                this.carbon_rating = d.carbon_rating || '';
            } catch (err) {
                console.error('NutriScore fetch error:', err);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@extends('layouts.front')

@section('title', $food->name)

@section('content')
<nav class="mb-4 text-sm">
    <april:button-link href="{{ route('products.index') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        All products
    </april:button-link>
</nav>

{{-- Trust header: the anti-greenwashing signal, stated plainly --}}
<april:card>
    <x-slot:content>
        <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold">{{ $food->name }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $food->category->name ?? 'Uncategorised' }}
                    @if($food->origin) · {{ $food->origin }} @endif
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Registered by
                    <span class="font-medium text-foreground">{{ $food->producer->name ?? 'nobody' }}</span>
                    on {{ $food->created_at->format('j M Y') }}
                </p>
            </div>

            <div class="shrink-0 text-center">
                <div class="relative mx-auto h-24 w-24"
                     role="img"
                     aria-label="Transparency score {{ $transparency }} out of 100. Verdict: {{ $verdict['level'] }}.">
                    <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90" aria-hidden="true">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="currentColor" stroke-width="3"
                                class="text-muted" />
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="3" stroke-linecap="round"
                                stroke-dasharray="{{ $transparency }}, 100"
                                class="{{ $verdict['tone']->textClasses() }}" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xl font-bold">
                        {{ $transparency }}
                    </span>
                </div>
                <p class="mt-1 text-sm font-semibold">{{ $verdict['level'] }}</p>
                <p class="max-w-[16rem] text-xs text-muted-foreground">{{ $verdict['message'] }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-muted/60 p-3">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Environmental grade</p>
                <div class="mt-1"><x-eco-score :score="$food->environmental_score" /></div>
            </div>
            <div class="rounded-lg bg-muted/60 p-3">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Consumer rating</p>
                <p class="mt-1 text-lg font-semibold">
                    @if($averageRating !== null)
                        <span class="inline-flex items-center gap-1">
                            <x-lucide-star class="size-4 fill-current text-secondary-foreground" />
                            {{ $averageRating }} <span class="text-sm font-normal text-muted-foreground">/ 5</span>
                        </span>
                    @else
                        <span class="text-sm font-normal text-muted-foreground">Not reviewed yet</span>
                    @endif
                </p>
            </div>
            <div class="rounded-lg bg-muted/60 p-3">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Times scanned</p>
                <p class="mt-1 text-lg font-semibold">{{ $food->scans_count }}</p>
            </div>
        </div>
    </x-slot:content>
</april:card>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    {{-- Interactive timeline --}}
    <section class="lg:col-span-2 rounded-2xl border border-border bg-card p-6 shadow-sm"
             x-data='{ selected: null }'>
        <h2 class="text-lg font-semibold">Supply chain timeline</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Each bar is the time a step took. Select a bar to see who recorded it.
        </p>

        <div class="mt-4 h-64">
            <canvas id="trace-timeline" role="img"
                    aria-label="Chart of how long the product spent at each recorded supply chain stage."></canvas>
        </div>

        @if($timeline === [])
            <p class="mt-4 rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                No supply chain has been recorded for this product.
            </p>
        @endif

        <ol class="mt-4 space-y-2">
            @forelse($timeline as $index => $step)
                <li>
                    <button type="button"
                            id="trace-step-{{ $index }}"
                            aria-controls="trace-step-detail-{{ $index }}"
                            :aria-expanded="selected?.index === {{ $index }} ? 'true' : 'false'"
                            class="w-full rounded-lg border border-border px-3 py-2 text-left text-sm hover:border-primary/50 hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            x-on:click="selected = { index: {{ $index }}, stage: @js($step['stage']), actor: @js($step['actor'] ?? 'Unknown'), role: @js($step['role'] ?? ''), date: @js($step['date']), notes: @js($step['notes'] ?? '') }">
                        <span class="flex items-center justify-between gap-3">
                            <span class="font-medium">{{ $step['stage'] }}</span>
                            <span class="text-xs text-muted-foreground">{{ \Carbon\Carbon::parse($step['date'])->format('j M Y') }}</span>
                        </span>
                        <span class="mt-0.5 block text-xs text-muted-foreground">
                            {{ $step['actor'] ?? 'Unknown actor' }}
                            @if($step['daysInStage'] > 0)
                                · {{ $step['daysInStage'] }} {{ Str::plural('day', $step['daysInStage']) }} before the next step
                            @endif
                        </span>
                    </button>
                </li>
            @empty
                <li class="text-sm text-muted-foreground">Nothing recorded yet.</li>
            @endforelse
        </ol>

        <div id="trace-step-detail-{{ $timeline === [] ? 0 : array_key_last($timeline) }}"
             x-show="selected" x-cloak
             class="mt-4 rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm"
             role="region" aria-live="polite">
            <p class="font-semibold" x-text="selected?.stage"></p>
            <p class="mt-1 text-muted-foreground">
                Recorded by <span x-text="selected?.actor"></span>
                <span x-show="selected?.role" x-text="' (' + selected.role + ')'"></span>
            </p>
            <p class="mt-1 text-muted-foreground" x-text="selected?.date"></p>
            <p class="mt-2" x-show="selected?.notes" x-text="selected?.notes"></p>
        </div>

        {{-- Chain completeness is itself a red flag --}}
        @if($recordedStages < $totalStages)
            <april:alert variant="none" class="mt-4 border-secondary/50 bg-secondary/50 text-secondary-foreground">
                <x-slot:icon>
                    <x-lucide-triangle-alert class="size-4" />
                </x-slot:icon>
                <x-slot:description>
                    @if($lastStage)
                        This product is recorded up to <strong>{{ $lastStage->label() }}</strong>.
                    @else
                        No stage of this product's chain has been recorded yet.
                    @endif
                    The later steps of the chain have not been recorded, so the journey is not yet fully traceable.
                </x-slot:description>
            </april:alert>
        @endif

        <script type="application/json" id="trace-timeline-data">{!! json_encode(['steps' => $timeline]) !!}</script>
    </section>

    {{-- Where it came from, and how far that is from us. --}}
    <section class="lg:col-span-2 rounded-2xl border border-border bg-card p-6 shadow-sm">
        <x-origin-map :food="$food" />
    </section>

    <div class="space-y-6">
        {{-- Certifications --}}
        <april:card>
            <x-slot:title class="text-lg">Certifications on file</x-slot:title>
            <x-slot:content>
                @forelse($food->certifications as $certification)
                    <div class="mt-3 rounded-lg border border-border p-3">
                        <p class="text-sm font-semibold">{{ $certification->name }}</p>
                        <dl class="mt-1 space-y-0.5 text-xs text-muted-foreground">
                            @if($certification->issuer)
                                <div class="flex justify-between gap-2">
                                    <dt>Issued by</dt><dd class="text-foreground">{{ $certification->issuer }}</dd>
                                </div>
                            @endif
                            @if($certification->certificate_number)
                                <div class="flex justify-between gap-2">
                                    <dt>Certificate</dt><dd class="text-foreground">{{ $certification->certificate_number }}</dd>
                                </div>
                            @endif
                            @if($certification->valid_until)
                                <div class="flex justify-between gap-2">
                                    <dt>Valid until</dt>
                                    <dd class="{{ $certification->isExpired() ? 'font-semibold text-destructive' : 'text-foreground' }}">
                                        {{ $certification->valid_until->format('j M Y') }}
                                        @if($certification->isExpired()) (expired) @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @empty
                    <p class="mt-3 rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground">
                        No certification is recorded for this product. Any claim on the packaging is unverified here.
                    </p>
                @endforelse
            </x-slot:content>
        </april:card>

        {{-- Report a claim --}}
        <april:card>
            <x-slot:title class="text-lg">Something look wrong?</x-slot:title>
            <x-slot:description>
                Tell us which claim you cannot verify. Upheld reports lower this product's transparency score.
            </x-slot:description>
            <x-slot:content>
                @auth
                    @if($canReview)
                        <form action="{{ route('products.reports.store', $food) }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <april:label for="reason" class="text-xs uppercase tracking-wide text-muted-foreground">
                                    Reason
                                </april:label>
                                <april:native-select id="reason" name="reason" required
                                                       class="mt-1"
                                                       aria-describedby="reason-error"
                                                       :aria-invalid="$errors->has('reason') ? 'true' : 'false'">
                                    @foreach($reasons as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </april:native-select>
                                <p id="reason-error" class="mt-1 text-sm text-destructive" role="alert" aria-live="polite">
                                    @error('reason') {{ $message }} @enderror
                                </p>
                            </div>
                            <div>
                                <april:label for="details" class="text-xs uppercase tracking-wide text-muted-foreground">
                                    Details
                                </april:label>
                                <x-textarea-field id="details" name="details" rows="3" class="mt-1" :value="old('details')" />
                                @error('details') <p class="mt-1 text-sm text-destructive" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <april:button type="submit" variant="destructive" class="w-full">Submit report</april:button>
                        </form>
                    @else
                        <p class="mt-3 rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                            Only consumer accounts can file reports.
                        </p>
                    @endif
                @else
                    <april:button-link href="{{ route('login') }}" variant="outline" class="mt-4 w-full">
                        Log in to report a claim
                    </april:button-link>
                @endauth
            </x-slot:content>
        </april:card>
    </div>
</div>

{{-- AI greenwashing detection --}}
<section class="mt-6" id="ai-audit">
    <april:card>
        <x-slot:title class="text-lg">
            <span class="flex items-center gap-2">
                <x-lucide-scan-search class="size-5 text-primary" />
                AI greenwashing audit
            </span>
        </x-slot:title>
        <x-slot:description>
            An automated auditor reads everything on this page and looks for claims the record does not
            support.
        </x-slot:description>
        <x-slot:content>
            <x-greenwashing-report :report="$analysis" :food="$food" />
        </x-slot:content>
    </april:card>
</section>

{{-- AI product assistant --}}
<section class="mt-6" id="assistant">
    <april:card>
        <x-slot:title class="text-lg">
            <span class="flex items-center gap-2">
                <x-lucide-message-circle-question class="size-5 text-primary" />
                Ask about this product
            </span>
        </x-slot:title>
        <x-slot:description>
            Answers come from the traceability record only, and cite the fields they used.
        </x-slot:description>
        <x-slot:content>
            <x-product-assistant :food="$food" :enabled="$assistantEnabled" />
        </x-slot:content>
    </april:card>
</section>

{{-- Better-evidenced alternatives --}}
@if($alternatives->isNotEmpty())
    <section class="mt-6">
        <h2 class="text-lg font-semibold">Better-evidenced alternatives</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            In the same category, scoring at least as well on transparency and environmental grade.
        </p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($alternatives as $alternative)
                <x-product-recommendation-card :recommendation="$alternative" />
            @endforeach
        </div>
    </section>
@endif

{{-- Reviews --}}
<april:card class="mt-6">
    <x-slot:content>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Consumer reviews</h2>
            @if($averageRating !== null)
                <span class="text-sm text-muted-foreground">
                    {{ $averageRating }} / 5 from {{ $food->reviews->count() }} {{ Str::plural('review', $food->reviews->count()) }}
                </span>
            @endif
        </div>

        @auth
            @if($canReview)
                <form action="{{ route('products.reviews.store', $food) }}" method="POST" class="mt-4 rounded-xl bg-muted/50 p-4">
                    @csrf
                    <p class="text-sm font-medium">{{ $myReview ? 'Update your review' : 'Leave a review' }}</p>
                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <div>
                            <april:label for="rating" class="text-xs uppercase tracking-wide text-muted-foreground">
                                Rating
                            </april:label>
                            <april:native-select id="rating" name="rating" required class="mt-1"
                                                   :aria-invalid="$errors->has('rating') ? 'true' : 'false'">
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected(($myReview?->rating ?? 5) === $i)>{{ $i }} / 5</option>
                                @endfor
                            </april:native-select>
                        </div>
                        <div class="min-w-[16rem] flex-1">
                            <april:label for="body" class="text-xs uppercase tracking-wide text-muted-foreground">
                                Your experience
                            </april:label>
                            <x-textarea-field id="body" name="body" rows="2" class="mt-1"
                                :value="old('body', $myReview?->body)"
                                placeholder="Did the product match its label?" />
                        </div>
                        <april:button type="submit">
                            {{ $myReview ? 'Update' : 'Publish' }}
                        </april:button>
                    </div>
                    @error('rating') <p class="mt-2 text-sm text-destructive" role="alert">{{ $message }}</p> @enderror
                    @error('body') <p class="mt-2 text-sm text-destructive" role="alert">{{ $message }}</p> @enderror
                </form>
            @endif
        @endauth

        <ul class="mt-4 divide-y divide-border">
            @forelse($food->reviews->sortByDesc('created_at') as $review)
                <li class="py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">{{ $review->user->name ?? 'Former user' }}</p>
                            <p class="text-xs text-muted-foreground">{{ $review->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-sm font-semibold text-secondary-foreground">{{ $review->rating }} / 5</span>
                    </div>
                    @if($review->body)
                        <p class="mt-2 text-sm text-foreground">{{ $review->body }}</p>
                    @endif
                </li>
            @empty
                <li class="py-6 text-center text-sm text-muted-foreground">No reviews yet. Be the first to say whether the label matched reality.</li>
            @endforelse
        </ul>
    </x-slot:content>
</april:card>
@endsection

@push('scripts')
    @vite(['resources/js/charts.js'])
@endpush
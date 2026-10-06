@extends('layouts.front')

@section('title', $food->name)

@section('content')
<nav class="mb-4 text-sm">
    <a href="{{ route('products.index') }}" class="text-muted-foreground hover:text-foreground">← All products</a>
</nav>

{{-- Trust header: the anti-greenwashing signal, stated plainly --}}
<section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
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
            <div class="relative mx-auto h-24 w-24">
                <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="currentColor" stroke-width="3"
                            class="text-muted" />
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="3" stroke-linecap="round"
                            stroke-dasharray="{{ $transparency }}, 100"
                            class="text-{{ $verdict['tone'] === 'high' ? 'destructive' : ($verdict['tone'] === 'low' ? 'bio' : 'footprint-medium') }}" />
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
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    {{-- Interactive timeline --}}
    <section class="lg:col-span-2 rounded-2xl border border-border bg-card p-6 shadow-sm"
             x-data='{ selected: null }'>
        <h2 class="text-lg font-semibold">Supply chain timeline</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Each bar is the time a step took. Click a bar to see who recorded it.
        </p>

        <div class="mt-4 h-64">
            <canvas id="trace-timeline"></canvas>
        </div>

        <p id="trace-timeline-empty" class="hidden rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            No supply chain has been recorded for this product.
        </p>

        <ol class="mt-4 space-y-2">
            @forelse($timeline as $step)
                <li>
                    <button type="button"
                            class="w-full rounded-lg border border-border px-3 py-2 text-left text-sm hover:border-primary/50 hover:bg-muted/50"
                            x-on:click="selected = { stage: @js($step['stage']), actor: @js($step['actor'] ?? 'Unknown'), role: @js($step['role'] ?? ''), date: @js($step['date']), notes: @js($step['notes'] ?? '') }">
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

        <div x-show="selected" x-cloak
             class="mt-4 rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm">
            <p class="font-semibold" x-text="selected?.stage"></p>
            <p class="mt-1 text-muted-foreground">
                Recorded by <span x-text="selected?.actor"></span>
                <span x-show="selected?.role" x-text="' (' + selected.role + ')'"></span>
            </p>
            <p class="mt-1 text-muted-foreground" x-text="selected?.date"></p>
            <p class="mt-2" x-show="selected?.notes" x-text="selected?.notes"></p>
        </div>

        {{-- Chain completeness is itself a red flag --}}
        @if($completedStages !== $totalStages)
            <p class="mt-4 flex items-start gap-2 rounded-lg bg-secondary/50 p-3 text-sm text-secondary-foreground">
                <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" />
                <span>
                    This product is recorded up to
                    <strong>{{ \App\Enums\Stage::from(end($completedStages))->label() ?? 'now' }}</strong>.
                    The later steps of the chain have not been recorded, so the journey is not yet fully traceable.
                </span>
            </p>
        @endif

        <script type="application/json" id="trace-timeline-data">{!! json_encode(['steps' => $timeline]) !!}</script>
    </section>

    <div class="space-y-6">
        {{-- Certifications --}}
        <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Certifications on file</h2>
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
        </section>

        {{-- Report a claim --}}
        <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Something look wrong?</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tell us which claim you cannot verify. Upheld reports lower this product's transparency score.
            </p>

            @auth
                @if($canReview)
                    <form action="{{ route('reports.store', $food) }}" method="POST" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="reason" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Reason</label>
                            <select id="reason" name="reason" required
                                    class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm">
                                @foreach($reasons as $reason)
                                    <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                @endforeach
                            </select>
                            @error('reason') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="details" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Details</label>
                            <textarea id="details" name="details" rows="3"
                                      class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm">{{ old('details') }}</textarea>
                            @error('details') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground hover:opacity-90">
                            Submit report
                        </button>
                    </form>
                @else
                    <p class="mt-3 rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                        Only consumer accounts can file reports.
                    </p>
                @endif
            @else
                <a href="{{ route('login') }}"
                   class="mt-4 block rounded-lg border border-border px-4 py-2 text-center text-sm font-semibold hover:bg-muted">
                    Log in to report a claim
                </a>
            @endauth
        </section>
    </div>
</div>

{{-- Reviews --}}
<section class="mt-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
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
            <form action="{{ route('reviews.store', $food) }}" method="POST" class="mt-4 rounded-xl bg-muted/50 p-4">
                @csrf
                <p class="text-sm font-medium">{{ $myReview ? 'Update your review' : 'Leave a review' }}</p>
                <div class="mt-3 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="rating" class="block text-xs uppercase tracking-wide text-muted-foreground">Rating</label>
                        <select id="rating" name="rating" required class="mt-1 rounded-lg border border-input bg-background px-3 py-2 text-sm">
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" @selected(($myReview?->rating ?? 5) === $i)>{{ $i }} / 5</option>
                            @endfor
                        </select>
                    </div>
                    <div class="min-w-[16rem] flex-1">
                        <label for="body" class="block text-xs uppercase tracking-wide text-muted-foreground">Your experience</label>
                        <textarea id="body" name="body" rows="2"
                                  class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                                  placeholder="Did the product match its label?">{{ old('body', $myReview?->body) }}</textarea>
                    </div>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                        {{ $myReview ? 'Update' : 'Publish' }}
                    </button>
                </div>
                @error('rating') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror
                @error('body') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror
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
</section>
@endsection

@push('scripts')
    @vite(['resources/js/charts.js'])
@endpush
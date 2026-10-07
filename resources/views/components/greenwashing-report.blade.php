{{--
    The AI greenwashing detector's output for one product.

    $report is an AnalysisReport. It carries either findings or a failure, and
    this component renders both honestly: a consumer must never be shown a
    clean bill of health that the detector did not actually produce.
--}}
@props(['report', 'food'])

@if ($report->hasFailed())
    <april:alert variant="none" class="border-border bg-muted/50">
        <x-slot:icon><x-lucide-wifi-off class="size-4" /></x-slot:icon>
        <x-slot:title>Analysis unavailable</x-slot:title>
        <x-slot:description>
            The detector could not be reached, so this product has <strong>not</strong> been cleared.
            Nothing below should be read as an all-clear.
        </x-slot:description>
    </april:alert>
@else
    {{-- Verdict header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-2xl font-bold tabular-nums" aria-hidden="true">{{ $report->riskScore }}</span>
            <div>
                <p class="text-sm font-semibold">{{ $report->verdict }}</p>
                <p class="text-xs text-muted-foreground">Misleading-claim risk, higher is worse</p>
            </div>
        </div>

        <april:badge variant="{{ $report->worstSeverity()?->value === 'high' ? 'destructive' : ($report->hasFindings() ? 'secondary' : 'outline') }}">
            {{ $report->findings === []
                ? 'No issues found'
                : count($report->findings).' '.Str::plural('finding', count($report->findings)) }}
        </april:badge>
    </div>

    @if ($report->summary !== '')
        <p class="mt-4 text-sm leading-relaxed text-foreground">{{ $report->summary }}</p>
    @endif

    @if ($report->hasFindings())
        <ul class="mt-4 space-y-3">
            @foreach ($report->findings as $index => $finding)
                <li class="rounded-lg border border-border p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h4 class="text-sm font-semibold">{{ $finding->title }}</h4>
                        <april:badge variant="none" class="{{ $finding->severity->classes() }}">
                            {{ $finding->severity->label() }}
                        </april:badge>
                    </div>

                    <p class="mt-2 text-sm text-muted-foreground">{{ $finding->detail }}</p>

                    @if ($finding->evidence !== '')
                        <p class="mt-2 rounded-md bg-muted/60 px-2.5 py-1.5 font-mono text-xs text-muted-foreground">
                            <span class="font-sans font-medium">On file:</span> {{ $finding->evidence }}
                        </p>
                    @endif

                    {{-- One-click escalation: the consumer does not choose a category,
                         it is already known. --}}
                    @auth
                        @if (auth()->user()->role === 'consumer')
                            <form action="{{ route('products.reportFinding', $food) }}" method="POST" class="mt-3">
                                @csrf
                                <input type="hidden" name="category" value="{{ $finding->category->value }}">
                                <input type="hidden" name="title" value="{{ $finding->title }}">
                                <input type="hidden" name="detail" value="{{ $finding->detail }}">
                                <april:button type="submit" size="sm" variant="outline">
                                    <x-lucide-flag class="size-4" />
                                    Report this
                                </april:button>
                                <span class="ml-2 text-xs text-muted-foreground">
                                    files it under &ldquo;{{ $finding->category->reportLabel() }}&rdquo;
                                </span>
                            </form>
                        @endif
                    @else
                        <p class="mt-3 text-xs text-muted-foreground">
                            <a href="{{ route('login') }}" class="text-primary underline underline-offset-2">Sign in</a>
                            to report this.
                        </p>
                    @endauth
                </li>
            @endforeach
        </ul>
    @else
        <div class="mt-4 flex items-start gap-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
            <x-lucide-shield-check class="mt-0.5 size-5 shrink-0 text-primary" />
            <p class="text-sm">
                The detector found no gap between what this product claims and what NutriTrace holds on
                record. That is a statement about the record, not a judgement of the product itself.
            </p>
        </div>
    @endif
@endif
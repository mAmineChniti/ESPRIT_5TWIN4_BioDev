@props(['food', 'report'])

{{--
    Let a reader say the detector got this product wrong.

    This is the counterpart to "Report this": reporting a finding escalates it
    against the product, while this challenges the analysis itself. It sits on
    the verdict card rather than inside a finding so it can also cover the case
    where the verdict is wrong but no individual finding is at fault.

    Rendered only when the analysis actually produced a verdict — there is
    nothing to dispute about a detector that failed to run.

    The fields are plain form controls rather than April's Alpine `select`,
    because the value is read by the server on submit: `april:select` keeps its
    value in a JS-bound hidden input, which posts nothing without JavaScript.
--}}
@php
    $suffix = 'analysis-dispute-'.$food->id;
    // The reporter's most recent report for this product, so a decision is
    // visible to them rather than filed into a queue they cannot read.
    $myReport = auth()->check()
        ? auth()->user()->analysisDisputes()->where('food_id', $food->id)->latest()->first()
        : null;
@endphp

<div class="mt-4 border-t border-border pt-4">
    @auth
        @if ($myReport && $myReport->isPending())
            <p class="flex items-center gap-2 text-sm text-muted-foreground">
                <x-lucide-clock class="size-4" />
                Your report about this analysis is awaiting review.
            </p>
        @else
            <april:alert-dialog>
                <x-slot:trigger>
                    <april:button type="button" variant="ghost" size="sm">
                        <x-lucide-message-square-warning class="size-4" />
                        Report a problem with this analysis
                    </april:button>
                </x-slot:trigger>

                <x-slot:content>
                    <april:alert-dialog-header>
                        <x-slot:title>What did the analysis get wrong?</x-slot:title>
                        <x-slot:description>
                            An administrator reviews every report. This challenges NutriTrace&rsquo;s
                            analysis of {{ $food->name }}; it is not a claim against the product.
                        </x-slot:description>
                    </april:alert-dialog-header>

                    <form method="POST" action="{{ route('products.analysis-disputes.store', $food) }}"
                        class="space-y-4">
                        @csrf

                        <div>
                            <april:label for="{{ $suffix }}-reason">What seems wrong</april:label>
                            <april:native-select id="{{ $suffix }}-reason" name="reason" required
                                class="mt-1"
                                aria-describedby="{{ $suffix }}-reason-error"
                                :aria-invalid="$errors->has('reason') ? 'true' : 'false'">
                                <option value="">Choose a reason…</option>
                                @foreach (App\Enums\AnalysisDisputeReason::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('reason') === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </april:native-select>
                            <x-input-error id="{{ $suffix }}-reason-error" :messages="$errors->get('reason')" />
                        </div>

                        @if ($report->hasFindings())
                            <div>
                                <april:label for="{{ $suffix }}-finding">Which part</april:label>
                                <april:native-select id="{{ $suffix }}-finding" name="finding_category"
                                    class="mt-1"
                                    aria-describedby="{{ $suffix }}-finding-hint">
                                    <option value="">The verdict as a whole</option>
                                    @foreach ($report->findings as $finding)
                                        <option value="{{ $finding->category->value }}"
                                            @selected(old('finding_category') === $finding->category->value)>{{ $finding->title }}</option>
                                    @endforeach
                                </april:native-select>
                                <p id="{{ $suffix }}-finding-hint" class="mt-2 text-sm text-muted-foreground">
                                    Leave this on the verdict if several findings share the same problem.
                                </p>
                            </div>
                        @endif

                        <div>
                            <april:label for="{{ $suffix }}-comment">Your comment</april:label>
                            <x-textarea-field id="{{ $suffix }}-comment" name="comment" rows="4"
                                class="mt-1"
                                required minlength="10" maxlength="2000"
                                placeholder="What the analysis should have said, and what in the record shows it."
                                :describedby="$suffix.'-comment-error'"
                                :value="old('comment')"
                                :aria-invalid="$errors->has('comment') ? 'true' : 'false'" />
                            <x-input-error id="{{ $suffix }}-comment-error" :messages="$errors->get('comment')" />
                        </div>

                        <april:alert-dialog-footer>
                            <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                            <april:alert-dialog-action x-on:click="$el.closest('form').requestSubmit()">
                                Send report
                            </april:alert-dialog-action>
                        </april:alert-dialog-footer>
                    </form>
                </x-slot:content>
            </april:alert-dialog>

            @if ($myReport && ! $myReport->isPending())
                {{-- The decision, so reporting is not a black hole. --}}
                @php [$variant, $tone] = $myReport->status->badgeClasses(); @endphp
                <april:alert variant="none" class="mt-3 border-border bg-muted/50">
                    <x-slot:icon><x-lucide-message-square-warning class="size-4" /></x-slot:icon>
                    <x-slot:title>Your report was {{ strtolower($myReport->status->label()) }}</x-slot:title>
                    <x-slot:description>
                        @if ($myReport->resolution_note)
                            {{ $myReport->resolution_note }}
                        @else
                            An administrator reviewed it on {{ $myReport->reviewed_at?->format('Y-m-d') }}.
                        @endif
                        <span class="mt-1 block">
                            <april:badge variant="{{ $variant }}" class="{{ $tone }}">{{ $myReport->status->label() }}</april:badge>
                        </span>
                    </x-slot:description>
                </april:alert>
            @endif
        @endif
    @else
        <p class="text-xs text-muted-foreground">
            <a href="{{ route('login') }}" class="text-primary underline underline-offset-2">Sign in</a>
            to tell us the analysis got this product wrong.
        </p>
    @endauth
</div>
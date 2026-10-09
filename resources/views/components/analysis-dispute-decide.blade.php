@props(['dispute'])

{{--
    One dialog that records either decision on an analysis report.

    Both outcomes are destructive in a different sense: dismissing tells a user
    their report was wrong, and upholding says NutriTrace's analysis is wrong.
    Each names its own consequence, and the dismissal note is required, so a
    reporter is never closed out without being told why.

    The field is in the same form as its submit button so a validation failure
    comes back with the note intact.
--}}
@php
    $suffix = 'dispute-'.$dispute->id;
    $product = $dispute->food?->name ?? 'the product';
@endphp

<april:alert-dialog>
    <x-slot:trigger>
        <april:button type="button" size="sm" variant="outline">Decide</april:button>
    </x-slot:trigger>

    <x-slot:content>
        <april:alert-dialog-header>
            <x-slot:title>Decide on this analysis report</x-slot:title>
            <x-slot:description>
                {{ $dispute->user?->name ?? 'Someone' }} says the analysis of
                <strong>{{ $product }}</strong> is wrong: &ldquo;{{ $dispute->reason->label() }}&rdquo;.
            </x-slot:description>
        </april:alert-dialog-header>

        <form method="POST" action="{{ route('admin.analysis-disputes.update', $dispute) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <april:label for="{{ $suffix }}-status">Decision</april:label>
                <april:native-select id="{{ $suffix }}-status" name="status" required
                    class="mt-1"
                    aria-describedby="{{ $suffix }}-status-hint">
                    @foreach (App\Enums\AnalysisDisputeStatus::cases() as $case)
                        @continue($case === App\Enums\AnalysisDisputeStatus::Pending)
                        <option value="{{ $case->value }}"
                            @selected(old('status', 'dismissed') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </april:native-select>
                <p id="{{ $suffix }}-status-hint" class="mt-2 text-sm text-muted-foreground">
                    Neither decision changes the product&rsquo;s score: the claim is about our analysis,
                    not about the product.
                </p>
            </div>

            <div>
                <april:label for="{{ $suffix }}-note">Note for the reporter</april:label>
                <x-textarea-field id="{{ $suffix }}-note" name="resolution_note" rows="3"
                    class="mt-1"
                    maxlength="2000"
                    placeholder="What you concluded, and what happens next."
                    :describedby="$suffix.'-note-error'"
                    :value="old('resolution_note')"
                    :aria-invalid="$errors->has('resolution_note') ? 'true' : 'false'" />
                <x-input-error id="{{ $suffix }}-note-error" :messages="$errors->get('resolution_note')" />
            </div>

            <april:alert-dialog-footer>
                <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                <april:alert-dialog-action x-on:click="$el.closest('form').requestSubmit()">
                    Record decision
                </april:alert-dialog-action>
            </april:alert-dialog-footer>
        </form>
    </x-slot:content>
</april:alert-dialog>
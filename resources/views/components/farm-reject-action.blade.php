@props(['action', 'farmName', 'farmId'])

{{--
    Rejecting a farm request, with the reason actually collected.

    The controller requires a rejection_reason, so a plain confirmation dialog
    could never succeed: it posted no reason, failed validation, and left the
    farm pending while telling the admin the field was required. The reason is
    part of the decision, so it belongs in the dialog beside the confirm button.

    The field is deliberately in the same form as the submit button rather than
    fetched on click, so a validation failure comes back with the reason intact.
--}}
@php
    $fieldId = 'farm-rejection-reason-'.$farmId;
    $errorId = $fieldId.'-error';
@endphp

<april:alert-dialog>
    <x-slot:trigger>
        <april:button type="button" variant="destructive" size="sm">
            <x-lucide-x class="mr-1 size-3.5" />
            Reject
        </april:button>
    </x-slot:trigger>

    <x-slot:content>
        <april:alert-dialog-header>
            <x-slot:title>Reject this request?</x-slot:title>
            <x-slot:description>
                {{ $farmName }} will not be published. The producer sees your reason, so say what
                needs fixing rather than only that the request failed.
            </x-slot:description>
        </april:alert-dialog-header>

        <form method="POST" action="{{ $action }}" class="space-y-2">
            @csrf
            @method('PATCH')

            <april:label for="{{ $fieldId }}">Reason for rejection</april:label>
            <april:textarea
                id="{{ $fieldId }}"
                name="rejection_reason"
                rows="3"
                maxlength="1000"
                required
                placeholder="What does the producer need to fix?"
                aria-describedby="{{ $errorId }}"
                @class(['border-destructive' => $errors->has('rejection_reason')])
                @if($errors->has('rejection_reason')) aria-invalid="true" @endif
            >{{ old('rejection_reason') }}</april:textarea>

            <x-input-error id="{{ $errorId }}" :messages="$errors->get('rejection_reason')" />

            <april:alert-dialog-footer class="mt-4">
                <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                {{-- April's action is a type="button", so submit explicitly. --}}
                <april:alert-dialog-action x-on:click="$el.closest('form').requestSubmit()">
                    Reject request
                </april:alert-dialog-action>
            </april:alert-dialog-footer>
        </form>
    </x-slot:content>
</april:alert-dialog>
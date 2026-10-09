@props([
    'action',
    'method' => 'DELETE',
    'label' => 'Confirm',
    'title',
    'description',
    'triggerVariant' => 'ghost',
    'triggerSize' => 'sm',
    'triggerClass' => '',
])

{{--
    A destructive action behind an explicit confirmation.

    Replaces window.confirm(): the dialog names the real consequence instead of
    asking a bare "are you sure?", and it is keyboard and screen-reader
    operable. April's alert-dialog-action is a type="button", so the form is
    submitted explicitly on click.
--}}
<april:alert-dialog>
    <x-slot:trigger>
        <april:button
            type="button"
            variant="{{ $triggerVariant }}"
            size="{{ $triggerSize }}"
            @class(['text-destructive hover:bg-destructive/10 hover:text-destructive' => $triggerVariant === 'ghost', $triggerClass])
        >
            {{ $slot }}
        </april:button>
    </x-slot:trigger>

    <x-slot:content>
        <april:alert-dialog-header>
            <x-slot:title>{{ $title }}</x-slot:title>
            <x-slot:description>{{ $description }}</x-slot:description>
        </april:alert-dialog-header>

        <april:alert-dialog-footer>
            <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>

            <form method="POST" action="{{ $action }}" class="contents" novalidate>
                @csrf
                @method($method)

                <april:alert-dialog-action x-on:click="$el.closest('form').requestSubmit()">
                    {{ $label }}
                </april:alert-dialog-action>
            </form>
        </april:alert-dialog-footer>
    </x-slot:content>
</april:alert-dialog>
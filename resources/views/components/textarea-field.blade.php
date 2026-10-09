@props([
    'id' => null,
    'name' => null,
    'rows' => 3,
    'value' => null,
    'describedby' => null,
])

{{--
    A textarea that keeps its value.

    April's <april:textarea> renders <textarea ...></textarea> with no
    {{ $slot }} in between, so anything written between its tags is discarded
    silently — no error, no warning. A textarea's value IS its inner text, so
    every <april:textarea>{{ old('x') }}</april:textarea> in this codebase came
    back blank: editing a farm blanked its description, editing a review blanked
    the body, and a failed rejection lost the reason.

    The classes are April's own, copied once here so they cannot drift.
--}}
<textarea
    data-slot="textarea"
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    rows="{{ $rows }}"
    {{ $attributes->twMerge([
        'flex min-h-[80px] rounded-md border bg-background px-3 py-2',
        'text-sm ring-offset-background placeholder:text-muted-foreground',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        'focus-visible:ring-offset-2 disabled:cursor-not-allowed',
        'disabled:opacity-50 border-input',
    ]) }}
    @if ($describedby) aria-describedby="{{ $describedby }}" @endif
>{{ $value }}</textarea>
@props(['messages', 'id' => null])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-destructive']) }}
        @if($id) id="{{ $id }}" @endif
        role="alert"
        aria-live="polite">
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

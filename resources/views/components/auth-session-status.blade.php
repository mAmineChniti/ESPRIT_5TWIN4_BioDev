@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-bio']) }}>
        {{ $status }}
    </div>
@endif

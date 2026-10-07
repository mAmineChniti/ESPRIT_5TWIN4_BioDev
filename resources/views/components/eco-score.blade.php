@props([
    'score' => null,
])

@php
    $raw = $score instanceof \App\Enums\EnvironmentalScore
        ? $score
        : (is_string($score) ? \App\Enums\EnvironmentalScore::tryFrom(strtoupper(trim($score))) : null);
@endphp

{{-- april:badge supplies the token-driven pill shape; a bare `rounded` would be a
     hardcoded 0.25rem and would not read --radius like every other component. --}}
@if ($raw)
    <april:badge variant="none" class="{{ $raw->badgeClasses() }}">
        {{ $raw->value }}
        <span class="font-normal opacity-80">{{ $raw->label() }}</span>
    </april:badge>
@else
    <april:badge variant="none" class="bg-muted text-muted-foreground">Not scored</april:badge>
@endif

@props([
    'score' => null,
])

@php
    $raw = $score instanceof \App\Enums\EnvironmentalScore
        ? $score
        : (is_string($score) ? \App\Enums\EnvironmentalScore::tryFrom(strtoupper(trim($score))) : null);
@endphp

@if ($raw)
    <span class="px-2 py-1 inline-flex items-center gap-1 text-xs font-semibold rounded {{ $raw->badgeClasses() }}">
        {{ $raw->value }}
        <span class="font-normal opacity-80">{{ $raw->label() }}</span>
    </span>
@else
    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded bg-muted text-muted-foreground">Not scored</span>
@endif
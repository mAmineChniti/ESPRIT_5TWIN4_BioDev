@props([
    'score' => null,
])

@php
    use App\Enums\EnvironmentalScore;

    $raw = $score instanceof EnvironmentalScore
        ? $score
        : (is_string($score) ? EnvironmentalScore::tryFrom(strtoupper(trim($score))) : null);
@endphp

{{-- rounded-md rather than bare rounded: the bare class is a hardcoded
     0.25rem that does not follow the theme's --radius scale. --}}
@if($raw)
    <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold {{ $raw->badgeClasses() }}">
        {{ $raw->value }}
        <span class="font-normal opacity-80">{{ $raw->label() }}</span>
    </span>
@else
    <span class="inline-flex rounded-md bg-muted px-2 py-1 text-xs font-semibold text-muted-foreground">Not scored</span>
@endif
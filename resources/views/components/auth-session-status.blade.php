@props(['status'])

{{-- April's alert already carries role="alert", so the status is announced
     instead of only being visible. --}}
@if ($status)
    <april:alert aria-live="polite" class="mb-4">
        <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
        <x-slot:description>{{ $status }}</x-slot:description>
    </april:alert>
@endif

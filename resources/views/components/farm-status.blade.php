@props(['status'])

{{--
    The farm's administrative state as a pill.

    variant="none" so April contributes only its shape and spacing while the
    colours come from FarmStatus::badgeClasses(). The theme has no warning
    channel, so "pending" borrows the muted secondary surface rather than an
    amber that would never follow the palette.
--}}
<april:badge variant="none" @class([$status->badgeClasses()])>
    {{ $status->label() }}
</april:badge>
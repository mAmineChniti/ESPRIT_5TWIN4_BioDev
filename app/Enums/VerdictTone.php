<?php

namespace App\Enums;

/**
 * How alarming a traceability verdict is.
 *
 * The colour for each tone lives here rather than in a Blade template so the
 * class names are literal strings Tailwind can see. Interpolating a class
 * name (`text-{{ $tone }}`) produces no CSS at all, and the tokens behind each
 * tone are the ones declared in resources/css/app.css.
 */
enum VerdictTone: string
{
    /** Fully traced, nothing upheld against it. */
    case Low = 'low';

    /** Recorded but incomplete, or evidence missing. */
    case Medium = 'medium';

    /** One or more upheld greenwashing reports. */
    case High = 'high';

    /**
     * Text colour, for rings, chart lines and legends.
     */
    public function textClasses(): string
    {
        return match ($this) {
            self::Low => 'text-primary',
            self::Medium => 'text-secondary-foreground',
            self::High => 'text-destructive',
        };
    }

    /**
     * Solid fill, for dots and swatches.
     */
    public function fillClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-primary',
            self::Medium => 'bg-secondary',
            self::High => 'bg-destructive',
        };
    }
}

<?php

namespace App\Enums;

/**
 * How serious a detected problem is.
 *
 * The detector assigns the severity; this enum only names and renders it, and
 * tolerates whatever the model returns so an unexpected value degrades to the
 * least alarming reading rather than throwing in front of a consumer.
 */
enum FindingSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Minor',
            self::Medium => 'Concern',
            self::High => 'Serious',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 5,
            self::High => 15,
        };
    }

    /**
     * Theme token classes for the finding pill.
     */
    public function classes(): string
    {
        return match ($this) {
            self::Low => 'bg-secondary text-secondary-foreground',
            self::Medium => 'bg-primary/10 text-primary',
            self::High => 'bg-destructive/10 text-destructive',
        };
    }

    public static function tryFromValue(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom(strtolower($value)) ?? self::Low) : self::Low;
    }
}

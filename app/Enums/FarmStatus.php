<?php

namespace App\Enums;

/**
 * The administrative state of a farm.
 *
 * A farm is only public once an admin has approved it. Pending and rejected
 * farms stay inside the back office, so the published pages must filter on
 * Approved rather than on "not rejected".
 */
enum FarmStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * The label shown in the back office.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Theme token classes used to render the status pill.
     *
     * April has no warning channel, so "pending" borrows the muted surface
     * rather than inventing an amber that would not follow the palette.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-secondary text-secondary-foreground',
            self::Approved => 'bg-primary/10 text-primary',
            self::Rejected => 'bg-destructive/10 text-destructive',
        };
    }

    /**
     * The value used for the statistic keys in the farms dashboard.
     */
    public function statKey(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Approved => 'approved',
            self::Rejected => 'rejected',
        };
    }

    /**
     * The allowed statuses as a plain array, for validation.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

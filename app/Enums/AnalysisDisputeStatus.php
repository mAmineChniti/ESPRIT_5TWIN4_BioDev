<?php

namespace App\Enums;

/**
 * Where an analysis dispute sits in the moderation queue.
 *
 * Upheld means the reporter was right and the analysis is wrong, which is the
 * opposite way round from a greenwashing report: an upheld dispute is a defect
 * in NutriTrace, not a mark against the product.
 */
enum AnalysisDisputeStatus: string
{
    case Pending = 'pending';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting review',
            self::Upheld => 'Analysis is wrong',
            self::Dismissed => 'Analysis stands',
        };
    }

    /**
     * April has no success variant, so the resolved states are mapped to the
     * tokens that exist. Literals only, so Tailwind can see them.
     *
     * @return array{string, string}
     */
    public function badgeClasses(): array
    {
        return match ($this) {
            self::Pending => ['outline', 'text-muted-foreground'],
            self::Upheld => ['none', 'bg-destructive/10 text-destructive'],
            self::Dismissed => ['secondary', 'text-secondary-foreground'],
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}

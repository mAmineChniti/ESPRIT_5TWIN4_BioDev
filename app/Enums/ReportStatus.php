<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting review',
            self::Upheld => 'Upheld',
            self::Dismissed => 'Dismissed',
        };
    }

    /**
     * Only upheld reports count against a product's trust score.
     */
    public function countsAgainstTrust(): bool
    {
        return $this === self::Upheld;
    }
}

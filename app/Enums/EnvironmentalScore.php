<?php

namespace App\Enums;

enum EnvironmentalScore: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    /**
     * Human readable impact description shown next to the grade.
     */
    public function label(): string
    {
        return match ($this) {
            self::A => 'Very low impact',
            self::B => 'Low impact',
            self::C => 'Medium impact',
            self::D => 'High impact',
            self::E => 'Very high impact',
        };
    }

    /**
     * Theme token classes used to render the badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::A, self::B => 'bg-primary text-primary-foreground',
            self::C => 'bg-secondary text-secondary-foreground',
            self::D, self::E => 'bg-destructive text-destructive-foreground',
        };
    }

    /**
     * The allowed grades as a plain array, for validation and factories.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

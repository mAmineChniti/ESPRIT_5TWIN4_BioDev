<?php

namespace App\Services;

use App\Enums\TransportMode;

final class CarbonFootprintCalculator
{
    /**
     * Footprint in kg CO2e: tonnes x kilometres x emission factor.
     */
    public static function calculate(float $weightKg, float $distanceKm, TransportMode $mode): float
    {
        return round(($weightKg / 1000) * $distanceKm * $mode->emissionFactor(), 2);
    }

    /**
     * The footprint of the same load for every mode, lowest first.
     *
     * @return list<array{mode: TransportMode, kg: float}>
     */
    public static function compare(float $weightKg, float $distanceKm): array
    {
        $rows = array_map(
            fn (TransportMode $mode): array => [
                'mode' => $mode,
                'kg' => self::calculate($weightKg, $distanceKm, $mode),
            ],
            TransportMode::cases()
        );

        usort($rows, fn (array $a, array $b): int => $a['kg'] <=> $b['kg']);

        return $rows;
    }
}

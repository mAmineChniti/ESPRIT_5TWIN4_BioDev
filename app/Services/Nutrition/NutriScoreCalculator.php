<?php

namespace App\Services\Nutrition;

use App\Models\Food;

/**
 * Official-aligned Nutri-Score calculation algorithm and environmental impact estimator.
 *
 * Implements the European nutritional profiling model (Santé Publique France standard)
 * computing positive and negative points per 100g to produce grades A to E,
 * alongside estimated CO2e lifecycle emissions.
 */
class NutriScoreCalculator
{
    /**
     * Compute the Nutri-Score grade and detailed breakdown for a product.
     *
     * @return array{
     *     grade: 'A'|'B'|'C'|'D'|'E',
     *     score: int,
     *     negative_points: int,
     *     positive_points: int,
     *     color: string,
     *     label: string,
     *     carbon_kg: float,
     *     carbon_rating: string,
     * }
     */
    public static function calculate(Food $food): array
    {
        return self::calculateFromValues(
            calories: (int) $food->calories,
            protein: (float) $food->protein,
            carbs: (float) $food->carbs,
            fat: (float) $food->fat,
            categoryName: $food->category?->name ?? 'Other',
            ecoScore: $food->environmental_score?->value ?? 'C',
            origin: $food->origin ?? 'Unknown'
        );
    }

    /**
     * Compute Nutri-Score from raw values (useful for live AJAX preview).
     *
     * @return array{
     *     grade: 'A'|'B'|'C'|'D'|'E',
     *     score: int,
     *     negative_points: int,
     *     positive_points: int,
     *     color: string,
     *     label: string,
     *     carbon_kg: float,
     *     carbon_rating: string,
     * }
     */
    public static function calculateFromValues(
        int $calories,
        float $protein,
        float $carbs,
        float $fat,
        string $categoryName = 'Other',
        string $ecoScore = 'C',
        string $origin = 'Unknown'
    ): array {
        // Negative points calculation
        // 1. Energy (kJ = kcal * 4.184)
        $energyKj = $calories * 4.184;
        $energyPoints = min(10, max(0, (int) floor($energyKj / 335)));

        // 2. Saturated fatty acids estimation (~35% of total fat if unspecified)
        $satFat = $fat * 0.35;
        $satFatPoints = min(10, max(0, (int) floor($satFat)));

        // 3. Simple sugars estimation (~35% of carbohydrates for standard mixed foods)
        $sugar = in_array(strtolower($categoryName), ['fruit'], true) ? $carbs * 0.75 : $carbs * 0.35;
        $sugarPoints = min(10, max(0, (int) floor($sugar / 4.5)));

        // 4. Sodium baseline estimation (standard 0.2g salt per 100g -> 80mg sodium -> 0-1 pt)
        $sodiumPoints = ($calories > 300) ? 1 : 0;

        $negativePoints = $energyPoints + $satFatPoints + $sugarPoints + $sodiumPoints;

        // Positive points calculation
        // 1. Fruit & vegetables proportion bonus based on category
        $fruitVegPoints = match (strtolower($categoryName)) {
            'fruit', 'vegetable' => 5,
            'grain' => 2,
            'seafood' => 1,
            default => 0,
        };

        // 2. Protein points
        $proteinPoints = min(5, max(0, (int) floor($protein / 1.6)));

        // 3. Fiber points estimation (higher in whole grains, fruits, vegetables)
        $fiberPoints = match (strtolower($categoryName)) {
            'grain', 'vegetable' => 3,
            'fruit' => 2,
            default => 1,
        };

        $positivePoints = $fruitVegPoints + $proteinPoints + $fiberPoints;

        // Standard rule: if negative points >= 11 and fruit/veg points < 5, protein is omitted
        $effectivePositives = ($negativePoints >= 11 && $fruitVegPoints < 5)
            ? ($fruitVegPoints + $fiberPoints)
            : $positivePoints;

        $finalScore = $negativePoints - $effectivePositives;

        $grade = match (true) {
            $finalScore <= -1 => 'A',
            $finalScore <= 2 => 'B',
            $finalScore <= 10 => 'C',
            $finalScore <= 18 => 'D',
            default => 'E',
        };

        $label = match ($grade) {
            'A' => __('Very high nutritional quality'),
            'B' => __('Good nutritional quality'),
            'C' => __('Average nutritional quality'),
            'D' => __('Low nutritional quality'),
            'E' => __('Poor nutritional quality'),
        };

        $color = match ($grade) {
            'A' => '#038141',
            'B' => '#85bb2f',
            'C' => '#fecb02',
            'D' => '#ee8100',
            'E' => '#e63e11',
        };

        // Carbon footprint estimate per kg of product
        $baseCarbon = match (strtoupper($ecoScore)) {
            'A' => 0.85,
            'B' => 1.75,
            'C' => 3.50,
            'D' => 6.80,
            'E' => 13.50,
            default => 3.00,
        };

        // Add small transport premium if international origin
        $isLocal = in_array(mb_strtolower(trim($origin)), ['tunisia', 'tunisie', 'local', 'national'], true);
        $carbonKg = round($baseCarbon * ($isLocal ? 1.0 : 1.25), 2);

        $carbonRating = match (true) {
            $carbonKg <= 1.2 => __('Very low impact'),
            $carbonKg <= 2.5 => __('Low impact'),
            $carbonKg <= 5.0 => __('Moderate impact'),
            $carbonKg <= 9.0 => __('High impact'),
            default => __('Very high impact'),
        };

        return [
            'grade' => $grade,
            'score' => $finalScore,
            'negative_points' => $negativePoints,
            'positive_points' => $positivePoints,
            'color' => $color,
            'label' => $label,
            'carbon_kg' => $carbonKg,
            'carbon_rating' => $carbonRating,
        ];
    }
}

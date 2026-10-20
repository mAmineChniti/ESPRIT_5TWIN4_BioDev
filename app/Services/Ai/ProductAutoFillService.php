<?php

namespace App\Services\Ai;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI-powered assistant for automatically filling and predicting product details.
 *
 * Infers category, realistic macronutrients, environmental score, and an
 * eco-responsible marketing summary from a product name or short description.
 *
 * Uses AiClient when configured; falls back gracefully to deterministic food
 * knowledge rules so demo and offline environments remain 100% operational.
 */
class ProductAutoFillService
{
    public function __construct(
        private readonly AiClient $ai,
    ) {}

    /**
     * Generate product attribute suggestions from a product name.
     *
     * @return array{
     *     category_id: int|null,
     *     category_name: string,
     *     origin: string,
     *     environmental_score: string,
     *     calories: int,
     *     protein: float,
     *     carbs: float,
     *     fat: float,
     *     explanation: string,
     *     source: 'ai'|'heuristic',
     * }
     */
    public function suggest(string $productName, ?string $userOrigin = null): array
    {
        $categories = Category::orderBy('name')->pluck('id', 'name')->all();
        $categoryNames = array_keys($categories);

        if ($this->ai->isConfigured()) {
            try {
                return $this->suggestWithAi($productName, $userOrigin, $categories, $categoryNames);
            } catch (Throwable $e) {
                Log::warning('AI product suggestion failed, falling back to heuristic engine.', [
                    'product' => $productName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->suggestWithHeuristics($productName, $userOrigin, $categories);
    }

    /**
     * @param  array<string, int>  $categories
     * @param  list<string>  $categoryNames
     * @return array{
     *     category_id: int|null,
     *     category_name: string,
     *     origin: string,
     *     environmental_score: string,
     *     calories: int,
     *     protein: float,
     *     carbs: float,
     *     fat: float,
     *     explanation: string,
     *     source: 'ai',
     * }
     */
    private function suggestWithAi(
        string $productName,
        ?string $userOrigin,
        array $categories,
        array $categoryNames
    ): array {
        $schema = [
            'type' => 'object',
            'required' => [
                'category_name',
                'origin',
                'environmental_score',
                'calories',
                'protein',
                'carbs',
                'fat',
                'explanation',
            ],
            'properties' => [
                'category_name' => [
                    'type' => 'string',
                    'description' => 'The best matching category from: '.implode(', ', $categoryNames),
                ],
                'origin' => [
                    'type' => 'string',
                    'description' => 'Estimated country or agricultural region of origin',
                ],
                'environmental_score' => [
                    'type' => 'string',
                    'enum' => ['A', 'B', 'C', 'D', 'E'],
                    'description' => 'Estimated eco-score letter (A is best, E is worst)',
                ],
                'calories' => [
                    'type' => 'integer',
                    'description' => 'Estimated energy in kcal per 100g',
                ],
                'protein' => [
                    'type' => 'number',
                    'description' => 'Estimated protein in grams per 100g',
                ],
                'carbs' => [
                    'type' => 'number',
                    'description' => 'Estimated carbohydrates in grams per 100g',
                ],
                'fat' => [
                    'type' => 'number',
                    'description' => 'Estimated fat in grams per 100g',
                ],
                'explanation' => [
                    'type' => 'string',
                    'description' => 'Short 1-2 sentence nutritional and environmental explanation for the producer',
                ],
            ],
            'additionalProperties' => false,
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert food scientist and agronomist for NutriTrace. Given a food product name, output realistic nutritional values per 100g, pick the closest existing category, assign an environmental score (A to E) based on life cycle impact, and suggest an origin if provided or typical for that specialty.',
            ],
            [
                'role' => 'user',
                'content' => "Product name: \"{$productName}\"".($userOrigin ? " (Preferred origin: {$userOrigin})" : '')."\nAvailable categories: ".implode(', ', $categoryNames),
            ],
        ];

        /** @var array{category_name: string, origin: string, environmental_score: string, calories: int, protein: float, carbs: float, fat: float, explanation: string} $data */
        $data = $this->ai->json($messages, $schema, 600);

        $matchedCategory = $this->matchCategory($data['category_name'], $categories);

        return [
            'category_id' => $matchedCategory['id'],
            'category_name' => $matchedCategory['name'],
            'origin' => trim($data['origin']) ?: ($userOrigin ?? 'Tunisia'),
            'environmental_score' => in_array($data['environmental_score'], ['A', 'B', 'C', 'D', 'E'], true) ? $data['environmental_score'] : 'B',
            'calories' => max(0, (int) $data['calories']),
            'protein' => round(max(0, (float) $data['protein']), 2),
            'carbs' => round(max(0, (float) $data['carbs']), 2),
            'fat' => round(max(0, (float) $data['fat']), 2),
            'explanation' => trim($data['explanation']),
            'source' => 'ai',
        ];
    }

    /**
     * Deterministic heuristic fallback when AI API is unavailable.
     *
     * @param  array<string, int>  $categories
     * @return array{
     *     category_id: int|null,
     *     category_name: string,
     *     origin: string,
     *     environmental_score: string,
     *     calories: int,
     *     protein: float,
     *     carbs: float,
     *     fat: float,
     *     explanation: string,
     *     source: 'heuristic',
     * }
     */
    private function suggestWithHeuristics(string $productName, ?string $userOrigin, array $categories): array
    {
        $normalized = mb_strtolower(trim($productName));

        // Nutritional & eco profiles by food patterns
        $profiles = [
            'huile' => ['category' => 'Other', 'calories' => 884, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 100.0, 'score' => 'B', 'desc' => 'Pure vegetable fat profile with low water content.'],
            'olive' => ['category' => 'Fruit', 'calories' => 115, 'protein' => 0.8, 'carbs' => 6.3, 'fat' => 10.7, 'score' => 'A', 'desc' => 'High in unsaturated fats and antioxidants; low environmental footprint.'],
            'miel' => ['category' => 'Other', 'calories' => 304, 'protein' => 0.3, 'carbs' => 82.4, 'fat' => 0.0, 'score' => 'A', 'desc' => 'Natural sugar concentrate produced by beekeeping, minimal processing.'],
            'lait' => ['category' => 'Dairy', 'calories' => 64, 'protein' => 3.3, 'carbs' => 4.8, 'fat' => 3.6, 'score' => 'C', 'desc' => 'Dairy product with balanced protein and calcium content.'],
            'fromage' => ['category' => 'Dairy', 'calories' => 380, 'protein' => 25.0, 'carbs' => 1.5, 'fat' => 31.0, 'score' => 'D', 'desc' => 'Concentrated dairy proteins and lipids.'],
            'yaourt' => ['category' => 'Dairy', 'calories' => 61, 'protein' => 3.5, 'carbs' => 4.7, 'fat' => 3.3, 'score' => 'B', 'desc' => 'Fermented dairy rich in probiotics.'],
            'tomate' => ['category' => 'Vegetable', 'calories' => 18, 'protein' => 0.9, 'carbs' => 3.9, 'fat' => 0.2, 'score' => 'A', 'desc' => 'Fresh vegetable with high water and lycopene content.'],
            'pomme' => ['category' => 'Fruit', 'calories' => 52, 'protein' => 0.3, 'carbs' => 13.8, 'fat' => 0.2, 'score' => 'A', 'desc' => 'Fresh fruit rich in dietary fiber and pectin.'],
            'orange' => ['category' => 'Fruit', 'calories' => 47, 'protein' => 0.9, 'carbs' => 11.8, 'fat' => 0.1, 'score' => 'A', 'desc' => 'Citrus fruit with high vitamin C and hydration.'],
            'dattes' => ['category' => 'Fruit', 'calories' => 282, 'protein' => 2.5, 'carbs' => 75.0, 'fat' => 0.4, 'score' => 'A', 'desc' => 'Nutrient-dense dried fruit with high potassium and fast-acting energy.'],
            'pain' => ['category' => 'Grain', 'calories' => 265, 'protein' => 9.0, 'carbs' => 49.0, 'fat' => 3.2, 'score' => 'B', 'desc' => 'Cereal staple providing complex carbohydrates.'],
            'pates' => ['category' => 'Grain', 'calories' => 350, 'protein' => 12.5, 'carbs' => 71.0, 'fat' => 1.5, 'score' => 'B', 'desc' => 'Semolina/grain derivative high in slow-release carbohydrates.'],
            'couscous' => ['category' => 'Grain', 'calories' => 360, 'protein' => 12.0, 'carbs' => 73.0, 'fat' => 1.1, 'score' => 'B', 'desc' => 'Durum wheat semolina staple.'],
            'poulet' => ['category' => 'Protein', 'calories' => 165, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'score' => 'C', 'desc' => 'Lean poultry protein with low saturated fat.'],
            'boeuf' => ['category' => 'Protein', 'calories' => 250, 'protein' => 26.0, 'carbs' => 0.0, 'fat' => 15.0, 'score' => 'E', 'desc' => 'Red meat high in iron and protein; higher environmental impact.'],
            'poisson' => ['category' => 'Seafood', 'calories' => 120, 'protein' => 22.0, 'carbs' => 0.0, 'fat' => 3.0, 'score' => 'B', 'desc' => 'Seafood source rich in omega-3 and essential amino acids.'],
            'thon' => ['category' => 'Seafood', 'calories' => 130, 'protein' => 28.0, 'carbs' => 0.0, 'fat' => 1.0, 'score' => 'C', 'desc' => 'Pelagic fish rich in lean marine protein.'],
        ];

        $matched = null;
        foreach ($profiles as $keyword => $data) {
            if (str_contains($normalized, $keyword)) {
                $matched = $data;
                break;
            }
        }

        if ($matched === null) {
            $matched = [
                'category' => 'Other',
                'calories' => 150,
                'protein' => 5.0,
                'carbs' => 20.0,
                'fat' => 4.0,
                'score' => 'B',
                'desc' => 'Estimated standard profile for artisan agricultural food.',
            ];
        }

        $matchedCategory = $this->matchCategory($matched['category'], $categories);

        return [
            'category_id' => $matchedCategory['id'],
            'category_name' => $matchedCategory['name'],
            'origin' => $userOrigin ?: 'Tunisia',
            'environmental_score' => $matched['score'],
            'calories' => $matched['calories'],
            'protein' => $matched['protein'],
            'carbs' => $matched['carbs'],
            'fat' => $matched['fat'],
            'explanation' => $matched['desc'],
            'source' => 'heuristic',
        ];
    }

    /**
     * @param  array<string, int>  $categories
     * @return array{id: int|null, name: string}
     */
    private function matchCategory(string $suggestedName, array $categories): array
    {
        $normalizedSuggest = mb_strtolower(trim($suggestedName));

        foreach ($categories as $name => $id) {
            if (mb_strtolower($name) === $normalizedSuggest || str_contains($normalizedSuggest, mb_strtolower($name))) {
                return ['id' => $id, 'name' => $name];
            }
        }

        $fallbackId = $categories['Other'] ?? array_values($categories)[0] ?? null;

        return [
            'id' => $fallbackId,
            'name' => array_search($fallbackId, $categories, true) ?: 'Other',
        ];
    }
}

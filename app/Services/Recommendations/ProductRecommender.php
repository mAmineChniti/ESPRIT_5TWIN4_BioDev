<?php

namespace App\Services\Recommendations;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Recommends products a consumer can feel better about.
 *
 * Ranking is deliberately computed, never generated: a model picking
 * "responsible products" would have no basis to compare transparency scores and
 * would happily recommend the worst thing in the catalog. The ordering comes
 * from the record; the model only writes the one-line reason afterwards, and if
 * it is unavailable the recommendation still ships with a computed reason.
 */
class ProductRecommender
{
    /** Grade ordering, best first. */
    private const GRADE_RANK = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4];

    public function __construct(private readonly AiClient $ai) {}

    /**
     * Better-traced alternatives to a product, same shelf.
     */
    public function alternativesTo(Food $food, int $limit = 4): Collection
    {
        $gradeRank = self::GRADE_RANK[$food->environmental_score?->value ?? 'E'];

        $candidates = Food::query()
            ->with(['category', 'certifications', 'transitions'])
            ->whereKeyNot($food->id)
            ->when(
                $food->category_id,
                fn ($query) => $query->where('category_id', $food->category_id)
            )
            ->whereNotNull('environmental_score')
            ->get()
            ->filter(function (Food $candidate) use ($food, $gradeRank): bool {
                $rank = self::GRADE_RANK[$candidate->environmental_score->value];

                // Only recommend something at least as good on both axes.
                return $rank <= $gradeRank && $candidate->transparencyScore() >= $food->transparencyScore();
            })
            ->sortByDesc(fn (Food $c): array => [
                $c->transparencyScore(),
                -self::GRADE_RANK[$c->environmental_score->value],
            ])
            ->take($limit);

        return $this->explain($candidates, $food);
    }

    /**
     * Products worth trying given what this consumer actually eats.
     *
     * Scoped to the categories they already log into, so the list is reachable
     * rather than a generic "eat more vegetables" lecture.
     */
    public function recommendedFor(User $user, int $limit = 6): Collection
    {
        $eatenIds = $user->meals()
            ->join('meal_food', 'meal_food.meal_id', '=', 'meals.id')
            ->pluck('meal_food.food_id')
            ->unique();

        $categoryIds = Food::whereIn('id', $eatenIds)->whereNotNull('category_id')->pluck('category_id')->unique();

        if ($categoryIds->isEmpty()) {
            // Nothing logged yet: the whole catalog, best evidenced first.
            $candidates = Food::with(['category', 'certifications', 'transitions'])
                ->whereNotNull('environmental_score')
                ->get()
                ->sortByDesc(fn (Food $c): array => [$c->transparencyScore(), -self::GRADE_RANK[$c->environmental_score->value]])
                ->take($limit);

            return $this->explain($candidates, null);
        }

        $candidates = Food::query()
            ->with(['category', 'certifications', 'transitions'])
            ->whereIn('category_id', $categoryIds)
            ->whereNotNull('environmental_score')
            ->whereNotIn('id', $eatenIds)
            ->get()
            ->sortByDesc(fn (Food $c): array => [$c->transparencyScore(), -self::GRADE_RANK[$c->environmental_score->value]])
            ->take($limit);

        return $this->explain($candidates, null);
    }

    /**
     * @param  Collection<int, Food>  $candidates
     * @return Collection<int, Recommendation>
     */
    private function explain(Collection $candidates, ?Food $comparedTo): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        $reasons = $this->modelReasons($candidates, $comparedTo);

        return $candidates->map(fn (Food $food): Recommendation => new Recommendation(
            food: $food,
            why: $reasons[$food->id] ?? $this->computedReason($food, $comparedTo),
            transparencyScore: $food->transparencyScore(),
            grade: $food->environmental_score?->value,
            reason: $food->trustVerdict()['message'],
        ));
    }

    /**
     * Let the model phrase the comparison; never let it choose the products.
     *
     * @param  Collection<int, Food>  $candidates
     * @return array<int, string>
     */
    private function modelReasons(Collection $candidates, ?Food $comparedTo): array
    {
        try {
            $payload = $this->ai->json(
                [
                    [
                        'role' => 'system',
                        'content' => implode("\n", [
                            'You write one short sentence per product explaining why it is a responsible choice.',
                            'You are given products that have ALREADY been selected and ranked by their',
                            'recorded evidence. Your only job is to describe, for a consumer, what makes',
                            'each one well evidenced.',
                            '',
                            'Rules:',
                            '- Never recommend or reject anything. The list is fixed.',
                            '- Say only what the record shows: traceable supply chain, certifications on file,',
                            '  environmental grade, how far it travelled.',
                            '- Never give health, dietary or medical advice.',
                            '- One sentence each, plain language, no marketing tone.',
                        ]),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'compared_to' => $comparedTo?->only(['name', 'origin']),
                            'products' => $candidates->map(fn (Food $f): array => [
                                'id' => $f->id,
                                'name' => $f->name,
                                'origin' => $f->origin,
                                'category' => $f->category?->name,
                                'grade' => $f->environmental_score?->value,
                                'transparency_score' => $f->transparencyScore(),
                                'supply_chain_complete' => $f->hasFullChain(),
                                'certifications' => $f->certifications->pluck('name')->all(),
                            ])->all(),
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ],
                ],
                [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['reasons'],
                    'properties' => [
                        'reasons' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['id', 'why'],
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'why' => ['type' => 'string', 'description' => 'One sentence.'],
                                ],
                            ],
                        ],
                    ],
                ],
                maxTokens: 2500,
            );
        } catch (AiException $e) {
            Log::info('Recommendation reasons fell back to computed wording.', ['reason' => $e->getMessage()]);

            return [];
        }

        $reasons = [];

        foreach (data_get($payload, 'reasons', []) ?: [] as $row) {
            if (is_array($row) && isset($row['id'], $row['why']) && is_scalar($row['why'])) {
                $reasons[(int) $row['id']] = trim((string) $row['why']);
            }
        }

        return $reasons;
    }

    /**
     * The wording used when the model is unavailable, derived from the record.
     */
    private function computedReason(Food $food, ?Food $comparedTo): string
    {
        $parts = [];

        if ($food->hasFullChain()) {
            $parts[] = 'every stage of its supply chain is recorded';
        } else {
            $parts[] = sprintf('%d of %d supply chain stages recorded', $food->recordedStageCount(), Stage::total());
        }

        if ($food->certifications->isNotEmpty()) {
            $parts[] = 'certified ('.$food->certifications->pluck('name')->implode(', ').')';
        }

        if ($food->environmental_score !== null) {
            $parts[] = 'graded '.$food->environmental_score->value.' ('.$food->environmental_score->label().')';
        }

        return ucfirst(implode(', ', $parts)).'.';
    }
}

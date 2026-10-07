<?php

namespace App\Services\Greenwashing;

use App\Enums\FindingCategory;
use App\Models\Food;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI greenwashing detector.
 *
 * The model is given the product's full record — the claims it makes and the
 * evidence actually on file — and asked to find the gaps between them. It does
 * not invent facts: every finding must quote the record field that triggered
 * it, and the schema enforces that.
 *
 * Results are cached because the same product is analysed repeatedly (product
 * page, consumer space, recommendations) and the record only changes when the
 * supply chain does.
 */
class GreenwashingDetector
{
    /**
     * Generous, because reasoning models (gpt-oss, the Gemini thinking models)
     * spend part of this budget reasoning before the JSON document starts. Too
     * small a limit truncates the reply mid-object and the parse fails.
     */
    private const MAX_OUTPUT_TOKENS = 3000;

    public function __construct(private readonly AiClient $ai) {}

    /**
     * Analyse a product, returning a report the page can always render.
     *
     * Never throws: an unreachable provider becomes an honest failure state,
     * because a consumer is better served by "we could not check this" than by
     * a 500 or, worse, a fabricated clean bill of health.
     */
    public function analyze(Food $food): AnalysisReport
    {
        $product = FoodSummary::fromFood($food);
        $cacheKey = $this->cacheKey($food);

        // Cached as a plain array, not the object: a serialising cache driver
        // would hand back an incomplete instance on the next request.
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return AnalysisReport::fromArray($cached);
        }

        try {
            $report = AnalysisReport::fromModelResponse(
                $this->ai->json(
                    $this->messages(ProductRecord::fromFood($food)),
                    $this->schema(),
                ),
                $product,
            );
        } catch (AiException $e) {
            Log::warning('Greenwashing analysis failed.', ['food_id' => $food->id, 'reason' => $e->getMessage()]);

            return AnalysisReport::unavailable($product, $e->getMessage());
        }

        if (! $report->hasFailed()) {
            Cache::put($cacheKey, $report->toArray(), now()->addHours(12));
        }

        return $report;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function messages(ProductRecord $record): array
    {
        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are the greenwashing auditor for NutriTrace, a food traceability platform.',
                    'You receive a product record with two sections: "claims" (what the product asserts about',
                    'itself) and "evidence" (what NutriTrace has actually recorded and can verify).',
                    '',
                    'Your job is to find the gap between the two.',
                    '',
                    'Rules:',
                    '- Only report a problem visible in the record. Never assume or invent facts.',
                    '- A field reading "not recorded" means nothing is on file; that is itself a finding.',
                    '- An eco claim in the name or category with no matching certification is a finding.',
                    '- A certification past its validity date proves nothing.',
                    '- A certification with no issuer or certificate number cannot be checked.',
                    '- An incomplete supply chain means the journey cannot be traced end to end.',
                    '- An upheld greenwashing report is the strongest signal available.',
                    '- Do not report the same problem twice.',
                    '- If nothing is wrong, return an empty findings array. Never invent a concern just to',
                    '  have something to say.',
                    '',
                    'For each finding give a short title, one or two sentences of plain language for a',
                    'consumer, a severity, and the exact record field that triggered it.',
                    '',
                    'Severity is "high" when the product actively claims something untrue or unsupported,',
                    '"medium" when a meaningful gap exists, and "low" for a small omission.',
                    '',
                    'Also write a "summary" of at most three sentences and a "verdict" of two or three words.',
                    '',
                    'Categories, each mapping to a reason a consumer can report it under:',
                    ...array_map(
                        static fn (FindingCategory $c): string => "- {$c->value} = {$c->reason()->label()}",
                        FindingCategory::cases()
                    ),
                ]),
            ],
            [
                'role' => 'user',
                'content' => 'Product record:'.PHP_EOL.json_encode(
                    $record->toArray(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
        ];
    }

    /**
     * JSON Schema the reply must satisfy.
     *
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'verdict', 'findings'],
            'properties' => [
                'summary' => [
                    'type' => 'string',
                    'description' => 'At most three plain sentences for the consumer.',
                ],
                'verdict' => [
                    'type' => 'string',
                    'description' => 'Two or three words, e.g. "No misleading claims detected".',
                ],
                'findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['category', 'severity', 'title', 'detail', 'evidence'],
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'enum' => FindingCategory::values(),
                            ],
                            'severity' => [
                                'type' => 'string',
                                'enum' => ['low', 'medium', 'high'],
                            ],
                            'title' => ['type' => 'string', 'description' => 'Max 8 words.'],
                            'detail' => ['type' => 'string', 'description' => 'One or two plain sentences.'],
                            'evidence' => [
                                'type' => 'string',
                                'description' => 'The record field that shows the problem, quoted or named.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Keyed on the record's own contents, so editing the product or recording a
     * new supply chain step invalidates the cached analysis automatically.
     */
    private function cacheKey(Food $food): string
    {
        $food->loadMissing(['certifications', 'transitions', 'reports', 'producer', 'category']);

        return 'greenwashing:'.sha1(json_encode([
            'food' => $food->only(['id', 'name', 'origin', 'environmental_score', 'calories', 'protein', 'carbs', 'fat']),
            'category' => $food->category?->name,
            'producer' => $food->producer?->only(['id', 'name', 'role']),
            'certifications' => $food->certifications->map->only(['id', 'name', 'issuer', 'certificate_number', 'valid_until'])->all(),
            'chain' => $food->transitions->map->only(['id', 'to_stage', 'occurred_at'])->all(),
            'reports' => $food->reports->map->only(['id', 'status', 'reason'])->all(),
            // scans_count is deliberately excluded: a scan changes nothing a
            // consumer is asked to judge, and keying on it re-billed the model
            // every single time someone looked up a product.
        ]));
    }
}

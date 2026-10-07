<?php

namespace App\Services\Greenwashing;

use App\Enums\FindingCategory;
use App\Enums\FindingSeverity;

/**
 * What the detector concluded about one product.
 *
 * Findings are ordered worst-first so the most serious problem is the one a
 * consumer reads first, and the score is derived from the findings themselves
 * rather than invented separately — that way the number and the reasoning can
 * never drift apart.
 */
final readonly class AnalysisReport
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public FoodSummary $product,
        public array $findings,
        public string $summary,
        public int $riskScore,
        public string $verdict,
        public ?string $failure = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromModelResponse(array $payload, FoodSummary $product): self
    {
        $findings = array_values(array_filter(array_map(
            static fn (mixed $row): ?Finding => is_array($row) ? Finding::fromArray($row) : null,
            data_get($payload, 'findings', []) ?: [],
        )));

        usort($findings, static fn (Finding $a, Finding $b): int => $b->severity->weight() <=> $a->severity->weight());

        $summary = trim((string) data_get($payload, 'summary', ''));
        $summary = self::clean($summary);

        // Sanitised the same way as the summary: the model's output is rendered
        // back to consumers, and neither field should carry markup or runs of
        // whitespace.
        $verdict = self::clean((string) data_get($payload, 'verdict', ''));

        return new self(
            product: $product,
            findings: $findings,
            summary: $summary,
            riskScore: self::riskScore($findings),
            verdict: $verdict !== '' ? $verdict : self::verdictFor($findings),
        );
    }

    private static function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    /**
     * A report the detector could not produce, kept so the page can say so plainly.
     */
    public static function unavailable(FoodSummary $product, string $reason): self
    {
        return new self(
            product: $product,
            findings: [],
            summary: '',
            riskScore: 0,
            verdict: '',
            failure: $reason,
        );
    }

    public function hasFailed(): bool
    {
        return $this->failure !== null;
    }

    public function hasFindings(): bool
    {
        return $this->findings !== [];
    }

    public function worstSeverity(): ?FindingSeverity
    {
        return $this->findings[0]->severity ?? null;
    }

    /**
     * The cacheable form.
     *
     * Cached as a plain array rather than the object itself: every cache driver
     * except "array" serialises, and unserialising a class before it is loaded
     * yields an incomplete object. Data in, object out.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'origin' => $this->product->origin,
            ],
            'findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $this->findings
            ),
            'summary' => $this->summary,
            'risk_score' => $this->riskScore,
            'verdict' => $this->verdict,
            'failure' => $this->failure,
        ];
    }

    /**
     * Rebuild from the cached array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            product: new FoodSummary(
                id: (int) data_get($data, 'product.id', 0),
                name: (string) data_get($data, 'product.name', ''),
                origin: data_get($data, 'product.origin'),
            ),
            findings: array_values(array_filter(array_map(
                static function (mixed $row): ?Finding {
                    if (! is_array($row)) {
                        return null;
                    }

                    $category = FindingCategory::tryFrom((string) ($row['category'] ?? ''));

                    return $category?->toFinding(
                        $row['title'] ?? null,
                        $row['detail'] ?? null,
                        $row['severity'] ?? null,
                        $row['evidence'] ?? null,
                    );
                },
                data_get($data, 'findings', []) ?: [],
            ))),
            summary: (string) data_get($data, 'summary', ''),
            riskScore: (int) data_get($data, 'risk_score', 0),
            verdict: (string) data_get($data, 'verdict', ''),
            failure: data_get($data, 'failure'),
        );
    }

    /**
     * @param  list<Finding>  $findings
     */
    public static function riskScore(array $findings): int
    {
        $penalty = array_sum(array_map(static fn (Finding $f): int => $f->severity->weight(), $findings));

        return max(0, min(100, (int) round($penalty / 1.4)));
    }

    /**
     * @param  list<Finding>  $findings
     */
    public static function verdictFor(array $findings): string
    {
        if ($findings === []) {
            return 'No misleading claims detected';
        }

        $high = count(array_filter($findings, static fn (Finding $f): bool => $f->severity === FindingSeverity::High));

        return match (true) {
            $high > 0 => 'Likely misleading',
            $findings !== [] => 'Claims not fully supported',
            default => 'No misleading claims detected',
        };
    }
}

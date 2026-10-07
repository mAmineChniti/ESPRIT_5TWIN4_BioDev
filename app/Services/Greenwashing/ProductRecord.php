<?php

namespace App\Services\Greenwashing;

use App\Enums\Stage;
use App\Models\Food;

/**
 * Everything NutriTrace knows about one product, flattened for the model.
 *
 * This is data marshalling, not judgement: the record states what is on file
 * and the model decides what that implies. Anything the record marks as absent
 * is genuinely absent from the database rather than omitted, because "not
 * recorded" is itself the most common thing a consumer needs told.
 */
final readonly class ProductRecord
{
    /**
     * @param  list<array<string, mixed>>  $certifications
     * @param  list<array<string, mixed>>  $chain
     * @param  list<array<string, mixed>>  $reports
     * @param  array<string, mixed>  $nutrition
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $category,
        public ?string $origin,
        public ?string $producer,
        public ?string $producerRole,
        public ?string $ecoGrade,
        public ?string $ecoGradeMeaning,
        public int $scans,
        public array $nutrition,
        public array $certifications,
        public array $chain,
        public array $reports,
        public int $recordedStages,
        public int $totalStages,
        public int $transparencyScore,
        public string $registeredAt,
    ) {}

    public static function fromFood(Food $food): self
    {
        $food->loadMissing(['category', 'producer', 'certifications', 'transitions.actor', 'reports']);

        return new self(
            id: $food->id,
            name: $food->name,
            category: $food->category?->name,
            origin: $food->origin,
            producer: $food->producer?->name,
            producerRole: $food->producer?->role,
            ecoGrade: $food->environmental_score?->value,
            ecoGradeMeaning: $food->environmental_score?->label(),
            scans: (int) $food->scans_count,
            nutrition: [
                'calories_per_100g' => (float) $food->calories,
                'protein_g_per_100g' => (float) $food->protein,
                'carbs_g_per_100g' => (float) $food->carbs,
                'fat_g_per_100g' => (float) $food->fat,
            ],
            certifications: $food->certifications->map(fn ($certification): array => [
                'name' => $certification->name,
                'issuer' => $certification->issuer,
                'certificate_number' => $certification->certificate_number,
                'valid_until' => $certification->valid_until?->format('Y-m-d'),
                'expired' => $certification->isExpired(),
                'obtained_on' => $food->certifications
                    ->firstWhere('id', $certification->id)?->pivot?->obtained_on,
            ])->values()->all(),
            chain: $food->transitions->map(fn ($transition): array => [
                'stage' => $transition->to_stage?->value,
                'from' => $transition->from_stage?->value,
                'recorded_by' => $transition->actor?->name,
                'recorded_by_role' => $transition->actor?->role,
                'when' => $transition->occurred_at->toDateString(),
                'notes' => $transition->notes,
            ])->values()->all(),
            reports: $food->reports->map(fn ($report): array => [
                'reason' => $report->reason?->value,
                'status' => $report->status?->value,
                'raised_by_role' => $report->user?->role,
                'when' => $report->created_at->toDateString(),
            ])->values()->all(),
            recordedStages: $food->transitions->count(),
            totalStages: Stage::total(),
            transparencyScore: $food->transparencyScore(),
            registeredAt: $food->created_at->toDateString(),
        );
    }

    /**
     * The record as the JSON block the model reads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product' => [
                'id' => $this->id,
                'name' => $this->name,
                'category' => $this->category ?? 'not recorded',
                'origin' => $this->origin ?? 'not recorded',
                'producer' => $this->producer ?? 'not recorded',
                'producer_role' => $this->producerRole ?? 'not recorded',
                'registered_on' => $this->registeredAt,
                'times_scanned' => $this->scans,
            ],
            'claims' => [
                'environmental_grade' => $this->ecoGrade ?? 'not recorded',
                'environmental_grade_meaning' => $this->ecoGradeMeaning ?? 'not recorded',
                'certifications' => $this->certifications,
                'declared_origin' => $this->origin ?? 'not recorded',
                'name_and_category_imply_eco_claims' => true,
            ],
            'evidence' => [
                'supply_chain' => [
                    'stages_recorded' => $this->recordedStages,
                    'stages_total' => $this->totalStages,
                    'complete' => $this->recordedStages >= $this->totalStages,
                    'steps' => $this->chain,
                ],
                'nutrition_per_100g' => $this->nutrition,
                'transparency_score' => $this->transparencyScore,
                'greenwashing_reports' => $this->reports,
            ],
        ];
    }
}

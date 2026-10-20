<?php

namespace App\Services\Agriculture;

class FarmAiDiagnosticReport
{
    /**
     * @param  list<string>  $strengths
     * @param  list<string>  $vulnerabilities
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public readonly int $score,
        public readonly string $verdict,
        public readonly string $waterRiskLevel,
        public readonly string $soilHealthLevel,
        public readonly array $strengths,
        public readonly array $vulnerabilities,
        public readonly array $recommendations,
        public readonly string $summary,
        public readonly bool $isAiGenerated,
        public readonly string $analyzedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromModelResponse(array $data): self
    {
        return new self(
            score: self::score($data['score'] ?? 70),
            verdict: (string) ($data['verdict'] ?? __('Good agro-ecological practices')),
            waterRiskLevel: self::waterRiskLevel($data['water_risk_level'] ?? 'Medium'),
            soilHealthLevel: self::soilHealthLevel($data['soil_health_level'] ?? 'Good'),
            strengths: array_values(array_filter((array) ($data['strengths'] ?? []), 'is_string')),
            vulnerabilities: array_values(array_filter((array) ($data['vulnerabilities'] ?? []), 'is_string')),
            recommendations: array_values(array_filter((array) ($data['recommendations'] ?? []), 'is_string')),
            summary: (string) ($data['summary'] ?? ''),
            isAiGenerated: true,
            analyzedAt: now()->toIso8601String(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            score: self::score($payload['score'] ?? 0),
            verdict: (string) ($payload['verdict'] ?? ''),
            waterRiskLevel: self::waterRiskLevel($payload['water_risk_level'] ?? 'Medium'),
            soilHealthLevel: self::soilHealthLevel($payload['soil_health_level'] ?? 'Good'),
            strengths: (array) ($payload['strengths'] ?? []),
            vulnerabilities: (array) ($payload['vulnerabilities'] ?? []),
            recommendations: (array) ($payload['recommendations'] ?? []),
            summary: (string) ($payload['summary'] ?? ''),
            isAiGenerated: (bool) ($payload['is_ai_generated'] ?? false),
            analyzedAt: (string) ($payload['analyzed_at'] ?? now()->toIso8601String()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'verdict' => $this->verdict,
            'water_risk_level' => $this->waterRiskLevel,
            'soil_health_level' => $this->soilHealthLevel,
            'strengths' => $this->strengths,
            'vulnerabilities' => $this->vulnerabilities,
            'recommendations' => $this->recommendations,
            'summary' => $this->summary,
            'is_ai_generated' => $this->isAiGenerated,
            'analyzed_at' => $this->analyzedAt,
        ];
    }

    /**
     * CSS classes for the score badge.
     */
    public function scoreBadgeVariant(): string
    {
        return match (true) {
            $this->score >= 80 => 'secondary',
            $this->score >= 60 => 'outline',
            default => 'destructive',
        };
    }

    private static function score(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    private static function waterRiskLevel(mixed $value): string
    {
        return in_array($value, ['Low', 'Medium', 'High'], true) ? (string) $value : 'Medium';
    }

    private static function soilHealthLevel(mixed $value): string
    {
        return in_array($value, ['Excellent', 'Good', 'Degraded'], true) ? (string) $value : 'Good';
    }
}

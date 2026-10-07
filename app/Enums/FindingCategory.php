<?php

namespace App\Enums;

use App\Services\Greenwashing\Finding;

/**
 * The vocabulary the detector must choose from when classifying a finding.
 *
 * Constraining the model to a fixed set is what lets every finding map onto a
 * report reason, so a consumer can escalate one without inventing a category.
 */
enum FindingCategory: string
{
    case UnverifiableClaim = 'unverifiable_claim';
    case MisleadingEcoScore = 'misleading_eco_score';
    case UnverifiedCertification = 'unverified_certification';
    case ExpiredCertification = 'expired_certification';
    case MisleadingOrigin = 'misleading_origin';
    case MissingTraceability = 'missing_traceability';
    case Other = 'other';

    public function reason(): ReportReason
    {
        return ReportReason::from($this->value);
    }

    /**
     * The label a consumer sees on the "report this" control.
     */
    public function reportLabel(): string
    {
        return $this->reason()->label();
    }

    /**
     * Build a finding in this category, normalising whatever the model returned.
     */
    public function toFinding(mixed $title, mixed $detail, mixed $severity, mixed $evidence = null): Finding
    {
        return new Finding(
            category: $this,
            severity: FindingSeverity::tryFromValue($severity),
            title: $this->cleanText($title) ?: $this->reason()->label(),
            detail: $this->cleanText($detail),
            evidence: $this->cleanText($evidence),
        );
    }

    private function cleanText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        // The model occasionally returns markup or a stray newline run; a
        // consumer-facing card should never contain either.
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

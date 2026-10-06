<?php

namespace App\Enums;

enum ReportReason: string
{
    case UnverifiableClaim = 'unverifiable_claim';
    case MisleadingEcoScore = 'misleading_eco_score';
    case UnverifiedCertification = 'unverified_certification';
    case ExpiredCertification = 'expired_certification';
    case MisleadingOrigin = 'misleading_origin';
    case MissingTraceability = 'missing_traceability';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::UnverifiableClaim => 'Claim I cannot verify',
            self::MisleadingEcoScore => 'Eco score looks wrong',
            self::UnverifiedCertification => 'Certification has no issuing body',
            self::ExpiredCertification => 'Certification has expired',
            self::MisleadingOrigin => 'Origin looks misleading',
            self::MissingTraceability => 'Missing steps in the supply chain',
            self::Other => 'Something else',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::UnverifiableClaim => 'The label makes a promise that is not backed by anything on this page.',
            self::MisleadingEcoScore => 'The grade does not match what is known about how this was made.',
            self::UnverifiedCertification => 'A certification is shown without who issued it or a certificate number.',
            self::ExpiredCertification => 'A certification is shown but its validity date has passed.',
            self::MisleadingOrigin => 'The stated origin does not match the recorded supply chain.',
            self::MissingTraceability => 'Steps are missing, or the dates jump in a way that makes no sense.',
            self::Other => 'Tell us what looks wrong.',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

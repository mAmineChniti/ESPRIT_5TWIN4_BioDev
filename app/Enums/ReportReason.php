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
            self::UnverifiableClaim => __('Claim I cannot verify'),
            self::MisleadingEcoScore => __('Eco score looks wrong'),
            self::UnverifiedCertification => __('Certification has no issuing body'),
            self::ExpiredCertification => __('Certification has expired'),
            self::MisleadingOrigin => __('Origin looks misleading'),
            self::MissingTraceability => __('Missing steps in the supply chain'),
            self::Other => __('Something else'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::UnverifiableClaim => __('The label makes a promise that is not backed by anything on this page.'),
            self::MisleadingEcoScore => __('The grade does not match what is known about how this was made.'),
            self::UnverifiedCertification => __('A certification is shown without who issued it or a certificate number.'),
            self::ExpiredCertification => __('A certification is shown but its validity date has passed.'),
            self::MisleadingOrigin => __('The stated origin does not match the recorded supply chain.'),
            self::MissingTraceability => __('Steps are missing, or the dates jump in a way that makes no sense.'),
            self::Other => __('Tell us what looks wrong.'),
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

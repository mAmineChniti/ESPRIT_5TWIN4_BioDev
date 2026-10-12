<?php

namespace App\Enums;

/**
 * Why someone believes the detector got a product wrong.
 *
 * Distinct from ReportReason, which is what a product is accused of. These are
 * the ways an analysis can be wrong, which is what makes the report actionable:
 * "the record is wrong" is a data problem, "the finding is bogus" is a prompt
 * problem, and neither is fixed by editing the product.
 */
enum AnalysisDisputeReason: string
{
    case WrongVerdict = 'wrong_verdict';
    case FalsePositive = 'false_positive';
    case MissedIssue = 'missed_issue';
    case WrongEvidence = 'wrong_evidence';
    case IncompleteRecord = 'incomplete_record';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WrongVerdict => __('The verdict is wrong'),
            self::FalsePositive => __('A flagged issue is not real'),
            self::MissedIssue => __('Something is wrong but was not flagged'),
            self::WrongEvidence => __('The finding cites the wrong evidence'),
            self::IncompleteRecord => __('The record itself is wrong'),
            self::Other => __('Something else'),
        };
    }

    /**
     * What the reporter is being asked to say, so they do not just repeat the
     * label back.
     */
    public function hint(): string
    {
        return match ($this) {
            self::WrongVerdict => __('Which part of the verdict is unsupported, and by what?'),
            self::FalsePositive => __('Which finding should not have been raised, and why not?'),
            self::MissedIssue => __('What did the analysis miss?'),
            self::WrongEvidence => __('Which field was cited, and what should it have been?'),
            self::IncompleteRecord => __('Which recorded value is inaccurate?'),
            self::Other => __('Tell us what the analysis got wrong.'),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}

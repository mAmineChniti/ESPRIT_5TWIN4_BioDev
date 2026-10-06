<?php

namespace Tests\Unit;

use App\Enums\ReportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportStatusTest extends TestCase
{
    /**
     * Only an upheld report is allowed to lower a product's transparency
     * score, otherwise a pending allegation would punish a product before
     * anyone has looked at it.
     */
    public function test_only_upheld_reports_count_against_a_product(): void
    {
        $this->assertTrue(ReportStatus::Upheld->countsAgainstTrust());
        $this->assertFalse(ReportStatus::Pending->countsAgainstTrust());
        $this->assertFalse(ReportStatus::Dismissed->countsAgainstTrust());
    }

    /**
     * @return array<string, array{ReportStatus, string}>
     */
    public static function statusProvider(): array
    {
        return [
            'pending' => [ReportStatus::Pending, 'Awaiting review'],
            'upheld' => [ReportStatus::Upheld, 'Upheld'],
            'dismissed' => [ReportStatus::Dismissed, 'Dismissed'],
        ];
    }

    #[DataProvider('statusProvider')]
    public function test_each_status_has_a_label(ReportStatus $status, string $expected): void
    {
        $this->assertSame($expected, $status->label());
    }
}

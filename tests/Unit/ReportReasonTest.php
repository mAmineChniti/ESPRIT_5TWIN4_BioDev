<?php

namespace Tests\Unit;

use App\Enums\ReportReason;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportReasonTest extends TestCase
{
    /**
     * @return array<string, array{ReportReason}>
     */
    public static function reasonProvider(): array
    {
        $reasons = [];

        foreach (ReportReason::cases() as $case) {
            $reasons[$case->name] = [$case];
        }

        return $reasons;
    }

    #[DataProvider('reasonProvider')]
    public function test_every_reason_explains_itself_to_a_consumer(ReportReason $reason): void
    {
        $this->assertNotSame('', $reason->label());
        $this->assertNotSame('', $reason->hint());
    }

    public function test_reason_values_are_unique_and_url_safe(): void
    {
        $values = array_column(ReportReason::cases(), 'value');

        $this->assertSame($values, array_unique($values));

        foreach ($values as $value) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $value);
        }
    }
}

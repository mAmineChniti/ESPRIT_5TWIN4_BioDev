<?php

namespace Tests\Unit;

use App\Enums\EnvironmentalScore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvironmentalScoreTest extends TestCase
{
    public function test_it_exposes_the_five_grades(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], EnvironmentalScore::values());
        $this->assertCount(5, EnvironmentalScore::cases());
    }

    /**
     * @return array<string, array{EnvironmentalScore, string}>
     */
    public static function gradeProvider(): array
    {
        return [
            'A' => [EnvironmentalScore::A, 'Very low impact'],
            'B' => [EnvironmentalScore::B, 'Low impact'],
            'C' => [EnvironmentalScore::C, 'Medium impact'],
            'D' => [EnvironmentalScore::D, 'High impact'],
            'E' => [EnvironmentalScore::E, 'Very high impact'],
        ];
    }

    #[DataProvider('gradeProvider')]
    public function test_every_grade_has_a_human_readable_label(
        EnvironmentalScore $grade,
        string $expected
    ): void {
        $this->assertSame($expected, $grade->label());
    }

    /**
     * The two lowest grades must render as good and the worst two as bad,
     * otherwise the badge misreports the product.
     *
     * @return array<string, array{EnvironmentalScore, string}>
     */
    public static function tierProvider(): array
    {
        return [
            'A' => [EnvironmentalScore::A, 'bg-primary'],
            'B' => [EnvironmentalScore::B, 'bg-primary'],
            'C' => [EnvironmentalScore::C, 'bg-secondary'],
            'D' => [EnvironmentalScore::D, 'bg-destructive'],
            'E' => [EnvironmentalScore::E, 'bg-destructive'],
        ];
    }

    #[DataProvider('tierProvider')]
    public function test_each_grade_maps_to_the_expected_tier(
        EnvironmentalScore $grade,
        string $expected
    ): void {
        $this->assertStringStartsWith($expected, $grade->badgeClasses());
        $this->assertStringContainsString('text-', $grade->badgeClasses());
    }

    public function test_the_worst_grade_is_never_styled_as_the_best(): void
    {
        $this->assertNotSame(
            EnvironmentalScore::A->badgeClasses(),
            EnvironmentalScore::E->badgeClasses()
        );

        $this->assertStringContainsString(
            'destructive',
            EnvironmentalScore::E->badgeClasses()
        );
    }

    public function test_it_can_be_built_from_its_string_value(): void
    {
        $this->assertSame(EnvironmentalScore::C, EnvironmentalScore::from('C'));
        $this->assertSame(EnvironmentalScore::C, EnvironmentalScore::tryFrom('C'));
        $this->assertNull(EnvironmentalScore::tryFrom('F'));
        $this->assertNull(EnvironmentalScore::tryFrom('z'));
    }
}

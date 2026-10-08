<?php

namespace Tests\Unit;

use App\Enums\FarmStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FarmStatusTest extends TestCase
{
    public function test_the_stored_values_are_the_ones_the_database_column_uses(): void
    {
        $this->assertSame(
            ['pending', 'approved', 'rejected'],
            FarmStatus::values(),
            'Changing these values requires the accompanying data migration.'
        );
    }

    public function test_each_status_has_a_human_label(): void
    {
        $this->assertSame('Pending', FarmStatus::Pending->label());
        $this->assertSame('Approved', FarmStatus::Approved->label());
        $this->assertSame('Rejected', FarmStatus::Rejected->label());
    }

    public function test_each_status_has_a_distinct_statistic_key(): void
    {
        $keys = array_map(fn (FarmStatus $status): string => $status->statKey(), FarmStatus::cases());

        $this->assertSame($keys, array_unique($keys), 'Two statuses share a statistics key.');
    }

    /**
     * The theme has no warning channel, so a badge must not invent a colour
     * that would never follow the palette.
     */
    public function test_badges_use_theme_tokens_only(): void
    {
        $allowed = [
            'bg-primary', 'text-primary', 'text-primary-foreground',
            'bg-secondary', 'text-secondary-foreground',
            'bg-destructive', 'text-destructive', 'text-destructive-foreground',
        ];

        foreach (FarmStatus::cases() as $status) {
            foreach (explode(' ', $status->badgeClasses()) as $class) {
                // An opacity modifier is fine; a bare Tailwind palette name is not.
                $base = explode('/', $class)[0];

                $this->assertContains(
                    $base,
                    $allowed,
                    "{$status->value} uses '{$class}', which is not a theme token."
                );
            }
        }
    }

    #[DataProvider('distinctnessProvider')]
    public function test_no_two_statuses_look_the_same(FarmStatus $a, FarmStatus $b): void
    {
        $this->assertNotSame(
            $a->badgeClasses(),
            $b->badgeClasses(),
            "{$a->value} and {$b->value} are rendered identically."
        );
    }

    /**
     * @return array<string, array{FarmStatus, FarmStatus}>
     */
    public static function distinctnessProvider(): array
    {
        return [
            'pending vs approved' => [FarmStatus::Pending, FarmStatus::Approved],
            'pending vs rejected' => [FarmStatus::Pending, FarmStatus::Rejected],
            'approved vs rejected' => [FarmStatus::Approved, FarmStatus::Rejected],
        ];
    }

    public function test_a_status_can_be_resolved_from_its_stored_value(): void
    {
        $this->assertSame(FarmStatus::Pending, FarmStatus::from('pending'));
        $this->assertNull(FarmStatus::tryFrom('nope'));
    }
}

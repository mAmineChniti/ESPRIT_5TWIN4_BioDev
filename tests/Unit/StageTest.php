<?php

namespace Tests\Unit;

use App\Enums\Stage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StageTest extends TestCase
{
    public function test_the_chain_runs_in_supply_order(): void
    {
        $this->assertSame(
            ['produced', 'processed', 'distributed'],
            Stage::order()
        );
    }

    /**
     * @return array<string, array{Stage, string}>
     */
    public static function stageProvider(): array
    {
        return [
            'produced' => [Stage::Produced, 'Produced'],
            'processed' => [Stage::Processed, 'Processed'],
            'distributed' => [Stage::Distributed, 'Distributed'],
        ];
    }

    #[DataProvider('stageProvider')]
    public function test_each_stage_has_a_label(Stage $stage, string $expected): void
    {
        $this->assertSame($expected, $stage->label());
        $this->assertSame($stage->value, strtolower($stage->label()));
    }

    public function test_stage_order_matches_the_case_values(): void
    {
        $this->assertSame(
            array_column(Stage::cases(), 'value'),
            Stage::order()
        );
    }

    public function test_a_stage_knows_its_position_in_the_chain(): void
    {
        $this->assertSame(0, Stage::Produced->position());
        $this->assertSame(1, Stage::Processed->position());
        $this->assertSame(2, Stage::Distributed->position());
    }

    public function test_positions_are_consecutive_and_follow_the_declared_order(): void
    {
        $positions = array_map(fn (Stage $stage): int => $stage->position(), Stage::cases());

        $this->assertSame(range(0, count(Stage::cases()) - 1), $positions);
        $this->assertSame(Stage::order(), array_map(
            fn (Stage $stage): string => $stage->value,
            Stage::cases()
        ));
    }

    public function test_a_stage_can_be_compared_to_another(): void
    {
        $this->assertTrue(Stage::Processed->position() > Stage::Produced->position());
        $this->assertTrue(Stage::Distributed->position() <= Stage::Distributed->position());
    }
}

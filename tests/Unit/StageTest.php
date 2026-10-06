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
}

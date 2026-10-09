<?php

namespace Tests\Unit;

use App\Enums\TransportMode;
use App\Services\CarbonFootprintCalculator;
use PHPUnit\Framework\TestCase;

class CarbonFootprintCalculatorTest extends TestCase
{
    public function test_it_multiplies_tonnes_distance_and_factor(): void
    {
        // 10 t x 100 km x 0.100 = 100 kg
        $this->assertSame(100.0, CarbonFootprintCalculator::calculate(10000, 100, TransportMode::Truck));
    }

    public function test_compare_returns_the_lowest_footprint_first(): void
    {
        $rows = CarbonFootprintCalculator::compare(1000, 500);

        $this->assertSame(TransportMode::Ship, $rows[0]['mode']);
        $this->assertSame(TransportMode::Plane, $rows[array_key_last($rows)]['mode']);
    }
}

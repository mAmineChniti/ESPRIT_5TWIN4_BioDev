<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\CarbonFootprintCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'SHP-'.fake()->unique()->numerify('######'),
            'warehouse_id' => Warehouse::factory(),
            'food_id' => null,
            'user_id' => null,
            'destination' => fake()->randomElement([
                'Marseille, France', 'Genoa, Italy', 'Tripoli, Libya', 'Algiers, Algeria',
                'Tunis, Tunisia', 'Sfax, Tunisia', 'Paris, France', 'Casablanca, Morocco',
            ]),
            'distance_km' => fake()->randomFloat(1, 40, 2500),
            'weight_kg' => fake()->randomFloat(2, 50, 20000),
            'transport_mode' => fake()->randomElement(TransportMode::cases()),
            'status' => fake()->randomElement(ShipmentStatus::cases()),
            'shipped_on' => fake()->dateTimeBetween('-3 months', '+2 weeks')->format('Y-m-d'),
            'carbon_footprint_kg' => 0,
        ];
    }

    /**
     * Compute the footprint from the final attributes, so overrides are respected.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Shipment $shipment): void {
            $shipment->carbon_footprint_kg = CarbonFootprintCalculator::calculate(
                (float) $shipment->weight_kg,
                (float) $shipment->distance_km,
                $shipment->transport_mode,
            );
        });
    }
}
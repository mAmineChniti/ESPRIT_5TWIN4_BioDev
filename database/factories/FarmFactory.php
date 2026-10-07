<?php

namespace Database\Factories;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farm>
 */
class FarmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agricultural_region_id' => AgriculturalRegion::factory(),
            'name' => 'Ferme '.fake()->company(),
            'producer_name' => fake()->name(),
            'address' => fake()->address(),
            'surface_hectares' => fake()->randomFloat(2, 5, 250),
            'farming_type' => fake()->randomElement(['Biologique', 'Raisonné', 'Traditionnel', 'Biodynamique']),
            'phone' => fake()->phoneNumber(),
            'description' => fake()->sentence(12),
        ];
    }
}

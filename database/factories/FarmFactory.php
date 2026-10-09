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
            'name' => 'Farm '.fake()->company(),
            'producer_name' => fake()->name(),
            'address' => fake()->address(),
            'surface_hectares' => fake()->randomFloat(2, 5, 250),
            'farming_type' => fake()->randomElement(['Organic', 'Sustainable', 'Traditional', 'Biodynamic']),
            'phone' => fake()->phoneNumber(),
            'description' => fake()->sentence(12),
        ];
    }
}

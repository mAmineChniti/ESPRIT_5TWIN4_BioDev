<?php

namespace Database\Factories;

use App\Models\AgriculturalRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgriculturalRegion>
 */
class AgriculturalRegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city() . ' Agricole',
            'code' => 'REG-' . strtoupper(fake()->unique()->lexify('???-###')),
            'climate' => fake()->randomElement(['Méditerranéen', 'Subhumide', 'Semi-aride', 'Continental']),
            'soil_type' => fake()->randomElement(['Argilo-limoneux', 'Sableux-fertile', 'Calcaire', 'Alluvial']),
            'description' => fake()->paragraph(),
        ];
    }
}

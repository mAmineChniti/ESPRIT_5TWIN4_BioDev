<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Bizerte', 'Gabes', 'Nabeul', 'Kairouan', 'Beja']);

        return [
            'name' => 'Warehouse '.$city.' '.fake()->unique()->numerify('##'),
            'city' => $city,
            'country' => 'Tunisia',
            'address' => fake()->streetAddress(),
            'capacity_m2' => fake()->numberBetween(500, 8000),
            'is_refrigerated' => fake()->boolean(35),
        ];
    }
}
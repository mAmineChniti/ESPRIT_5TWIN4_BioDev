<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journey>
 */
class JourneyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Food::factory(),
            'qr_code' => 'QR-'.fake()->unique()->bothify('######??'),
            'environmental_score' => fake()->randomFloat(2, 0, 100),
            'total_distance_km' => fake()->randomFloat(1, 0, 5000),
            'ai_summary' => fake()->sentence(12),
            'generated_at' => now(),
        ];
    }
}

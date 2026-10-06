<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            'user_id' => User::factory()->state(['role' => 'consumer']),
            'rating' => fake()->numberBetween(1, 5),
            'body' => fake()->optional()->sentence(),
        ];
    }
}

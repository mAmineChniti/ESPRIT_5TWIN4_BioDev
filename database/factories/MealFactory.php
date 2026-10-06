<?php

namespace Database\Factories;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'consumer']),
            'name' => fake()->randomElement(['Energy breakfast', 'Light lunch', 'Protein dinner', 'Post-workout snack', 'Weekend brunch']),
            'type' => fake()->randomElement(Meal::TYPES),
            'consumed_on' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

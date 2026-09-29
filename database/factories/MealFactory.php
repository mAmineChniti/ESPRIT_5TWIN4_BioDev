<?php

namespace Database\Factories;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Energy breakfast', 'Light lunch', 'Protein dinner', 'Post-workout snack', 'Weekend brunch']),
            'type' => fake()->randomElement(['breakfast', 'lunch', 'dinner', 'snack', 'other']),
            'consumed_on' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

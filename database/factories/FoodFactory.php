<?php

namespace Database\Factories;

use App\Models\Food;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Apple', 'Brown rice', 'Grilled chicken', 'Salmon', 'Lentils', 'Plain yogurt', 'Oats', 'Banana', 'Broccoli', 'Egg']),
            'category' => fake()->randomElement(['fruit', 'vegetable', 'protein', 'grain', 'dairy', 'other']),
            'calories' => fake()->numberBetween(30, 600),
            'protein' => fake()->randomFloat(2, 0, 40),
            'carbs' => fake()->randomFloat(2, 0, 80),
            'fat' => fake()->randomFloat(2, 0, 40),
        ];
    }
}

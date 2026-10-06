<?php

namespace Database\Factories;

use App\Enums\EnvironmentalScore;
use App\Models\Category;
use App\Models\Food;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Generic fixtures. This factory deliberately holds no product taxonomy —
 * which products exist, and which category each belongs to, is catalog data and
 * lives in the database. Seeding a coherent demo catalog is the seeder's job.
 *
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category_id' => Category::factory(),
            'producer_id' => User::factory()->state(['role' => 'producer']),
            'origin' => fake()->randomElement(['France', 'Spain', 'Italy', 'Morocco', 'Greece', 'Tunisia']),
            'environmental_score' => fake()->randomElement(EnvironmentalScore::cases()),
            'calories' => fake()->numberBetween(30, 600),
            'protein' => fake()->randomFloat(2, 0, 40),
            'carbs' => fake()->randomFloat(2, 0, 80),
            'fat' => fake()->randomFloat(2, 0, 40),
        ];
    }

    /**
     * Attach the food to an existing category instead of generating one.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (): array => [
            'category_id' => $category->id,
        ]);
    }
}

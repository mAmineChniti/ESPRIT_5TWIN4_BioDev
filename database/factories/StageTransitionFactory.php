<?php

namespace Database\Factories;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\StageTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StageTransition>
 */
class StageTransitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            'actor_id' => null,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'notes' => fake()->optional()->sentence(),
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 60)),
        ];
    }
}

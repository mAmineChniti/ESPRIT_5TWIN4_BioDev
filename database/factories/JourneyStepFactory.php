<?php

namespace Database\Factories;

use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JourneyStep>
 */
class JourneyStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * `step_order` defaults to 1 because the uniqueness constraint is scoped
     * to the journey, and a fresh journey is created by default. Pass an
     * explicit order when adding several steps to the same journey.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'step_order' => 1,
            'type' => fake()->randomElement(['origin', 'transport', 'storage', 'sale']),
            'location' => fake()->city(),
            'step_date' => fake()->date(),
            'description' => fake()->sentence(10),
            'farm_id' => null,
            'shipment_id' => null,
        ];
    }
}

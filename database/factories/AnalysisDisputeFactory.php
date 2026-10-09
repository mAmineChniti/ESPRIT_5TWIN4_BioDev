<?php

namespace Database\Factories;

use App\Enums\AnalysisDisputeReason;
use App\Enums\AnalysisDisputeStatus;
use App\Models\AnalysisDispute;
use App\Models\Food;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalysisDispute>
 */
class AnalysisDisputeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            // Any role may dispute an analysis, so the factory does not pin one.
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(AnalysisDisputeReason::cases()),
            'finding_category' => null,
            'comment' => fake()->sentence(),
            'status' => AnalysisDisputeStatus::Pending,
            'resolution_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * A challenge aimed at one finding rather than the verdict as a whole.
     */
    public function aboutFinding(?string $category = null): static
    {
        return $this->state(fn (): array => [
            'finding_category' => $category,
        ]);
    }

    public function upheld(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisDisputeStatus::Upheld,
            'reviewed_by' => User::factory()->state(['role' => 'admin']),
            'reviewed_at' => now(),
            'resolution_note' => fake()->sentence(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisDisputeStatus::Dismissed,
            'reviewed_by' => User::factory()->state(['role' => 'admin']),
            'reviewed_at' => now(),
            'resolution_note' => fake()->sentence(),
        ]);
    }
}

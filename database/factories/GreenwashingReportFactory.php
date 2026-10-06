<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GreenwashingReport>
 */
class GreenwashingReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            'user_id' => User::factory()->state(['role' => 'consumer']),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'details' => fake()->optional()->sentence(),
            'status' => ReportStatus::Pending,
        ];
    }

    public function upheld(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportStatus::Upheld,
            'reviewed_by' => User::factory()->state(['role' => 'admin']),
            'reviewed_at' => now(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportStatus::Dismissed,
            'reviewed_by' => User::factory()->state(['role' => 'admin']),
            'reviewed_at' => now(),
        ]);
    }
}

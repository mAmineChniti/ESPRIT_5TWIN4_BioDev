<?php

namespace Database\Factories;

use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Organic', 'Local', 'Fair trade']),
            'issuer' => fake()->randomElement(['Ecocert', 'Fairtrade Labelling', 'AOP', 'Rainforest Alliance']),
            'certificate_number' => strtoupper(fake()->bothify('??-####-????')),
            'valid_until' => fake()->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'valid_until' => now()->subYear()->format('Y-m-d'),
        ]);
    }
}

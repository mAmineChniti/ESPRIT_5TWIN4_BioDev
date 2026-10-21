<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Journey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traceability journeys for a subset of the seeded catalog.
 *
 * A journey belongs to exactly one product, so it is only created for foods
 * that do not already have one. Step types use the renamed English values.
 */
class JourneySeeder extends Seeder
{
    /**
     * The four step types, in the order a product travels through them.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const STEP_TYPES = [
        ['origin', 'Tunis', 'TN'],
        ['transport', 'Sousse', 'TN'],
        ['storage', 'Sfax', 'TN'],
        ['sale', 'Nabeul', 'TN'],
    ];

    /**
     * Seed traceability journeys using locations in the city reference.
     */
    public function run(): void
    {
        $foods = Food::query()
            ->doesntHave('journey')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        foreach ($foods as $food) {
            // A journey and its steps are one unit of work.
            DB::transaction(function () use ($food): void {
                $journey = Journey::create([
                    'product_id' => $food->id,
                    // Derived from the product id rather than the loop index: a
                    // product has exactly one journey, so this is unique and stays
                    // the same when re-seeded. Numbering by index handed out
                    // QR-000001 again on a second run and collided with the unique
                    // index on qr_code.
                    'qr_code' => 'QR-'.str_pad((string) $food->id, 6, '0', STR_PAD_LEFT),
                    'environmental_score' => fake()->randomFloat(2, 40, 95),
                    'total_distance_km' => fake()->randomFloat(1, 20, 1800),
                    'ai_summary' => fake()->sentence(14),
                    'generated_at' => now(),
                ]);

                $this->recordSteps($journey->id);
            });
        }
    }

    /**
     * Record a seeded subset of the journey workflow.
     */
    private function recordSteps(int $journeyId): void
    {
        $depth = fake()->numberBetween(2, count(self::STEP_TYPES));
        $firstDate = now()->subDays(fake()->numberBetween(10, 40));

        foreach (array_slice(self::STEP_TYPES, 0, $depth) as $offset => [$type, $location, $countryCode]) {
            DB::table('journey_steps')->insert([
                'journey_id' => $journeyId,
                'step_order' => $offset + 1,
                'type' => $type,
                'location' => $location,
                'country_code' => $countryCode,
                'step_date' => $firstDate->copy()->addDays($offset * 3)->format('Y-m-d'),
                'description' => fake()->sentence(8),
                'farm_id' => null,
                'shipment_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

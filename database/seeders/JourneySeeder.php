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
     * @var list<array{0: string, 1: string}>
     */
    private const STEP_TYPES = [
        ['origin', 'Farm or cooperative'],
        ['transport', 'In transit'],
        ['storage', 'Warehouse'],
        ['sale', 'Retail'],
    ];

    public function run(): void
    {
        $foods = Food::query()
            ->doesntHave('journey')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        foreach ($foods as $index => $food) {
            $journey = Journey::create([
                'product_id' => $food->id,
                'qr_code' => 'QR-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                'environmental_score' => fake()->randomFloat(2, 40, 95),
                'total_distance_km' => fake()->randomFloat(1, 20, 1800),
                'ai_summary' => fake()->sentence(14),
                'generated_at' => now(),
            ]);

            $this->recordSteps($journey->id);
        }
    }

    private function recordSteps(int $journeyId): void
    {
        $depth = fake()->numberBetween(2, count(self::STEP_TYPES));
        $firstDate = now()->subDays(fake()->numberBetween(10, 40));

        foreach (array_slice(self::STEP_TYPES, 0, $depth) as $offset => [$type, $location]) {
            DB::table('journey_steps')->insert([
                'journey_id' => $journeyId,
                'step_order' => $offset + 1,
                'type' => $type,
                'location' => $location,
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

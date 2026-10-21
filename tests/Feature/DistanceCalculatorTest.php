<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityDistance;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Services\DistanceCalculator;
use Database\Seeders\CitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DistanceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed the international city reference for distance tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
    }

    /**
     * Sum consecutive distances for a Tunisian journey.
     */
    public function test_journey_distance_sums_three_known_tunisian_cities(): void
    {
        $journey = $this->createJourneyWithLocations([
            ['Tunis', 'TN'],
            ['Sousse', 'TN'],
            ['Sfax', 'TN'],
        ]);

        $this->assertSame(237, $journey->recalculateDistance());
        $this->assertSame('237.0', $journey->fresh()->total_distance_km);
    }

    /**
     * Sum consecutive distances for an international journey.
     */
    public function test_journey_distance_supports_international_cities(): void
    {
        $journey = $this->createJourneyWithLocations([
            ['Paris', 'FR'],
            ['Lyon', 'FR'],
            ['Marseille', 'FR'],
        ]);

        $this->assertSame(669, $journey->recalculateDistance());
        $this->assertSame('669.0', $journey->fresh()->total_distance_km);
    }

    /**
     * Use a logged 500 km fallback when either city is missing from the reference.
     */
    public function test_unknown_city_uses_the_five_hundred_kilometre_fallback(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'Ville inconnue dans le référentiel : VilleInconnueXYZ',
                \Mockery::type('array'),
            );

        $distance = app(DistanceCalculator::class)->distanceBetween(
            'VilleInconnueXYZ',
            'Sousse',
            'TN',
            'TN',
        );

        $this->assertSame(500, $distance);
        $this->assertDatabaseCount('city_distances', 0);
    }

    /**
     * Reuse a canonical cached pair for repeated calls in either direction.
     */
    public function test_distance_is_cached_once_for_a_canonical_city_pair(): void
    {
        $calculator = app(DistanceCalculator::class);

        $forwardDistance = $calculator->distanceBetween('Tunis', 'Sousse', 'TN', 'TN');
        $reverseDistance = $calculator->distanceBetween('Sousse', 'Tunis', 'TN', 'TN');

        $this->assertSame($forwardDistance, $reverseDistance);
        $this->assertSame(116, $forwardDistance);
        $this->assertDatabaseCount('city_distances', 1);

        $cachedPair = CityDistance::query()->firstOrFail();
        $this->assertLessThan($cachedPair->city_b_id, $cachedPair->city_a_id);
    }

    /**
     * Check the straight-line Haversine result for Tunis and Sousse.
     */
    public function test_tunis_to_sousse_uses_great_circle_haversine_distance(): void
    {
        $distance = app(DistanceCalculator::class)->distanceBetween('Tunis', 'Sousse', 'TN', 'TN');

        $this->assertEqualsWithDelta(116, $distance, 10);
    }

    /**
     * Return zero for a journey with no recorded steps.
     */
    public function test_journey_without_steps_has_zero_distance(): void
    {
        $journey = Journey::factory()->create(['total_distance_km' => 42]);

        $this->assertSame(0, $journey->recalculateDistance());
        $this->assertSame('0.0', $journey->fresh()->total_distance_km);
    }

    /**
     * Return zero for a journey with only one recorded step.
     */
    public function test_journey_with_one_step_has_zero_distance(): void
    {
        $journey = $this->createJourneyWithLocations([['Tunis', 'TN']]);

        $this->assertSame(0, $journey->recalculateDistance());
        $this->assertSame('0.0', $journey->fresh()->total_distance_km);
        $this->assertDatabaseCount('city_distances', 0);
    }

    /**
     * Ignore steps with an empty city while summing the remaining route.
     */
    public function test_steps_without_a_location_are_ignored(): void
    {
        $journey = Journey::factory()->create();
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'location' => '',
        ]);
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 2,
            'location' => 'Tunis',
            'country_code' => 'TN',
        ]);
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 3,
            'location' => 'Sousse',
            'country_code' => 'TN',
        ]);

        $this->assertSame(116, $journey->recalculateDistance());
    }

    /**
     * Verify that accented names and hyphenated names resolve consistently.
     */
    public function test_city_names_are_normalized_before_lookup(): void
    {
        $this->assertSame(
            City::normalizeName('São-Paulo'),
            City::normalizeName('Sao Paulo'),
        );

        $this->assertSame(
            0,
            app(DistanceCalculator::class)->distanceBetween('Sao-Paulo', 'São Paulo', 'BR', 'BR'),
        );
    }

    /**
     * Create a journey and its ordered steps for distance calculation.
     *
     * @param  list<array{0: string, 1: string}>  $locations
     */
    private function createJourneyWithLocations(array $locations): Journey
    {
        $journey = Journey::factory()->create();

        foreach ($locations as $index => [$location, $countryCode]) {
            JourneyStep::factory()->create([
                'journey_id' => $journey->id,
                'step_order' => $index + 1,
                'location' => $location,
                'country_code' => $countryCode,
            ]);
        }

        return $journey->fresh();
    }
}

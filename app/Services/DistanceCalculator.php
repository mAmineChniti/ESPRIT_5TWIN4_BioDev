<?php

namespace App\Services;

use App\Models\City;
use App\Models\CityDistance;
use Illuminate\Support\Facades\Log;

class DistanceCalculator
{
    /**
     * Return the cached or calculated great-circle distance between two cities.
     *
     * Unknown cities use a documented flat 500 km fallback so a missing
     * reference entry does not prevent a traceability journey from being saved.
     */
    public function distanceBetween(
        string $cityA,
        string $cityB,
        ?string $countryA = null,
        ?string $countryB = null,
    ): int {
        $firstCity = $this->findCity($cityA, $countryA);
        $secondCity = $this->findCity($cityB, $countryB);

        if ($firstCity === null || $secondCity === null) {
            return 500;
        }

        if ($firstCity->is($secondCity)) {
            return 0;
        }

        $cityAId = min($firstCity->id, $secondCity->id);
        $cityBId = max($firstCity->id, $secondCity->id);
        $cachedDistance = CityDistance::query()
            ->where('city_a_id', $cityAId)
            ->where('city_b_id', $cityBId)
            ->value('distance_km');

        if ($cachedDistance !== null) {
            return (int) $cachedDistance;
        }

        $distanceInKilometers = $this->haversineDistance($firstCity, $secondCity);

        return CityDistance::query()->firstOrCreate(
            [
                'city_a_id' => $cityAId,
                'city_b_id' => $cityBId,
            ],
            ['distance_km' => $distanceInKilometers],
        )->distance_km;
    }

    /**
     * Find a city by its normalized name and optional ISO 3166-1 alpha-2 code.
     */
    private function findCity(string $name, ?string $countryCode): ?City
    {
        $normalizedName = City::normalizeName($name);
        $query = City::query()->where('name_normalized', $normalizedName);

        if ($countryCode !== null && trim($countryCode) !== '') {
            $query->where('country_code', strtoupper(trim($countryCode)));
        }

        $city = $query->orderBy('id')->first();

        if ($city === null) {
            Log::warning('Ville inconnue dans le référentiel : '.$name, [
                'city' => $name,
                'country_code' => $countryCode,
            ]);
        }

        return $city;
    }

    /**
     * Calculate great-circle distance using the haversine formula and Earth's
     * mean radius of 6,371 km, rounded to the nearest kilometre.
     */
    private function haversineDistance(City $firstCity, City $secondCity): int
    {
        $latitudeDifference = deg2rad($secondCity->latitude - $firstCity->latitude);
        $longitudeDifference = deg2rad($secondCity->longitude - $firstCity->longitude);
        $firstLatitude = deg2rad($firstCity->latitude);
        $secondLatitude = deg2rad($secondCity->latitude);

        $haversine = sin($latitudeDifference / 2) ** 2
            + cos($firstLatitude) * cos($secondLatitude) * sin($longitudeDifference / 2) ** 2;
        $haversine = min(1.0, max(0.0, $haversine));
        $centralAngle = 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));

        return (int) round(6371 * $centralAngle);
    }
}

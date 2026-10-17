<?php

namespace App\Services;

use App\Services\Geo\CountryRegistry;
use App\Services\Geo\Location;

/**
 * Where a product came from, and how far that is from the person looking at it.
 *
 * There is no longer a fixed home market: the other end of the line is wherever
 * the visitor is, which the caller resolves and passes in. The origin end comes
 * from the product record, which stores a country as whatever the producer
 * typed, and is resolved by the country registry.
 *
 * The distance is computed here rather than in the browser on purpose: it makes
 * the figure real, server-rendered content that a screen reader can read and a
 * test can assert. The map script only draws the line the same numbers describe,
 * so there is one source of truth. The browser may later refine the visitor end
 * with a precise fix, and redraw to match, but it never invents a figure the
 * server did not already have.
 */
class OriginRegistry
{
    /**
     * Mean earth radius, kilometres.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Below this the product counts as local and is not drawn as an import.
     *
     * Only consulted when the two ends cannot be compared by country, which is
     * the case for a browser fix, since a precise position carries no country
     * code. A few dozen kilometres still means the same thing to a shopper: it
     * did not cross a border, so no route line is shown.
     */
    private const LOCAL_THRESHOLD_KM = 50.0;

    public function __construct(private readonly CountryRegistry $countries) {}

    /**
     * Great-circle distance between two points, in kilometres.
     */
    public function distanceBetween(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $toRadians = M_PI / 180;

        $deltaLat = ($lat2 - $lat1) * $toRadians;
        $deltaLng = ($lng2 - $lng1) * $toRadians;

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1 * $toRadians) * cos($lat2 * $toRadians) * sin($deltaLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * Everything the view needs to describe and draw one product's origin.
     *
     * @return array{
     *     origin: ?string,
     *     isKnown: bool,
     *     isLocal: bool,
     *     isImported: bool,
     *     distanceKm: ?int,
     *     originPoint: ?array<string, mixed>,
     *     viewer: ?array<string, mixed>
     * }
     */
    public function describe(?string $origin, ?Location $viewer = null): array
    {
        $originPoint = $this->countries->resolve($origin);

        if ($originPoint === null) {
            return [
                'origin' => $origin !== null && trim($origin) !== '' ? trim($origin) : null,
                'isKnown' => false,
                'isLocal' => false,
                'isImported' => false,
                'distanceKm' => null,
                'originPoint' => null,
                'viewer' => $viewer?->toArray(),
            ];
        }

        $distanceKm = $viewer === null
            ? null
            : (int) round($this->distanceBetween(
                $viewer->lat,
                $viewer->lng,
                $originPoint->lat,
                $originPoint->lng,
            ));

        $isLocal = $this->isLocal($viewer, $originPoint, $distanceKm);

        return [
            'origin' => trim((string) $origin),
            'isKnown' => true,
            'isLocal' => $isLocal,
            // Only an import gets a distance: a local product has no leg to
            // measure, and a figure here would imply freight that never happens.
            // Equally, with no visitor there is no leg at all, so nothing may
            // claim one was measured.
            'isImported' => $viewer !== null && ! $isLocal,
            'distanceKm' => $isLocal ? null : $distanceKm,
            'originPoint' => $originPoint->toArray(),
            'viewer' => $viewer?->toArray(),
        ];
    }

    /**
     * Whether the product reached the visitor without crossing a border.
     */
    private function isLocal(?Location $viewer, Location $originPoint, ?int $distanceKm): bool
    {
        if ($viewer === null) {
            return false;
        }

        // Two known country codes settle it exactly, including for neighbours
        // that sit a few dozen kilometres apart across a frontier.
        if ($viewer->countryCode !== null && $originPoint->countryCode !== null) {
            return $viewer->countryCode === $originPoint->countryCode;
        }

        // Otherwise only the distance is available to judge by.
        return $distanceKm !== null && $distanceKm <= self::LOCAL_THRESHOLD_KM;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Where a product came from, and how far that is from the market it ships into.
 *
 * The registry is keyed on English country names because that is what the
 * catalog stores. The lookup is normalised first, so "BRÉSIL", "Brasil" and
 * "Brazil" all resolve without every alias being listed.
 *
 * The distance is computed here rather than in the browser on purpose: it makes
 * the figure real, server-rendered content that a screen reader can read and a
 * test can assert. The map script only draws the line the same numbers describe,
 * so there is one source of truth.
 */
class OriginRegistry
{
    /**
     * The market NutriTrace ships into. Every route is measured against it.
     *
     * @var array{name: string, lat: float, lng: float}
     */
    public const HOME = ['name' => 'Tunisia', 'lat' => 36.8065, 'lng' => 10.1815];

    /**
     * Mean earth radius, kilometres.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Below this the product counts as local and is not drawn as an import.
     *
     * A few dozen kilometres still means the same thing to a shopper: it did not
     * cross a border, so no route line is shown.
     */
    private const LOCAL_THRESHOLD_KM = 50.0;

    /**
     * Country name => [latitude, longitude].
     *
     * Capital or centroid coordinates; precise enough to draw a route.
     *
     * @var array<string, array{float, float}>
     */
    private const COORDINATES = [
        'algeria' => [28.0339, 1.6596],
        'argentina' => [-34.6037, -58.3816],
        'australia' => [-33.8688, 151.2093],
        'belgium' => [50.8503, 4.3517],
        'brazil' => [-14.2350, -51.9253],
        'canada' => [43.6532, -79.3832],
        'china' => [35.8617, 104.1954],
        'denmark' => [55.6761, 12.5683],
        'egypt' => [26.8206, 30.8025],
        'france' => [46.2276, 2.2137],
        'germany' => [52.5200, 13.4050],
        'greece' => [37.9838, 23.7275],
        'india' => [28.6139, 77.2090],
        'ireland' => [53.3498, -6.2603],
        'italy' => [41.8719, 12.5674],
        'japan' => [35.6762, 139.6503],
        'mexico' => [19.4326, -99.1332],
        'morocco' => [31.7917, -7.0926],
        'netherlands' => [52.3676, 4.9041],
        'poland' => [52.2297, 21.0122],
        'portugal' => [38.7223, -9.1393],
        'south africa' => [-26.2041, 28.0473],
        'south korea' => [37.5665, 126.9780],
        'spain' => [40.4168, -3.7038],
        'sweden' => [59.3293, 18.0686],
        'tunisia' => [36.8065, 10.1815],
        'turkey' => [39.9334, 32.8597],
        'ukraine' => [50.4501, 30.5234],
        'united kingdom' => [51.5074, -0.1278],
        'united states' => [39.8283, -98.5795],
        'vietnam' => [14.0583, 108.2772],
    ];

    /**
     * Spellings that normalise to something other than the canonical key.
     *
     * Keys are written in their normalised form — lower case, accents as ASCII,
     * punctuation collapsed to single spaces — because that is what they are
     * compared against. These are the genuinely different names: endonyms that
     * are not the English name, and the abbreviations people actually type.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'brasil' => 'brazil',
        'bresil' => 'brazil',
        'espana' => 'spain',
        'allemagne' => 'germany',
        'italia' => 'italy',
        'turkiye' => 'turkey',
        'nederland' => 'netherlands',
        'holland' => 'netherlands',
        'the netherlands' => 'netherlands',
        'maroc' => 'morocco',
        'tunisie' => 'tunisia',
        'grece' => 'greece',
        'etats unis' => 'united states',
        'usa' => 'united states',
        'us' => 'united states',
        'america' => 'united states',
        'uk' => 'united kingdom',
        'great britain' => 'united kingdom',
        'england' => 'united kingdom',
        'korea' => 'south korea',
        'republic of korea' => 'south korea',
    ];

    /**
     * Reduce an origin string to a lookup key.
     *
     * Accents become their ASCII letter so "BRÉSIL" reduces to "bresil" and so
     * resolves to Brazil, rather than needing an alias for every accented
     * spelling.
     */
    public function normalise(?string $origin): string
    {
        $key = mb_strtolower(trim((string) $origin));

        if ($key === '') {
            return '';
        }

        // Latin accents to ASCII. Str::ascii does not need the intl extension.
        $key = Str::ascii($key);

        // Anything that is not a letter or digit becomes a space.
        $key = (string) preg_replace('/[^a-z0-9]+/', ' ', $key);

        return trim($key);
    }

    /**
     * The coordinates for an origin, or null when the country is not known.
     *
     * @return array{name: string, lat: float, lng: float}|null
     */
    public function coordinatesFor(?string $origin): ?array
    {
        $key = $this->normalise($origin);

        if ($key === '') {
            return null;
        }

        $key = self::ALIASES[$key] ?? $key;

        if (! isset(self::COORDINATES[$key])) {
            return null;
        }

        [$lat, $lng] = self::COORDINATES[$key];

        // Str::title, not ucfirst: ucfirst('united states') is "United states".
        return ['name' => Str::title($key), 'lat' => $lat, 'lng' => $lng];
    }

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
     *     distanceKm: ?int,
     *     origin_: ?array{name: string, lat: float, lng: float},
     *     home: array{name: string, lat: float, lng: float}
     * }
     */
    public function describe(?string $origin): array
    {
        $coordinates = $this->coordinatesFor($origin);

        if ($coordinates === null) {
            return [
                'origin' => $origin !== null && trim($origin) !== '' ? trim($origin) : null,
                'isKnown' => false,
                'isLocal' => false,
                'distanceKm' => null,
                'origin_' => null,
                'home' => self::HOME,
            ];
        }

        $distanceKm = (int) round($this->distanceBetween(
            $coordinates['lat'],
            $coordinates['lng'],
            self::HOME['lat'],
            self::HOME['lng'],
        ));

        return [
            'origin' => trim((string) $origin),
            'isKnown' => true,
            // Local means the same place, not merely nearby.
            'isLocal' => $distanceKm <= self::LOCAL_THRESHOLD_KM,
            'distanceKm' => $distanceKm,
            'origin_' => $coordinates,
            'home' => self::HOME,
        ];
    }

    /**
     * The distance rounded for display, or null when there is nothing to show.
     */
    public function displayDistance(?string $origin): ?string
    {
        $description = $this->describe($origin);

        if (! $description['isKnown'] || $description['isLocal']) {
            return null;
        }

        $km = $description['distanceKm'];

        // Reads better to a shopper than 1,640 kilometres of digits.
        return $km >= 1000
            ? number_format($km / 1000, 1).' thousand kilometres'
            : $km.' kilometres';
    }
}
<?php

namespace App\Services\Geo;

use DateTimeZone;
use Illuminate\Http\Request;
use Throwable;

/**
 * Where the visitor is, resolved as precisely as the situation allows.
 *
 * Three tiers, most accurate first. The map tries each until one answers, and
 * the view is told which tier answered so it can describe the figure honestly
 * rather than implying a street-level fix it does not have:
 *
 *   1. Browser  — the visitor pressed the button and allowed geolocation.
 *                 Precise, client-side, and entirely optional.
 *   2. IP       — resolved on the server from the request address. Good to a
 *                 city, and the reason the distance needs no JavaScript.
 *   3. Timezone — the visitor's browser reports its zone and the server maps
 *                 that to a country. Good to a country, nothing more.
 *
 * The first two are cached; the third is resolved per call and costs nothing
 * beyond a timezone lookup. Any tier may return null, and null is a legitimate
 * answer that the view states rather than papering over.
 */
class VisitorLocation
{
    private ?Location $current = null;

    private bool $resolved = false;

    public function __construct(
        private readonly IpGeolocator $ip,
        private readonly CountryRegistry $countries,
    ) {}

    /**
     * What the server can tell about the visitor before any client input.
     */
    public function current(?Request $request = null): ?Location
    {
        if (! $this->resolved) {
            $this->current = $this->ip->locate(($request ?? request())->ip());
            $this->resolved = true;
        }

        return $this->current;
    }

    /**
     * The country a browser timezone points at, or null when it points nowhere.
     *
     * A zone names a place on the ground, so it resolves to the same
     * representative point the origin side uses for that country. That keeps
     * both ends of the line drawn at the same granularity, which is what makes
     * the distance between them mean anything.
     */
    public function fromTimezone(?string $timezone): ?Location
    {
        $timezone = is_string($timezone) ? trim($timezone) : '';

        if ($timezone === '' || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return null;
        }

        try {
            $countryCode = strtoupper((string) ((new DateTimeZone($timezone))->getLocation()['country_code'] ?? ''));
        } catch (Throwable) {
            return null;
        }

        $point = $this->countries->representativePoint($countryCode);

        if ($point === null) {
            return null;
        }

        return new Location(
            name: $point->name,
            lat: $point->lat,
            lng: $point->lng,
            countryCode: $point->countryCode,
            source: 'timezone',
        );
    }
}

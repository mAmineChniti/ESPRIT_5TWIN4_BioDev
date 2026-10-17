<?php

namespace App\Services\Geo;

/**
 * One point on the earth, and how we came to believe in it.
 *
 * The source is carried alongside the coordinates because the map has to be
 * honest about precision: a figure derived from an IP address is good to a
 * city, one taken from the browser's geolocation is good to a street, and the
 * copy in front of the visitor should not claim the first is the second.
 */
final readonly class Location
{
    public function __construct(
        public string $name,
        public float $lat,
        public float $lng,
        public ?string $countryCode = null,
        public string $source = 'unknown',
    ) {}

    /**
     * Whether this point came from the browser rather than from the network.
     *
     * Only a browser fix is precise enough to describe as the visitor's exact
     * position; an IP lookup is an approximation of the city they are in.
     */
    public function isPrecise(): bool
    {
        return $this->source === 'browser';
    }

    /**
     * The shape the view and the map script consume.
     *
     * @return array{name: string, lat: float, lng: float, countryCode: ?string, source: string, precise: bool}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'countryCode' => $this->countryCode,
            'source' => $this->source,
            'precise' => $this->isPrecise(),
        ];
    }
}

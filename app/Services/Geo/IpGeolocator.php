<?php

namespace App\Services\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a visitor's IP address into an approximate point.
 *
 * This is the coarse half of the location story: it runs on the server so the
 * distance in the copy is real content that survives without JavaScript, and
 * it is only ever an approximation of the city, never a street. The browser's
 * own geolocation refines it when the visitor asks for that.
 *
 * It is deliberately hard to make this slow or noisy:
 *
 *   - An address that is private, loopback or reserved never leaves the
 *     server, which also keeps the test suite from reaching the network.
 *   - The answer is cached per address, so a visitor pays one lookup a day
 *     rather than one per page view.
 *   - A provider that is slow, down or unconfigured costs the visitor a coarse
 *     map, never a failed page render.
 */
class IpGeolocator
{
    /**
     * Locate an IP address, or null when it cannot or should not be located.
     */
    public function locate(?string $ip): ?Location
    {
        if (! $this->isEnabled() || ! $this->isRoutable($ip)) {
            return null;
        }

        $payload = Cache::remember(
            $this->cacheKey($ip),
            max(1, (int) config('geo.ip.cache_ttl', 86400)),
            fn (): array => $this->fetch($ip),
        );

        return $this->toLocation(is_array($payload) ? $payload : null);
    }

    /**
     * Whether an address is one worth asking a third party about.
     *
     * Private, loopback and reserved ranges describe the network between the
     * visitor and this server, not where the visitor is.
     */
    public function isRoutable(?string $ip): bool
    {
        if (! is_string($ip) || trim($ip) === '') {
            return false;
        }

        return filter_var(
            trim($ip),
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * Ask the provider, returning an empty array when it cannot answer.
     *
     * @return array<string, mixed>
     */
    private function fetch(string $ip): array
    {
        try {
            $response = Http::timeout((int) config('geo.ip.timeout', 3))
                ->acceptJson()
                ->get(rtrim((string) config('geo.ip.endpoint'), '/').'/'.rawurlencode($ip));

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : [];
        } catch (Throwable $e) {
            // A page render must not fail because a geolocation provider did.
            Log::debug('IP geolocation lookup failed.', ['exception' => $e]);

            return [];
        }
    }

    /**
     * Interpret a provider response as a point, or null when it is not one.
     *
     * @param  array<string, mixed>|null  $payload
     */
    private function toLocation(?array $payload): ?Location
    {
        if ($payload === null || ($payload['success'] ?? false) !== true) {
            return null;
        }

        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        $city = trim((string) ($payload['city'] ?? ''));
        $region = trim((string) ($payload['region'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));

        // The most specific label the provider actually gave us.
        $name = implode(', ', array_filter([trim($city), $country !== $city ? $country : '']))
            ?: ($region !== '' ? $region : 'your location');

        return new Location(
            name: $name,
            lat: $lat,
            lng: $lng,
            countryCode: isset($payload['country_code']) ? strtoupper(trim((string) $payload['country_code'])) : null,
            source: 'ip',
        );
    }

    /**
     * Whether the lookup is switched on at all.
     */
    private function isEnabled(): bool
    {
        return (bool) config('geo.ip.enabled', true) && filled(config('geo.ip.endpoint'));
    }

    /**
     * A cache key that does not put a visitor's address into the key space.
     */
    private function cacheKey(string $ip): string
    {
        return 'geo:ip:'.hash('sha256', $ip);
    }
}

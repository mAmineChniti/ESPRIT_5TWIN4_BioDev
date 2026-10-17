<?php

namespace App\Services\Geo;

use ALameLlama\Geographer\Country;
use ALameLlama\Geographer\Earth;
use DateTimeZone;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns a country written as free text into a point on the map.
 *
 * A product records where it came from as whatever the producer typed, so the
 * lookup has to survive accents, punctuation, loose spacing, endonyms and ISO
 * codes. Two sources answer that, and neither is complete on its own:
 *
 *   - Names come from Geographer, which carries the short name, the long name
 *     and all three ISO 3166-1 codes for every country in the standard. It is
 *     English only, so the aliases below cover what it cannot see.
 *   - Coordinates come from PHP's own timezone database, where each zone
 *     records the country it belongs to and a representative point for it. That
 *     is a city rather than a capital, which is the right granularity for a
 *     country-to-country figure and, unlike a capital lookup, is never missing
 *     because the capital is too small to make a list of large cities.
 *
 * Both indexes are derived data over files that ship with the package and with
 * PHP itself, so they are built once per process and then reused.
 */
class CountryRegistry
{
    /**
     * The zone database's marker for a timezone that belongs to no country.
     */
    private const NO_COUNTRY = '??';

    /**
     * Country code => label, for the countries a visitor might type instead.
     *
     * Geographer answers in English only; these are the genuinely different
     * names: endonyms, and the abbreviations people actually type. Keys are
     * already normalised, because that is what they are compared against.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        // Endonyms.
        'allemagne' => 'DE',
        'brasil' => 'BR',
        'bresil' => 'BR',
        'deutschland' => 'DE',
        'espana' => 'ES',
        'grece' => 'GR',
        'holland' => 'NL',
        'italia' => 'IT',
        'maroc' => 'MA',
        'nederland' => 'NL',
        'osterreich' => 'AT',
        'polska' => 'PL',
        'schweiz' => 'CH',
        'suisse' => 'CH',
        'turkiye' => 'TR',
        'tunisie' => 'TN',
        // Names the English list does not use.
        'south korea' => 'KR',
        'north korea' => 'KP',
        'republic of korea' => 'KR',
        'korea' => 'KR',
        'russia' => 'RU',
        'the netherlands' => 'NL',
        'ivory coast' => 'CI',
        'czech republic' => 'CZ',
        'vatican' => 'VA',
        'holy see' => 'VA',
        // The abbreviations people type.
        'uk' => 'GB',
        'great britain' => 'GB',
        'england' => 'GB',
        'scotland' => 'GB',
        'wales' => 'GB',
        'america' => 'US',
        'etats unis' => 'US',
        'etats-unis' => 'US',
        'usa' => 'US',
        'us' => 'US',
        'uae' => 'AE',
        'ua' => 'UA',
    ];

    /**
     * The zone to represent countries that span several.
     *
     * Without this the first zone in alphabetical order wins, which is how
     * America/Adak ends up standing in for the United States. These are the
     * country's principal or most populous zone, chosen by hand.
     *
     * @var array<string, string>
     */
    private const PRINCIPAL_ZONE = [
        'AQ' => 'Antarctica/McMurdo',
        'AR' => 'America/Argentina/Buenos_Aires',
        'AU' => 'Australia/Sydney',
        'BR' => 'America/Sao_Paulo',
        'CA' => 'America/Toronto',
        'CL' => 'America/Santiago',
        'CN' => 'Asia/Shanghai',
        'DE' => 'Europe/Berlin',
        'ES' => 'Europe/Madrid',
        'FM' => 'Pacific/Pohnpei',
        'GL' => 'America/Nuuk',
        'ID' => 'Asia/Jakarta',
        'KI' => 'Pacific/Tarawa',
        'MH' => 'Pacific/Majuro',
        'MN' => 'Asia/Ulaanbaatar',
        'MX' => 'America/Mexico_City',
        'PG' => 'Pacific/Port_Moresby',
        'PF' => 'Pacific/Tahiti',
        'PT' => 'Europe/Lisbon',
        'RU' => 'Europe/Moscow',
        'UA' => 'Europe/Kyiv',
        'UM' => 'Pacific/Wake',
        'US' => 'America/New_York',
    ];

    /**
     * Normalised name or code => ISO 3166-1 alpha-2.
     *
     * @var array<string, string>|null
     */
    private static ?array $index = null;

    /**
     * ISO 3166-1 alpha-2 => country display name.
     *
     * @var array<string, string>|null
     */
    private static ?array $labels = null;

    /**
     * ISO 3166-1 alpha-2 => [latitude, longitude].
     *
     * @var array<string, array{float, float}>|null
     */
    private static ?array $points = null;

    /**
     * Reduce free text to a comparable lookup key.
     *
     * Accents become their ASCII letter so "BRÉSIL" reduces to "bresil" and so
     * resolves, rather than needing an alias for every accented spelling.
     */
    public function normalise(?string $value): string
    {
        $key = mb_strtolower(trim((string) $value));

        if ($key === '') {
            return '';
        }

        // Str::ascii does not need the intl extension.
        $key = Str::ascii($key);

        // Anything that is not a letter or digit becomes a space.
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $key));
    }

    /**
     * The ISO 3166-1 alpha-2 code for a country written as free text.
     *
     * Accepts a name in any of the spellings the catalog holds, or one of the
     * three ISO codes. Returns null when the text names no known country,
     * which is a real answer: a misspelling must not resolve to a neighbour.
     */
    public function isoCodeFor(?string $value): ?string
    {
        $key = $this->normalise($value);

        if ($key === '') {
            return null;
        }

        return self::index()[$key] ?? null;
    }

    /**
     * The point representing a country, or null when there is nothing to plot.
     */
    public function representativePoint(?string $isoCode): ?Location
    {
        if ($isoCode === null) {
            return null;
        }

        $isoCode = strtoupper($isoCode);

        $coordinates = self::points()[$isoCode] ?? null;

        if ($coordinates === null) {
            return null;
        }

        return new Location(
            name: self::labels()[$isoCode] ?? $isoCode,
            lat: $coordinates[0],
            lng: $coordinates[1],
            countryCode: $isoCode,
            source: 'country',
        );
    }

    /**
     * The point for a country written as free text, or null when unknown.
     */
    public function resolve(?string $value): ?Location
    {
        return $this->representativePoint($this->isoCodeFor($value));
    }

    /**
     * Name and code => ISO code, for every country Geographer knows.
     *
     * @return array<string, string>
     */
    private static function index(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }

        $index = self::ALIASES;
        $labels = [];

        try {
            $countries = (new Earth)->getCountries();

            foreach ($countries as $country) {
                $isoCode = strtoupper((string) ($country['isoCode'] ?? ''));

                if ($isoCode === '') {
                    continue;
                }

                $labels[$isoCode] = self::nameOf($country, 'getShortName') ?? $isoCode;

                // Every spelling of the name the catalog might hold.
                foreach (['getShortName', 'getLongName'] as $accessor) {
                    $name = self::nameOf($country, $accessor);

                    if ($name !== null) {
                        $index[self::flatten($name)] = $isoCode;
                    }
                }

                // All three ISO codes, keyed the way normalise() keys text.
                foreach (['isoCode', 'code3', 'numericCode'] as $field) {
                    $code = mb_strtolower(trim((string) ($country[$field] ?? '')));

                    if ($code !== '') {
                        $index[$code] = $isoCode;
                    }
                }
            }
        } catch (Throwable) {
            // Without the country list there is nothing to resolve against,
            // and an unresolvable origin is a state the view already handles.
        }

        self::$labels = $labels;
        self::$index = $index;

        return self::$index;
    }

    /**
     * ISO code => the country's display name.
     *
     * @return array<string, string>
     */
    private static function labels(): array
    {
        if (self::$labels === null) {
            self::index();
        }

        return self::$labels ?? [];
    }

    /**
     * ISO code => a representative point, taken from PHP's timezone database.
     *
     * @return array<string, array{float, float}>
     */
    private static function points(): array
    {
        if (self::$points !== null) {
            return self::$points;
        }

        $byCountry = [];

        foreach (timezone_identifiers_list() as $zone) {
            $location = (new DateTimeZone($zone))->getLocation();
            $countryCode = strtoupper((string) ($location['country_code'] ?? ''));

            // The zone database writes "??" for a zone that belongs to no
            // country, such as UTC and the Etc/GMT offsets. Those carry a
            // sentinel coordinate at the south pole, so indexing one would
            // give a real looking key a point in the Antarctic Ocean.
            if ($countryCode === '' || $countryCode === self::NO_COUNTRY) {
                continue;
            }

            $byCountry[$countryCode][$zone] = [(float) $location['latitude'], (float) $location['longitude']];
        }

        $points = [];

        foreach (array_keys($byCountry) as $countryCode) {
            $zones = $byCountry[$countryCode];

            // A country that spans several zones is represented by its
            // principal one; the rest fall through to their only zone.
            $chosen = self::PRINCIPAL_ZONE[$countryCode] ?? array_key_first($zones);

            if (isset($zones[$chosen])) {
                $points[$countryCode] = $zones[$chosen];
            }
        }

        return self::$points = $points;
    }

    /**
     * A country name in English, or null when the list has no such form.
     */
    private static function nameOf(Country $country, string $accessor): ?string
    {
        try {
            $name = $country->{$accessor}();
        } catch (Throwable) {
            return null;
        }

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    /**
     * Reduce a country name to the same shape normalise() produces.
     */
    private static function flatten(string $name): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', Str::ascii(mb_strtolower($name))));
    }
}

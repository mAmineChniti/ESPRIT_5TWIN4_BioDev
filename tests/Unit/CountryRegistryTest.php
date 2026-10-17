<?php

namespace Tests\Unit;

use ALameLlama\Geographer\Earth;
use App\Services\Geo\CountryRegistry;
use App\Services\Geo\Location;
use Tests\TestCase;

/**
 * Resolving a country written as free text into a point on the map.
 */
class CountryRegistryTest extends TestCase
{
    private function registry(): CountryRegistry
    {
        return new CountryRegistry;
    }

    private function viewer(string $name, float $lat, float $lng, ?string $countryCode = null): Location
    {
        return new Location($name, $lat, $lng, $countryCode, 'ip');
    }

    // ---------- Lookup ----------

    public function test_it_resolves_the_catalogs_origins(): void
    {
        foreach (['France', 'Spain', 'Italy', 'Morocco', 'Greece', 'Tunisia'] as $origin) {
            $this->assertNotNull(
                $this->registry()->resolve($origin),
                "No coordinates for {$origin}, which the seeder uses."
            );
        }
    }

    public function test_it_resolves_the_spellings_the_catalog_might_hold(): void
    {
        $registry = $this->registry();

        // Case, accents and punctuation must not defeat the lookup.
        $this->assertSame('BR', $registry->isoCodeFor('BRÉSIL'));
        $this->assertSame('BR', $registry->isoCodeFor('Brasil'));
        $this->assertSame('BR', $registry->isoCodeFor('brazil'));
        $this->assertSame('FR', $registry->isoCodeFor('  france  '));
        $this->assertSame('ES', $registry->isoCodeFor('España'));

        // Endonyms, which the country list does not carry.
        $this->assertSame('TN', $registry->isoCodeFor('Tunisie'));
        $this->assertSame('MA', $registry->isoCodeFor('Maroc'));
        $this->assertSame('DE', $registry->isoCodeFor('Deutschland'));
        $this->assertSame('NL', $registry->isoCodeFor('Holland'));

        // The abbreviations people actually type.
        $this->assertSame('US', $registry->isoCodeFor('USA'));
        $this->assertSame('GB', $registry->isoCodeFor('UK'));
        $this->assertSame('GB', $registry->isoCodeFor('Great Britain'));
        $this->assertSame('KR', $registry->isoCodeFor('South Korea'));
    }

    public function test_it_resolves_all_three_iso_codes(): void
    {
        $registry = $this->registry();

        $this->assertSame('FR', $registry->isoCodeFor('FR'));
        $this->assertSame('FR', $registry->isoCodeFor('fr'));
        $this->assertSame('FR', $registry->isoCodeFor('FRA'));
        $this->assertSame('FR', $registry->isoCodeFor('250'));
    }

    public function test_an_unknown_or_missing_origin_resolves_to_nothing(): void
    {
        $registry = $this->registry();

        foreach ([null, '', '   ', 'Atlantis', 'Narnia'] as $origin) {
            $this->assertNull($registry->resolve($origin), "Resolved {$origin} to a country.");
        }
    }

    // ---------- Coverage ----------

    public function test_every_country_in_the_standard_resolves(): void
    {
        $registry = $this->registry();
        $unresolved = [];

        foreach ((new Earth)->getCountries() as $country) {
            if ($registry->resolve($country->getShortName()) === null) {
                $unresolved[] = $country->getShortName();
            }
        }

        // Bouvet Island and Heard Island have no timezone, so PHP's database
        // holds no coordinates for them. Both are uninhabited, which is why
        // nothing anywhere needs to plot them.
        $this->assertSame(
            ['Bouvet Island', 'Heard Island and McDonald Islands'],
            $unresolved,
        );
    }

    public function test_a_country_spanning_many_zones_is_plotted_in_its_principal_one(): void
    {
        $registry = $this->registry();

        // Alphabetic order would put the United States in America/Adak, the
        // Aleutian Islands, which is over 8,000 km from where anyone lives.
        $unitedStates = $registry->resolve('United States');
        $this->assertNotNull($unitedStates);
        $this->assertGreaterThan(30, $unitedStates->lat);
        $this->assertLessThan(50, $unitedStates->lat);
        $this->assertGreaterThan(-125, $unitedStates->lng);
        $this->assertLessThan(-65, $unitedStates->lng);

        // And the same for the other countries that do not share one zone.
        foreach (['RU', 'CA', 'AU', 'BR', 'AR', 'MX', 'CL', 'DE', 'ES', 'PT', 'ID', 'CN'] as $iso) {
            $point = $registry->representativePoint($iso);
            $this->assertNotNull($point, "No point for {$iso}.");
            $this->assertTrue(
                $point->lat >= -60 && $point->lat <= 75 && $point->lng >= -180 && $point->lng <= 180,
                "{$iso} plotted outside the world at {$point->lat}, {$point->lng}."
            );
        }
    }

    // ---------- The viewer end ----------

    public function test_the_viewer_comes_from_the_request_and_the_origin_from_the_product(): void
    {
        $registry = $this->registry();

        $origin = $registry->resolve('Brazil');
        $viewer = $this->viewer('Paris, France', 48.8566, 2.3522, 'FR');

        $this->assertSame('BR', $origin->countryCode);
        $this->assertSame('FR', $viewer->countryCode);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use App\Services\OriginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where a product came from, and how far that is from Tunisia.
 */
class OriginMapTest extends TestCase
{
    use RefreshDatabase;

    private function registry(): OriginRegistry
    {
        return new OriginRegistry;
    }

    // ---------- Lookup ----------

    public function test_it_resolves_the_catalogs_origins(): void
    {
        foreach (['France', 'Spain', 'Italy', 'Morocco', 'Greece', 'Tunisia'] as $origin) {
            $this->assertNotNull(
                $this->registry()->coordinatesFor($origin),
                "No coordinates for {$origin}, which the seeder uses."
            );
        }
    }

    public function test_it_resolves_the_spellings_the_catalog_might_hold(): void
    {
        $registry = $this->registry();

        // Case, accents and punctuation must not defeat the lookup.
        $this->assertSame('Brazil', $registry->coordinatesFor('BRÉSIL')['name']);
        $this->assertSame('Brazil', $registry->coordinatesFor('Brasil')['name']);
        $this->assertSame('Brazil', $registry->coordinatesFor('brazil')['name']);
        $this->assertSame('France', $registry->coordinatesFor('  france  ')['name']);
        $this->assertSame('Spain', $registry->coordinatesFor('España')['name']);
        $this->assertSame('Tunisia', $registry->coordinatesFor('Tunisie')['name']);
        $this->assertSame('United States', $registry->coordinatesFor('USA')['name']);
        $this->assertSame('United Kingdom', $registry->coordinatesFor('UK')['name']);
        $this->assertSame('United Kingdom', $registry->coordinatesFor('Great Britain')['name']);
    }

    public function test_an_unknown_or_missing_origin_resolves_to_nothing(): void
    {
        $registry = $this->registry();

        foreach ([null, '', '   ', 'Atlantis'] as $origin) {
            $this->assertNull($registry->coordinatesFor($origin));
        }
    }

    // ---------- Distance ----------

    public function test_the_distance_is_a_real_measurement(): void
    {
        $registry = $this->registry();

        // Tunis to Tunis is zero.
        $this->assertSame(0.0, round($registry->distanceBetween(36.8065, 10.1815, 36.8065, 10.1815), 3));

        // Tunis to Paris is roughly 1,500 km, not 0 and not the circumference.
        $tunisToParis = $registry->distanceBetween(36.8065, 10.1815, 48.8566, 2.3522);
        $this->assertGreaterThan(1400, $tunisToParis);
        $this->assertLessThan(1600, $tunisToParis);

        // Symmetric in both arguments.
        $london = [-0.1278, 51.5074];
        $this->assertEqualsWithDelta(
            $registry->distanceBetween(36.8065, 10.1815, $london[0], $london[1]),
            $registry->distanceBetween($london[0], $london[1], 36.8065, 10.1815),
            0.0001
        );

        // The antipode negates the latitude and shifts the longitude by 180.
        // Halfway round the earth is half the circumference, not the width of
        // the map.
        $antipode = $registry->distanceBetween(36.8065, 10.1815, -36.8065, 10.1815 - 180);
        $this->assertEqualsWithDelta(M_PI * 6371.0, $antipode, 1.0);
    }

    public function test_a_local_product_is_not_treated_as_an_import(): void
    {
        $description = $this->registry()->describe('Tunisia');

        $this->assertTrue($description['isKnown']);
        $this->assertTrue($description['isLocal']);
        $this->assertSame(0, $description['distanceKm']);

        // Morocco is 150 km away: known, but still across a border, so it is an
        // import and keeps its distance.
        $morocco = $this->registry()->describe('Morocco');
        $this->assertTrue($morocco['isKnown']);
        $this->assertFalse($morocco['isLocal']);
        $this->assertGreaterThan(100, $morocco['distanceKm']);
    }

    // ---------- What the pages show ----------

    public function test_the_product_page_states_the_distance_in_text(): void
    {
        $food = Food::factory()->create(['origin' => 'France']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        // The figure is server-rendered, so it is readable without JavaScript
        // and assertable. The rounded kilometre count, not the abbreviated form.
        $km = $this->registry()->describe('France')['distanceKm'];
        $this->assertStringContainsString(number_format((float) $km).' km', $html);
        $this->assertStringContainsString('to Tunisia', $html);

        // And the map is drawn with the same two endpoints and the same number.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
        $this->assertStringContainsString('L.polyline([start, end]', $html);
    }

    public function test_the_back_office_page_shows_the_same_map(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);
        $food = Food::factory()->create(['origin' => 'Italy', 'producer_id' => $producer->id]);

        $html = $this->actingAs($producer)->get(route('foods.show', $food))->assertOk()->getContent();

        $km = $this->registry()->describe('Italy')['distanceKm'];
        $this->assertStringContainsString(number_format((float) $km).' km', $html);
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
    }

    public function test_a_local_product_says_so_and_plots_no_route(): void
    {
        $food = Food::factory()->create(['origin' => 'Tunisia']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('Grown in', $html);
        $this->assertStringContainsString('no import leg', $html);

        // The map is still there. The script always ships every branch, so the
        // signal is the flag the server hands it plus the copy around it.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
        $this->assertStringContainsString('const local = true;', $html);
        $this->assertStringNotContainsString(' of freight', $html);
        $this->assertDoesNotMatchRegularExpression('/[\d,]+ km/', $html);
    }

    public function test_an_unknown_origin_is_reported_rather_than_guessed(): void
    {
        $food = Food::factory()->create(['origin' => 'Atlantis']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('Atlantis', $html);
        $this->assertStringContainsString('no coordinates for it', $html);

        // The map falls back to a world view instead of pretending to plot it.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
        $this->assertStringContainsString('world view', $html);
        $this->assertStringContainsString('const known = false;', $html);
        $this->assertStringNotContainsString(' of freight', $html);
        $this->assertDoesNotMatchRegularExpression('/[\d,]+ km/', $html);
    }

    public function test_a_missing_origin_draws_nothing_at_all(): void
    {
        $food = Food::factory()->create(['origin' => null]);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('No origin is recorded', $html);
        // No map and no Leaflet request when there is nothing to plot.
        $this->assertStringNotContainsString('origin-map-', $html);
        $this->assertStringNotContainsString('leaflet', strtolower($html));
    }

    public function test_an_imported_product_draws_a_route_with_the_distance_on_it(): void
    {
        $food = Food::factory()->create(['origin' => 'Brazil']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('const known = true;', $html);
        $this->assertStringContainsString('const local = false;', $html);
        // The figure is bound onto the line itself, not only in a popup.
        $this->assertStringContainsString('bindTooltip', $html);
        $this->assertStringContainsString('map.fitBounds(line.getBounds()', $html);
    }

    public function test_the_route_line_is_not_painted_with_the_error_colour(): void
    {
        $food = Food::factory()->create(['origin' => 'Brazil']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        // destructive is this theme's error channel; a shipping route is not an
        // error, and on a dark background it reads as a fault.
        $this->assertStringContainsString('token(\'--foreground\')', $html);
        $this->assertStringNotContainsString('token(\'--destructive\')', $html);
    }
}
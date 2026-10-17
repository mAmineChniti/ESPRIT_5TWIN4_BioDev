<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use App\Services\Geo\Location;
use App\Services\OriginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Where a product came from, and how far that is from the person reading it.
 */
class OriginMapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A routable address, so the lookup is actually attempted.
     */
    private const VISITOR_IP = '8.8.8.8';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Without this a private test address could still reach the real
        // provider, so a missing fake fails loudly instead of quietly working.
        Http::preventStrayRequests();

        config([
            'geo.ip.enabled' => true,
            'geo.ip.endpoint' => 'https://geo.test',
            'geo.ip.cache_ttl' => 60,
        ]);
    }

    private function registry(): OriginRegistry
    {
        return app(OriginRegistry::class);
    }

    /**
     * Pretend the provider places the visitor in one city.
     */
    private function visitorIsIn(string $city, string $country, string $code, float $lat, float $lng): void
    {
        Http::fake([
            'geo.test/*' => Http::response([
                'success' => true,
                'ip' => self::VISITOR_IP,
                'city' => $city,
                'region' => '',
                'country' => $country,
                'country_code' => $code,
                'latitude' => $lat,
                'longitude' => $lng,
            ]),
        ]);
    }

    /**
     * Render a product page as seen from an address the provider can locate.
     */
    private function pageAs(Food $food, string $path = '/en'): string
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::VISITOR_IP])
            ->get($path.'/products/'.$food->id)
            ->assertOk()
            ->getContent();
    }

    /**
     * The sentence the page states the origin in.
     *
     * Scoped to this element on purpose: the component also ships a copy of the
     * wording to its own script, so asserting against the whole document would
     * match phrasing the reader never sees.
     */
    private function summaryOf(string $html, Food $food): string
    {
        $id = preg_quote('origin-map-'.$food->id.'-summary', '/');

        $this->assertMatchesRegularExpression(
            '/<p id="'.$id.'"[^>]*>(.*?)<\/p>/s',
            $html,
            'The origin map states its finding in a paragraph, and it is missing.'
        );

        preg_match('/<p id="'.$id.'"[^>]*>(.*?)<\/p>/s', $html, $matches);

        return $matches[1];
    }

    // ---------- Distance ----------

    public function test_the_distance_is_a_real_measurement(): void
    {
        $registry = $this->registry();

        // A point to itself is zero.
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
        $antipode = $registry->distanceBetween(36.8065, 10.1815, -36.8065, 10.1815 - 180);
        $this->assertEqualsWithDelta(M_PI * 6371.0, $antipode, 1.0);
    }

    // ---------- Local or imported ----------

    public function test_a_product_from_the_visitors_own_country_is_not_an_import(): void
    {
        $viewer = new Location('Tunis, Tunisia', 36.8065, 10.1815, 'TN', 'ip');
        $description = $this->registry()->describe('Tunisia', $viewer);

        $this->assertTrue($description['isKnown']);
        $this->assertTrue($description['isLocal']);
        $this->assertFalse($description['isImported']);
        $this->assertNull($description['distanceKm'], 'A local product has no leg to measure.');

        // Morocco is a short hop away but a different country, so it is an
        // import and keeps its distance.
        $morocco = $this->registry()->describe('Morocco', $viewer);
        $this->assertTrue($morocco['isKnown']);
        $this->assertFalse($morocco['isLocal']);
        $this->assertTrue($morocco['isImported']);
        $this->assertGreaterThan(100, $morocco['distanceKm']);
    }

    public function test_the_distance_is_measured_from_the_visitor_not_a_fixed_home(): void
    {
        $registry = $this->registry();

        $fromTunisia = $registry->describe('France', new Location('Tunis', 36.8065, 10.1815, 'TN', 'ip'));
        $fromBrazil = $registry->describe('France', new Location('Brasilia', -15.7942, -47.8822, 'BR', 'ip'));

        // The same product is a different distance to two different readers,
        // which is the entire point of resolving the visitor rather than
        // hardcoding a home market. Brazil is much further from France.
        $this->assertNotSame($fromTunisia['distanceKm'], $fromBrazil['distanceKm']);

        // Tunis to France is about 1,500 km; Brasilia to France over 8,000.
        $this->assertGreaterThan(1400, $fromTunisia['distanceKm']);
        $this->assertLessThan(1600, $fromTunisia['distanceKm']);
        $this->assertGreaterThan(8000, $fromBrazil['distanceKm']);
    }

    public function test_a_precise_fix_without_a_country_is_judged_by_distance(): void
    {
        $registry = $this->registry();

        // A browser fix carries coordinates but no country code, so only the
        // distance can decide whether this crossed a border.
        $beside = new Location('precise', 36.85, 10.20, null, 'browser');
        $this->assertTrue($registry->describe('Tunisia', $beside)['isLocal']);

        $faraway = new Location('precise', 40.7142, -74.0060, null, 'browser');
        $this->assertFalse($registry->describe('Tunisia', $faraway)['isLocal']);
    }

    // ---------- What the pages show ----------

    public function test_the_product_page_states_the_distance_in_text(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);
        $html = $this->pageAs($food);
        $summary = $this->summaryOf($html, $food);

        // The figure is server-rendered, so it is readable without JavaScript
        // and assertable. The rounded kilometre count, not the abbreviated form.
        $km = $this->registry()->describe('France', new Location('Tunis', 36.8065, 10.1815, 'TN', 'ip'))['distanceKm'];
        $this->assertStringContainsString(number_format((float) $km).' km', $summary);

        // And it is measured from the visitor's own city, not a fixed market.
        $this->assertStringContainsString('France to Tunis, Tunisia', $summary);

        // The map is drawn with the same two endpoints.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
        $this->assertStringContainsString('L.polyline([', $html);
    }

    public function test_the_back_office_page_shows_the_same_map(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $producer = User::factory()->create(['role' => 'producer']);
        $food = Food::factory()->create(['origin' => 'Italy', 'producer_id' => $producer->id]);

        $html = $this->actingAs($producer)
            ->withServerVariables(['REMOTE_ADDR' => self::VISITOR_IP])
            ->get(route('foods.show', $food))
            ->assertOk()
            ->getContent();

        $km = $this->registry()->describe('Italy', new Location('Tunis', 36.8065, 10.1815, 'TN', 'ip'))['distanceKm'];
        $this->assertStringContainsString(number_format((float) $km).' km', $this->summaryOf($html, $food));
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
    }

    public function test_a_product_from_the_visitors_country_says_so_and_plots_no_route(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'Tunisia']);
        $html = $this->pageAs($food);
        $summary = $this->summaryOf($html, $food);

        $this->assertStringContainsString('Grown in Tunisia', $summary);
        $this->assertStringContainsString('no import leg', $summary);

        // No import leg, so no freight and no figure is claimed.
        $this->assertStringNotContainsString('freight', $summary);
        $this->assertDoesNotMatchRegularExpression('/[\d,]+ km/', $summary);

        // The map is still there. The script always ships every branch, so the
        // signal is the flag the server hands it plus the copy around it.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
    }

    public function test_an_unknown_origin_is_reported_rather_than_guessed(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'Atlantis']);
        $html = $this->pageAs($food);
        $summary = $this->summaryOf($html, $food);

        $this->assertStringContainsString('Atlantis', $summary);
        $this->assertStringContainsString('no coordinates for it', $summary);

        // The map falls back to a world view instead of pretending to plot it,
        // and offers no way to refine a line that cannot be drawn. The wording
        // it would use is not even shipped to the page.
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
        $this->assertStringNotContainsString('Use my location', $html);
        $this->assertStringNotContainsString('Share your location', $html);
    }

    public function test_a_missing_origin_draws_nothing_at_all(): void
    {
        $food = Food::factory()->create(['origin' => null]);
        $html = $this->pageAs($food);

        $this->assertStringContainsString('No origin is recorded', $html);
        // No map and no Leaflet request when there is nothing to plot.
        $this->assertStringNotContainsString('origin-map-', $html);
        $this->assertStringNotContainsString('leaflet', strtolower($html));
    }

    public function test_an_imported_product_draws_a_route_with_the_distance_on_it(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'Brazil']);
        $html = $this->pageAs($food);

        // The figure is bound onto the line itself, not only in a popup.
        $this->assertStringContainsString('bindTooltip', $html);
        $this->assertStringContainsString('map.fitBounds(route.getBounds()', $html);
    }

    public function test_the_route_line_uses_the_visible_primary_colour(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'Brazil']);
        $html = $this->pageAs($food);

        // Primary is the leaf-green brand channel: dark green against the light
        // OpenStreetMap tiles in light mode, bright leaf in dark mode. Foreground
        // turns near-white in dark mode and vanishes on those same light tiles;
        // destructive is the error channel and a shipping route is not an error.
        $this->assertStringContainsString("'--primary'", $html);
        $this->assertStringNotContainsString("'--foreground'", $html);
        $this->assertStringNotContainsString("'--destructive'", $html);

        // Solid and full-opacity: the old thin dashed 0.65 line was faint.
        $this->assertStringContainsString('weight: 5', $html);
        $this->assertStringContainsString('opacity: 1', $html);
        $this->assertStringNotContainsString('dashArray', $html);
    }

    public function test_the_map_cannot_paint_over_the_page_chrome(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);
        $html = $this->pageAs($food);

        // Leaflet stacks its own panes at z-index 100 to 1000. The front nav is
        // z-40 and every overlay in the app is z-50, so without a stacking
        // context of its own the map's zoom buttons, attribution and popups
        // paint straight over them the moment the page is scrolled.
        $this->assertMatchesRegularExpression(
            '/id="origin-map-'.$food->id.'"[^>]*class="[^"]*\bisolate\b/',
            $html,
            'The map container is not isolated, so Leaflet outranks the nav.'
        );
    }

    // ---------- Behind a load balancer ----------

    public function test_the_forwarded_address_is_used_behind_a_trusted_proxy(): void
    {
        // A deployment behind nginx or Cloudflare reaches Laravel from the
        // proxy, and the visitor is only named in X-Forwarded-For. Without the
        // proxy being trusted, every visitor is located as the load balancer.
        config(['trustedproxy.proxies' => '*']);
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);

        $html = $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
        ])->get('/en/products/'.$food->id)->assertOk()->getContent();

        // The provider was asked about the visitor, not about the proxy.
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '203.0.113.9'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '10.0.0.1'));

        $this->assertStringContainsString('France to Tunis, Tunisia', $this->summaryOf($html, $food));
    }

    public function test_an_untrusted_forwarded_address_is_ignored(): void
    {
        // Trusting nothing is the default, and a header a visitor can set for
        // themselves must not be allowed to place them wherever they like.
        config(['trustedproxy.proxies' => null]);
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
        ])->get('/en/products/'.$food->id)->assertOk();

        // The loopback address is never sent anywhere, so nothing was located.
        Http::assertNothingSent();
    }

    // ---------- When the visitor cannot be placed ----------

    public function test_a_private_address_never_leaves_the_server(): void
    {
        // The test client is on the loopback interface. No fake is registered,
        // so a lookup attempt would be a stray request and would fail the test.
        $food = Food::factory()->create(['origin' => 'France']);

        $html = $this->get('/en/products/'.$food->id)->assertOk()->getContent();
        $summary = $this->summaryOf($html, $food);

        // Nothing was invented: the page says it does not know where the
        // reader is and offers to ask the browser instead.
        $this->assertStringContainsString('Share your location', $summary);
        $this->assertStringContainsString('Use my location', $html);
        $this->assertStringNotContainsString('freight', $summary);
        $this->assertDoesNotMatchRegularExpression('/[\d,]+ km/', $summary);
    }

    public function test_a_failing_provider_does_not_break_the_page(): void
    {
        Http::fake(['geo.test/*' => Http::response(['success' => false, 'message' => 'quota'], 429)]);

        $food = Food::factory()->create(['origin' => 'France']);
        $html = $this->pageAs($food);

        $this->assertStringContainsString('Share your location', $this->summaryOf($html, $food));
        $this->assertStringContainsString('id="origin-map-'.$food->id.'"', $html);
    }

    public function test_a_disabled_lookup_never_calls_out(): void
    {
        config(['geo.ip.enabled' => false]);

        // No fake at all: with the lookup off there must be no request.
        $food = Food::factory()->create(['origin' => 'France']);
        $html = $this->pageAs($food);

        $this->assertStringContainsString('Share your location', $this->summaryOf($html, $food));
    }

    public function test_the_reader_is_told_when_the_position_is_only_approximate(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);
        $html = $this->pageAs($food);

        // An IP lookup is good to a city. Presenting it as the reader's exact
        // position would be a claim the server cannot make.
        $this->assertStringContainsString('approximate', $html);
        $this->assertStringContainsString('role="status"', $html);
    }

    // ---------- The timezone fallback ----------

    public function test_the_browser_timezone_can_place_a_reader(): void
    {
        $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => 'Africa/Tunis'])
            ->assertOk()
            ->assertJsonPath('location.countryCode', 'TN');

        $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => 'Europe/Paris'])
            ->assertOk()
            ->assertJsonPath('location.countryCode', 'FR');
    }

    public function test_a_forged_timezone_is_rejected(): void
    {
        $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => 'Mars/Olympus_Mons'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('timezone');

        // The timezone is what positions the map line, so it is never trusted
        // as a raw string.
        $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => '../../etc/passwd'])
            ->assertStatus(422);
    }

    public function test_the_timezone_fallback_needs_no_location_permission(): void
    {
        $response = $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => 'America/New_York'])
            ->assertOk();

        $this->assertSame('US', $response->json('location.countryCode'));

        // A country is not a street: it must not claim to be precise.
        $this->assertFalse($response->json('location.precise'));
    }

    public function test_a_timezone_naming_no_country_is_an_honest_empty_answer(): void
    {
        $this->postJson(route('location.store', ['locale' => 'en']), ['timezone' => 'UTC'])
            ->assertOk()
            ->assertJsonPath('location', null);
    }

    // ---------- Translated ----------

    public function test_the_copy_is_translated(): void
    {
        $food = Food::factory()->create(['origin' => 'France']);

        // Nothing on a loopback client is placeable, so this shows the copy the
        // reader gets when the server could not work them out.
        $french = $this->summaryOf($this->get('/fr/products/'.$food->id)->assertOk()->getContent(), $food);
        $this->assertStringContainsString('Partagez votre position', $french);

        // Arabic is right to left, and the same copy has to survive that.
        $arabic = $this->summaryOf($this->get('/ar/products/'.$food->id)->assertOk()->getContent(), $food);
        $this->assertStringContainsString('شارك موقعك', $arabic);

        $this->assertStringContainsString('Utiliser ma position', $this->get('/fr/products/'.$food->id)->assertOk()->getContent());
        $this->assertStringContainsString('استخدم موقعي', $this->get('/ar/products/'.$food->id)->assertOk()->getContent());
    }

    public function test_the_distance_sentence_is_translated(): void
    {
        $this->visitorIsIn('Tunis', 'Tunisia', 'TN', 36.8065, 10.1815);

        $food = Food::factory()->create(['origin' => 'France']);

        $french = $this->summaryOf($this->pageAs($food, '/fr'), $food);
        $this->assertStringContainsString('de fret', $french);
        $this->assertStringNotContainsString(' of freight', $french);

        $arabic = $this->summaryOf($this->pageAs($food, '/ar'), $food);
        $this->assertStringContainsString('شحن', $arabic);
        $this->assertStringNotContainsString(' of freight', $arabic);
    }
}

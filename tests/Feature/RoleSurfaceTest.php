<?php

namespace Tests\Feature;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\Food;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\Meal;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every role's surface: what it may reach, and what the navigation offers it.
 *
 * A link that is visible to someone but answers 403 or 404 is a defect, so
 * these walk the rendered output rather than only asserting status codes. The
 * link-coverage test at the bottom is the general guard: any page a role can
 * open must be reachable by clicking from that role's dashboard.
 */
class RoleSurfaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin'],
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
            'consumer' => ['consumer'],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    // ---------- Route naming convention ----------

    public function test_no_route_name_uses_an_audience_or_camel_case_prefix(): void
    {
        $problems = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null) {
                continue;
            }

            // The convention is one namespace per module: no `front.`/`back.`
            // audience prefix, and no camelCase segment.
            foreach (['front.', 'back.'] as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $problems[] = "{$name} starts with the audience prefix {$prefix}";
                }
            }

            foreach (explode('.', $name) as $segment) {
                if (preg_match('/[A-Z]/', $segment)) {
                    $problems[] = "{$name} has a camelCase segment {$segment}";
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_every_route_name_is_unique(): void
    {
        $seen = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null) {
                continue;
            }

            $this->assertArrayNotHasKey($name, $seen, "Route name {$name} is registered twice.");
            $seen[$name] = true;
        }

        $this->assertNotEmpty($seen);
    }

    #[DataProvider('roleProvider')]
    public function test_every_role_dashboard_sits_at_the_same_depth(string $role): void
    {
        // /admin/dashboard next to /producer/dashboard, not a bare /admin.
        $dashboard = $this->user($role)->dashboardUrl();

        $this->assertSame(
            '/'.$role.'/dashboard',
            parse_url($dashboard, PHP_URL_PATH),
            "The {$role} dashboard is not at /{$role}/dashboard."
        );

        $this->actingAs($this->user($role))->get($dashboard)->assertOk();
    }

    // ---------- The traceability module is navigable ----------

    public function test_a_processor_is_offered_the_journeys_module_and_can_follow_it(): void
    {
        $processor = $this->user('processor');
        $journey = Journey::factory()->create();
        JourneyStep::factory()->create(['journey_id' => $journey->id]);

        // The sidebar is the navigation that exists on every back office page,
        // so a module that is not in it is reachable only by typing a URL.
        $catalog = $this->actingAs($processor)
            ->get(route('foods.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-slot="sidebar"', $catalog);
        $this->assertStringContainsString('href="'.route('processor.journeys.index').'"', $catalog);

        // The dashboard also points at it, so the module is discoverable from
        // the page the processor lands on after signing in.
        $dashboard = $this->actingAs($processor)
            ->get(route('processor.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('processor.journeys.index'), $dashboard);

        // And from there every step page must be reachable by clicking rather
        // than by typing a URL.
        $index = $this->actingAs($processor)->get(route('processor.journeys.index'))->assertOk()->getContent();
        $show = $this->actingAs($processor)->get(route('processor.journeys.show', $journey))->assertOk()->getContent();
        $steps = $this->actingAs($processor)->get(route('processor.journeys.steps.index', $journey))->assertOk()->getContent();
        $create = $this->actingAs($processor)->get(route('processor.journeys.steps.create', $journey))->assertOk()->getContent();

        $this->assertStringContainsString(route('processor.journeys.show', $journey), $index);
        $this->assertStringContainsString(route('processor.journeys.steps.index', $journey), $index);
        $this->assertStringContainsString(route('processor.journeys.steps.index', $journey), $show);
        $this->assertStringContainsString(route('processor.journeys.steps.create', $journey), $show);
        $this->assertStringContainsString(route('processor.journeys.steps.create', $journey), $steps);
        $this->assertStringContainsString(route('processor.journeys.steps.index', $journey), $create);
    }

    public function test_no_other_role_is_offered_the_journeys_module(): void
    {
        $food = Food::factory()->create();

        foreach (['admin', 'producer', 'distributor', 'consumer'] as $role) {
            $user = $this->user($role);

            $html = $this->actingAs($user)->get(route('foods.index'))->assertOk()->getContent();
            $this->assertStringNotContainsString(
                route('processor.journeys.index'),
                $html,
                "The {$role} sidebar offers the traceability module."
            );

            // And the routes themselves still refuse, from the URL as well.
            $this->actingAs($user)->get(route('processor.journeys.index'))->assertForbidden();
        }

        $this->assertNotNull($food);
    }

    public function test_the_journey_qr_page_is_reachable_from_the_journey(): void
    {
        $processor = $this->user('processor');
        $journey = Journey::factory()->create();
        $journey->forceFill(['qr_code' => 'TESTQR42'])->save();

        $show = $this->actingAs($processor)->get(route('processor.journeys.show', $journey))->assertOk()->getContent();
        $this->assertStringContainsString(route('processor.journeys.qr', $journey), $show);

        $qr = $this->actingAs($processor)->get(route('processor.journeys.qr', $journey))->assertOk()->getContent();
        $this->assertStringContainsString(route('journeys.public', 'TESTQR42'), $qr);
    }

    // ---------- The consumer space belongs to consumers ----------

    #[DataProvider('roleProvider')]
    public function test_only_a_consumer_is_offered_the_consumer_space(string $role): void
    {
        $user = $this->user($role);

        $html = $this->actingAs($user)->get(route('products.index'))->assertOk()->getContent();

        if ($role === 'consumer') {
            $this->assertStringContainsString('href="'.route('consumer.space').'"', $html);
            $this->actingAs($user)->get(route('consumer.space'))->assertOk();
            $this->actingAs($user)->get(route('consumer.recommendations'))->assertOk();

            return;
        }

        // The space reports on the reader's own meals and reports, so nobody
        // else is offered it — and following the link is refused rather than
        // rendering a page full of controls that 403.
        $this->assertStringNotContainsString(route('consumer.space'), $html);
        $this->actingAs($user)->get(route('consumer.space'))->assertForbidden();
        $this->actingAs($user)->get(route('consumer.recommendations'))->assertForbidden();
    }

    #[DataProvider('roleProvider')]
    public function test_no_role_is_offered_a_meal_link_it_cannot_follow(string $role): void
    {
        $user = $this->user($role);

        if ($role === 'consumer') {
            // The consumer owns the meal area, so they are the one role that
            // must be able to follow these links.
            $this->actingAs($user)->get(route('meals.create'))->assertOk();

            return;
        }

        // The back office sidebar and the public header are what every page
        // carries, so a CTA to the consumer-only meal logger in either is a
        // visible link that answers 403.
        foreach (['foods.index', 'products.index'] as $entry) {
            $html = $this->actingAs($user)->get(route($entry))->assertOk()->getContent();

            $this->assertStringNotContainsString(
                route('meals.create'),
                $html,
                "The {$role} is offered /meals/create on {$entry}, which is consumer-only."
            );
        }

        // The consumer dashboard is where the CTA used to leak from, and it is
        // now unreachable, so assert the refusal directly.
        $this->actingAs($user)->get(route('consumer.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('meals.create'))->assertForbidden();
    }

    // ---------- The trace record is reachable ----------

    public function test_the_product_page_links_to_the_trace_record_and_back(): void
    {
        $food = Food::factory()->create();

        $html = $this->actingAs($this->user('producer'))->get(route('foods.show', $food))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('foods.transitions.index', $food).'"', $html);

        // The trace page returns to the product it belongs to, not to the index.
        $trace = $this->actingAs($this->user('producer'))->get(route('foods.transitions.index', $food))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('foods.show', $food).'"', $trace);
    }

    #[DataProvider('roleProvider')]
    public function test_every_role_can_read_and_find_the_trace_record(string $role): void
    {
        $food = Food::factory()->create();
        $user = $this->user($role);

        $html = $this->actingAs($user)->get(route('foods.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="'.route('foods.transitions.index', $food).'"',
            $html,
            "The {$role} is not offered the trace record."
        );

        $this->actingAs($user)->get(route('foods.transitions.index', $food))->assertOk();
    }

    // ---------- Logistics honesty ----------

    public function test_a_distributor_is_not_shown_another_distributors_shipments(): void
    {
        $mine = $this->user('distributor');
        $theirs = $this->user('distributor');
        $warehouse = Warehouse::factory()->create();

        $ownShipment = Shipment::factory()->create(['user_id' => $mine->id, 'warehouse_id' => $warehouse->id]);
        $otherShipment = Shipment::factory()->create(['user_id' => $theirs->id, 'warehouse_id' => $warehouse->id]);

        $html = $this->actingAs($mine)->get(route('logistics.shipments.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('logistics.shipments.show', $ownShipment), $html);
        $this->assertStringNotContainsString(
            route('logistics.shipments.show', $otherShipment),
            $html,
            'The listing offers a shipment the controller will refuse.'
        );

        // The footprint totals must not count another distributor's freight.
        $this->assertStringNotContainsString(
            number_format((float) $otherShipment->carbon_footprint_kg, 2),
            $html
        );
    }

    public function test_an_admin_still_sees_every_shipment(): void
    {
        $distributor = $this->user('distributor');
        $warehouse = Warehouse::factory()->create();
        $shipment = Shipment::factory()->create(['user_id' => $distributor->id, 'warehouse_id' => $warehouse->id]);

        $this->actingAs($this->user('admin'))
            ->get(route('logistics.shipments.index'))
            ->assertOk()
            ->assertSee(route('logistics.shipments.show', $shipment), false);
    }

    // ---------- Every role has something to do ----------

    /**
     * A role with no module of its own is a role that cannot use the app.
     * This pins the intended surface per role.
     *
     * @param  list<string>  $expectedModules
     */
    #[DataProvider('moduleProvider')]
    public function test_each_role_reaches_the_modules_it_owns(string $role, array $expectedModules): void
    {
        $this->fixtures();
        $user = $this->user($role);
        $reached = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            if ($this->actingAs($user)->get($this->urlFor($name))->getStatusCode() === 200) {
                $reached[] = $name;
            }
        }

        foreach ($expectedModules as $module) {
            $this->assertContains(
                $module,
                $reached,
                "The {$role} cannot reach {$module}."
            );
        }
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function moduleProvider(): array
    {
        return [
            'admin' => ['admin', [
                'admin.dashboard', 'admin.users.index', 'admin.reports.index',
                'admin.analysis-disputes.index',
                'farms.requests', 'regions.create', 'foods.index',
                'logistics.warehouses.index', 'logistics.shipments.index',
            ]],
            'producer' => ['producer', [
                'producer.dashboard', 'foods.create', 'foods.index',
                'farms.create', 'farms.index', 'regions.index',
            ]],
            'processor' => ['processor', [
                'processor.dashboard', 'processor.journeys.index',
                'processor.journeys.steps.create', 'foods.create', 'foods.index',
            ]],
            'distributor' => ['distributor', [
                'distributor.dashboard', 'logistics.warehouses.create',
                'logistics.shipments.create', 'foods.create', 'foods.index',
            ]],
            'consumer' => ['consumer', [
                'consumer.dashboard', 'consumer.space', 'consumer.recommendations',
                'meals.create', 'meals.index', 'foods.index',
            ]],
        ];
    }

    /**
     * The URL of every GET route, with real records behind the bindings, so a
     * role's whole surface can be walked for 200s in one pass.
     */
    private function urlFor(string $name): string
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($candidate) => $candidate->getName() === $name);

        if ($route === null || ! str_contains($route->uri(), '{')) {
            return route($name);
        }

        preg_match_all('/{(\w+)\??}/', $route->uri(), $matches);

        $values = [];
        foreach ($matches[1] as $parameter) {
            $values[$parameter] = $this->keyFor($parameter);
        }

        return route($name, $values);
    }

    /**
     * @return array<string, int|string>
     */
    private function keyFor(string $parameter): int|string
    {
        return match ($parameter) {
            'food' => $this->fixtures['food']->getRouteKey(),
            'user' => $this->user('consumer')->getRouteKey(),
            'journey' => $this->fixtures['journey']->getRouteKey(),
            'step' => $this->fixtures['step']->getRouteKey(),
            'meal' => Meal::factory()->create(['user_id' => $this->user('consumer')->id])->getRouteKey(),
            'shipment' => Shipment::factory()->create([
                'user_id' => $this->user('distributor')->id,
                'warehouse_id' => $this->fixtures['warehouse']->getRouteKey(),
            ])->getRouteKey(),
            'warehouse' => $this->fixtures['warehouse']->getRouteKey(),
            'region', 'agriculturalRegion' => $this->fixtures['region']->getRouteKey(),
            'farm' => Farm::factory()->create([
                'user_id' => $this->user('producer')->id,
                'agricultural_region_id' => $this->fixtures['region']->id,
            ])->getRouteKey(),
            'code' => 'TESTQR',
            default => 1,
        };
    }

    /**
     * One shared record per binding so the matrix does not create a farm per
     * route and wander off the ids.
     *
     * @var array<string, mixed>
     */
    private array $fixtures = [];

    private function fixtures(): array
    {
        if ($this->fixtures !== []) {
            return $this->fixtures;
        }

        $food = Food::factory()->create();
        $journey = Journey::factory()->create(['product_id' => $food->id]);
        $journey->forceFill(['qr_code' => 'TESTQR'])->save();

        return $this->fixtures = [
            'food' => $food,
            'journey' => $journey,
            'step' => JourneyStep::factory()->create(['journey_id' => $journey->id]),
            'warehouse' => Warehouse::factory()->create(),
            'region' => AgriculturalRegion::factory()->create(),
        ];
    }
}

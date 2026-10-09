<?php

namespace Tests\Feature;

use App\Enums\AnalysisDisputeStatus;
use App\Enums\EnvironmentalScore;
use App\Enums\FarmStatus;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Models\AgriculturalRegion;
use App\Models\AnalysisDispute;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Farm;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Meal;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDatabase(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_seeds_a_user_for_every_role(): void
    {
        $this->seedDatabase();

        foreach (['admin', 'producer', 'processor', 'distributor', 'consumer'] as $role) {
            $this->assertDatabaseHas('users', ['role' => $role]);
        }
    }

    /**
     * Every role must be reachable at `<role>@example.com`.
     *
     * The consumer used to be seeded as `test@example.com`, so signing in as the
     * consumer looked impossible even though the account existed — the demo
     * credentials looked incomplete.
     */
    public function test_every_role_has_a_predictable_demo_login(): void
    {
        $this->seedDatabase();

        foreach (['admin', 'producer', 'processor', 'distributor', 'consumer'] as $role) {
            $this->assertDatabaseHas('users', [
                'email' => $role.'@example.com',
                'role' => $role,
            ]);
        }
    }

    /**
     * The seeded accounts must actually authenticate, not merely exist.
     */
    public function test_every_seeded_demo_account_can_sign_in(): void
    {
        $this->seedDatabase();

        foreach ([
            'admin', 'producer', 'processor', 'distributor', 'consumer', 'consumer2',
        ] as $account) {
            $user = User::where('email', $account.'@example.com')->firstOrFail();

            $response = $this->post(route('login'), [
                'email' => $account.'@example.com',
                'password' => 'password',
            ]);

            $response->assertRedirect();
            $this->assertAuthenticatedAs($user);

            // Whichever landing the controller picks for this role, it must be
            // a page that role can actually read. Following redirects because
            // /dashboard is itself a redirect to the role's own dashboard.
            $this->followingRedirects()
                ->get($response->headers->get('Location'))
                ->assertOk();

            $this->post(route('logout'))->assertRedirect(route('home'));
            $this->assertGuest();
        }
    }

    public function test_it_seeds_categories_products_and_meals(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, Food::count());
        $this->assertGreaterThan(0, Meal::count());
    }

    public function test_every_seeded_product_is_filed_under_a_category(): void
    {
        $this->seed();

        // The catalog is demo content, so the count is not fixed; what must
        // hold is that nothing is orphaned or uncategorised.
        $this->assertSame(
            0,
            Food::whereNull('category_id')->count(),
            'Some seeded products have no category.'
        );

        $this->assertSame(
            0,
            Food::whereDoesntHave('category')->count(),
            'Some seeded products point at a category that does not exist.'
        );
    }

    public function test_every_seeded_product_has_an_owner(): void
    {
        $this->seedDatabase();

        $this->assertSame(0, Food::whereNull('producer_id')->count());
    }

    public function test_every_seeded_product_has_a_valid_environmental_grade(): void
    {
        $this->seedDatabase();

        foreach (Food::pluck('environmental_score') as $score) {
            $this->assertInstanceOf(EnvironmentalScore::class, $score);
        }
    }

    public function test_every_seeded_product_has_a_supply_chain_in_order(): void
    {
        $this->seedDatabase();

        $expected = Stage::order();

        foreach (Food::whereHas('transitions')->get() as $food) {
            $actual = $food->transitions->map(fn ($transition) => $transition->to_stage->value)->all();

            $this->assertSame(
                array_slice($expected, 0, count($actual)),
                $actual,
                "Supply chain for [{$food->name}] is out of order."
            );
        }
    }

    public function test_supply_chain_timestamps_increase_along_the_chain(): void
    {
        $this->seedDatabase();

        foreach (Food::whereHas('transitions')->get() as $food) {
            $dates = $food->transitions
                ->map(fn ($transition): int => $transition->occurred_at->getTimestamp())
                ->all();

            $sorted = $dates;
            sort($sorted);

            $this->assertSame($sorted, $dates, "Timestamps for [{$food->name}] are out of order.");
        }
    }

    public function test_certifications_are_linked_from_the_certifications_table(): void
    {
        $this->seedDatabase();

        $this->assertGreaterThan(0, Certification::count());

        foreach (Food::with('certifications')->get() as $food) {
            foreach ($food->certifications as $certification) {
                $this->assertNotNull($certification->issuer);
            }
        }
    }

    public function test_every_seeded_meal_belongs_to_a_user(): void
    {
        $this->seed();

        $this->assertSame(0, Meal::whereNull('user_id')->count());
        $this->assertSame(0, Meal::whereDoesntHave('foods')->count());
    }

    public function test_it_seeds_reviews_from_consumers(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Review::count());
        $this->assertSame(0, Review::whereNull('user_id')->count());
    }

    public function test_it_seeds_some_upheld_reports_so_the_trust_signal_is_visible(): void
    {
        $this->seed();

        $upheld = GreenwashingReport::where('status', ReportStatus::Upheld->value)->get();

        $this->assertGreaterThan(0, $upheld->count());

        foreach ($upheld as $report) {
            $this->assertNotNull($report->reviewed_by);
            $this->assertNotNull($report->reviewed_at);
        }
    }

    public function test_it_seeds_analysis_disputes_in_both_states(): void
    {
        $this->seed();

        // The admin queue is worthless empty, and the sidebar badge is only
        // visible when something is waiting, so both states must be seeded.
        $this->assertGreaterThan(0, AnalysisDispute::where('status', AnalysisDisputeStatus::Pending->value)->count());
        $this->assertGreaterThan(0, AnalysisDispute::where('status', AnalysisDisputeStatus::Dismissed->value)->count());

        foreach (AnalysisDispute::all() as $dispute) {
            $this->assertNotNull($dispute->food_id, 'A seeded dispute is not attached to a product.');
            $this->assertNotNull($dispute->user_id, 'A seeded dispute has no reporter.');
            $this->assertNotEmpty($dispute->comment, 'A seeded dispute has no explanation.');

            // Only a decided dispute may carry a decision.
            if ($dispute->isPending()) {
                $this->assertNull($dispute->reviewed_at);
            } else {
                $this->assertNotNull($dispute->reviewed_at);
                $this->assertNotNull($dispute->reviewed_by);
            }
        }
    }

    public function test_a_seeded_dismissal_always_explains_itself(): void
    {
        $this->seed();

        // The request makes a note mandatory on dismissal, so the demo data may
        // not contradict the rule the form enforces.
        foreach (AnalysisDispute::where('status', AnalysisDisputeStatus::Dismissed->value)->get() as $dispute) {
            $this->assertNotEmpty($dispute->resolution_note);
        }
    }

    public function test_seeded_products_have_transparency_scores_in_range(): void
    {
        $this->seed();

        foreach (Food::with('transitions', 'certifications')->get() as $food) {
            $score = $food->transparencyScore();

            $this->assertGreaterThanOrEqual(0, $score, "[{$food->name}] scored below zero.");
            $this->assertLessThanOrEqual(100, $score, "[{$food->name}] scored above 100.");
        }
    }

    // ---------- Farms and regions ----------

    public function test_every_seeded_farm_carries_a_status_the_enum_understands(): void
    {
        $this->seed();

        $farms = Farm::all();

        $this->assertNotEmpty($farms, 'The seeder produced no farms.');

        foreach ($farms as $farm) {
            $this->assertInstanceOf(
                FarmStatus::class,
                $farm->status,
                "[{$farm->name}] has a status the FarmStatus enum cannot represent."
            );
        }
    }

    public function test_only_approved_farms_are_publishable(): void
    {
        $this->seed();

        // The scope the public pages use must never return a farm that is
        // still awaiting review or has been turned down.
        $published = Farm::query()->approved()->get();

        foreach ($published as $farm) {
            $this->assertSame(FarmStatus::Approved, $farm->status, "[{$farm->name}] was published unapproved.");
        }

        $this->assertSame(
            Farm::where('status', FarmStatus::Approved->value)->count(),
            $published->count()
        );
    }

    public function test_a_region_exposes_only_its_approved_farms(): void
    {
        $this->seed();

        $region = AgriculturalRegion::firstOrFail();

        $this->assertGreaterThanOrEqual($region->farms()->count(), $region->approvedFarms()->count());

        foreach ($region->approvedFarms()->get() as $farm) {
            $this->assertSame(FarmStatus::Approved, $farm->status);
        }
    }

    public function test_every_seeded_farm_belongs_to_a_region(): void
    {
        $this->seed();

        foreach (Farm::all() as $farm) {
            $this->assertNotNull($farm->region, "[{$farm->name}] is not attached to a region.");
        }
    }

    public function test_a_rejected_farm_records_why_it_was_rejected(): void
    {
        $farm = Farm::factory()->create([
            'status' => FarmStatus::Rejected,
            'rejection_reason' => 'Adresse incomplète',
        ]);

        $this->assertTrue($farm->isRejected());
        $this->assertNotEmpty($farm->rejection_reason);
        $this->assertFalse($farm->canBeEdited(), 'A rejected farm must not be editable.');

        // And it stays out of the public listing.
        $this->get(route('agricultural-regions.show', $farm->region))->assertDontSee($farm->address);
    }
}

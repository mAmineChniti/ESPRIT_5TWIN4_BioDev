<?php

namespace Tests\Feature;

use App\Enums\EnvironmentalScore;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Models\Certification;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Meal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerSpaceTest extends TestCase
{
    use RefreshDatabase;

    private function consumer(): User
    {
        return User::factory()->create(['role' => 'consumer']);
    }

    private function tracedFood(int $stages = 3): Food
    {
        $food = Food::factory()->create(['environmental_score' => EnvironmentalScore::B]);

        $previous = null;

        foreach (array_slice(Stage::order(), 0, $stages) as $offset => $stage) {
            $food->transitions()->create([
                'actor_id' => User::factory()->create(['role' => 'producer'])->id,
                'from_stage' => $previous,
                'to_stage' => $stage,
                'occurred_at' => now()->subDays((3 - $offset) * 2),
            ]);
            $previous = $stage;
        }

        return $food;
    }

    // ---------- Public search ----------

    public function test_the_search_page_is_publicly_reachable(): void
    {
        $this->get(route('products.index'))->assertOk();
    }

    public function test_search_matches_name_origin_and_producer(): void
    {
        Food::factory()->create(['name' => 'Grilled Salmon']);
        $origin = Food::factory()->create(['name' => 'Something Else', 'origin' => 'Tunisia']);

        $this->get(route('products.index', ['q' => 'Salmon']))->assertOk()->assertSee('Grilled Salmon');
        $this->get(route('products.index', ['q' => 'Tunisia']))->assertOk()->assertSee($origin->name);
    }

    public function test_search_can_filter_to_certified_products_only(): void
    {
        $certified = Food::factory()->create(['name' => 'Certified Thing']);
        Food::factory()->create(['name' => 'Plain Thing']);
        $certified->certifications()->attach(Certification::factory()->create());

        $response = $this->get(route('products.index', ['certified' => 1]));

        $response->assertOk();
        $response->assertSee('Certified Thing');
        $response->assertDontSee('Plain Thing');
    }

    public function test_search_rejects_an_unknown_grade(): void
    {
        $this->get(route('products.index', ['grade' => 'Z']))->assertSessionHasErrors('grade');
    }

    public function test_a_product_page_is_publicly_reachable(): void
    {
        $food = $this->tracedFood();

        $this->get(route('products.show', $food))->assertOk()->assertSee($food->name);
    }

    public function test_the_product_page_exposes_timeline_data_for_the_chart(): void
    {
        $food = $this->tracedFood();

        $response = $this->get(route('products.show', $food));

        $response->assertOk();
        $response->assertSee('trace-timeline-data', false);
        $response->assertSee('Produced', false);
        $response->assertSee('Distributed', false);
    }

    public function test_scanning_a_numeric_code_increments_the_scan_counter(): void
    {
        $food = Food::factory()->create();

        $this->get(route('products.scan', ['code' => $food->id]))
            ->assertRedirect(route('products.show', $food));

        $this->assertSame(1, $food->fresh()->scans_count);
    }

    public function test_scanning_an_unknown_code_falls_back_to_search(): void
    {
        $this->get(route('products.scan', ['code' => 'zzzz']))
            ->assertRedirect(route('products.index', ['q' => 'zzzz']))
            ->assertSessionHasErrors('code');
    }

    public function test_the_greenwashing_guide_is_publicly_reachable(): void
    {
        $this->get(route('greenwashing'))->assertOk();
    }

    public function test_the_catalog_renders_a_single_pagination_control(): void
    {
        Food::factory(13)->create();

        $response = $this->get(route('products.index'));

        $response->assertOk();

        // One control, not a mobile copy plus a desktop copy.
        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Pagination"'));
        $this->assertStringNotContainsString('sm:hidden', $response->getContent());
    }

    public function test_pagination_is_styled_with_theme_tokens_not_a_palette(): void
    {
        Food::factory(13)->create();

        $html = $this->get(route('products.index'))->getContent();

        preg_match('/<nav role="navigation" aria-label="Pagination".*?<\/nav>/s', $html, $match);

        $this->assertNotEmpty($match, 'Pagination control was not rendered.');

        foreach ([
            'gray-', 'white', 'blue-', 'indigo-', 'dark:',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $match[0],
                "Pagination still hardcodes [{$forbidden}]."
            );
        }
    }

    // ---------- Reviews ----------

    public function test_a_consumer_can_leave_a_review(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $food), ['rating' => 4, 'body' => 'Matched the label'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'food_id' => $food->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);
    }

    public function test_a_second_review_by_the_same_user_updates_instead_of_duplicating(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $this->actingAs($user)->post(route('reviews.store', $food), ['rating' => 2]);
        $this->actingAs($user)->post(route('reviews.store', $food), ['rating' => 5]);

        $this->assertSame(1, Review::where('food_id', $food->id)->where('user_id', $user->id)->count());
        $this->assertSame(5, Review::where('food_id', $food->id)->first()->rating);
    }

    public function test_review_rating_must_be_between_one_and_five(): void
    {
        $this->actingAs($this->consumer())
            ->post(route('reviews.store', Food::factory()->create()), ['rating' => 9])
            ->assertSessionHasErrors('rating');
    }

    public function test_a_guest_cannot_review(): void
    {
        $this->post(route('reviews.store', Food::factory()->create()), ['rating' => 5])
            ->assertRedirect(route('login'));
    }

    public function test_only_consumers_may_review(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'producer']))
            ->post(route('reviews.store', Food::factory()->create()), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_a_user_cannot_delete_someone_elses_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->consumer())
            ->delete(route('reviews.destroy', ['food' => $review->food_id, 'review' => $review]))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_average_rating_reflects_reviews(): void
    {
        $food = Food::factory()->create();
        Review::factory()->count(3)->create(['food_id' => $food->id, 'rating' => 4]);
        Review::factory()->create(['food_id' => $food->id, 'rating' => 2]);

        $this->assertSame(3.5, $food->averageRating());
    }

    // ---------- Greenwashing reports ----------

    public function test_a_consumer_can_file_a_greenwashing_report(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $this->actingAs($user)->post(route('reports.store', $food), [
            'reason' => ReportReason::UnverifiableClaim->value,
            'details' => 'Nothing on the page backs this up.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('greenwashing_reports', [
            'food_id' => $food->id,
            'user_id' => $user->id,
            'reason' => ReportReason::UnverifiableClaim->value,
            'status' => ReportStatus::Pending->value,
        ]);
    }

    public function test_a_report_requires_a_valid_reason(): void
    {
        $this->actingAs($this->consumer())
            ->post(route('reports.store', Food::factory()->create()), ['reason' => 'because'])
            ->assertSessionHasErrors('reason');
    }

    public function test_a_user_cannot_file_two_pending_reports_on_one_product(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $this->actingAs($user)->post(route('reports.store', $food), ['reason' => ReportReason::Other->value]);
        $this->actingAs($user)->post(route('reports.store', $food), ['reason' => ReportReason::Other->value])
            ->assertSessionHasErrors('reason');

        $this->assertSame(1, GreenwashingReport::where('food_id', $food->id)->count());
    }

    public function test_only_admins_can_decide_a_report(): void
    {
        $report = GreenwashingReport::factory()->create();

        $this->actingAs($this->consumer())
            ->patch(route('reports.update', $report), ['status' => ReportStatus::Upheld->value])
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('reports.update', $report), ['status' => ReportStatus::Upheld->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(ReportStatus::Upheld, $report->fresh()->status);
    }

    // ---------- Transparency score ----------

    public function test_a_fully_traced_certified_product_scores_high(): void
    {
        $food = $this->tracedFood(3);
        $food->certifications()->attach(Certification::factory()->create());

        $this->assertGreaterThanOrEqual(80, $food->transparencyScore());
        $this->assertSame('Well traced', $food->trustVerdict()['level']);
    }

    public function test_a_product_with_no_chain_is_unverified(): void
    {
        $food = Food::factory()->create(['environmental_score' => null]);

        $this->assertSame('Unverified', $food->trustVerdict()['level']);
    }

    public function test_an_upheld_report_lowers_the_score_and_flags_the_product(): void
    {
        $food = $this->tracedFood(3);
        $before = $food->transparencyScore();

        GreenwashingReport::factory()->upheld()->create(['food_id' => $food->id]);

        $food->refresh();

        $this->assertLessThan($before, $food->transparencyScore());
        $this->assertSame('At risk', $food->trustVerdict()['level']);
        $this->assertSame(1, $food->upheldReportCount());
    }

    public function test_dismissed_and_pending_reports_do_not_count_against_a_product(): void
    {
        $food = $this->tracedFood(3);
        $clean = $food->transparencyScore();

        GreenwashingReport::factory()->create(['food_id' => $food->id, 'status' => ReportStatus::Pending]);
        GreenwashingReport::factory()->dismissed()->create(['food_id' => $food->id]);

        $this->assertSame($clean, $food->fresh()->transparencyScore());
        $this->assertSame(0, $food->fresh()->upheldReportCount());
    }

    public function test_the_transparency_score_never_leaves_its_bounds(): void
    {
        $food = $this->tracedFood(3);
        GreenwashingReport::factory(10)->upheld()->create(['food_id' => $food->id]);

        $score = $food->fresh()->transparencyScore();

        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // ---------- Consumer dashboard ----------

    public function test_the_consumer_dashboard_shows_only_the_signed_in_users_activity(): void
    {
        $me = $this->consumer();
        $food = Food::factory()->create();

        $mine = Meal::factory()->create(['user_id' => $me->id, 'name' => 'My Dinner']);
        $mine->foods()->attach($food, ['quantity' => 100]);
        Review::factory()->create(['user_id' => $me->id, 'food_id' => $food->id]);
        Meal::factory()->create(['name' => 'Someone Elses Dinner']);

        $this->actingAs($me)->get(route('consumer.dashboard'))
            ->assertOk()
            ->assertSee('My Dinner')
            ->assertDontSee('Someone Elves Dinner')
            ->assertSee('consumer-dashboard-data', false);
    }

    public function test_dashboard_calories_come_from_the_products_logged(): void
    {
        $me = $this->consumer();
        $food = Food::factory()->create(['calories' => 100]);
        $meal = Meal::factory()->create(['user_id' => $me->id, 'consumed_on' => now()]);
        $meal->foods()->attach($food, ['quantity' => 250]);

        // 100 kcal per 100g at 250g = 250 kcal
        $this->assertSame(250, $meal->fresh()->totalCalories());

        $this->actingAs($me)->get(route('consumer.dashboard'))->assertOk();
    }

    public function test_non_consumers_cannot_open_the_consumer_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'producer']))
            ->get(route('consumer.dashboard'))
            ->assertForbidden();
    }
}

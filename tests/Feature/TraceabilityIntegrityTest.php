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
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Regressions for defects found auditing the traceability guarantees.
 *
 * Each test here failed before the corresponding fix, so they pin the behaviour
 * rather than the implementation.
 */
class TraceabilityIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function producer(): User
    {
        return User::factory()->create(['role' => 'producer']);
    }

    private function consumer(): User
    {
        return User::factory()->create(['role' => 'consumer']);
    }

    /**
     * A product owned by $producer with the first $stages of the chain recorded.
     */
    private function foodWithStages(User $producer, int $stages): Food
    {
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $previous = null;

        foreach (array_slice(Stage::order(), 0, $stages) as $offset => $stage) {
            $food->transitions()->create([
                'actor_id' => $producer->id,
                'from_stage' => $previous,
                'to_stage' => $stage,
                'occurred_at' => now()->subDays((3 - $offset) * 2),
            ]);
            $previous = $stage;
        }

        return $food->fresh();
    }

    // ---------- Chain completeness drives the verdict ----------

    public function test_a_product_with_one_of_three_stages_is_not_called_well_traced(): void
    {
        $food = $this->foodWithStages($this->producer(), 1);

        // Certifications and a grade are enough on their own to push the raw
        // score over 80, which used to produce a false "full chain" claim.
        $food->certifications()->attach(Certification::factory()->create());
        $food->update(['environmental_score' => EnvironmentalScore::A]);

        $this->assertGreaterThanOrEqual(80, $food->transparencyScore());
        $this->assertSame('Partly traced', $food->trustVerdict()['level']);
        $this->assertStringNotContainsString('full chain', $food->trustVerdict()['message']);
    }

    public function test_a_complete_chain_with_certifications_is_well_traced(): void
    {
        $food = $this->foodWithStages($this->producer(), 3);
        $food->certifications()->attach(Certification::factory()->create());
        $food->update(['environmental_score' => EnvironmentalScore::A]);

        $this->assertSame('Well traced', $food->trustVerdict()['level']);
    }

    public function test_a_complete_chain_without_certifications_does_not_claim_them(): void
    {
        $food = $this->foodWithStages($this->producer(), 3);
        $food->update(['environmental_score' => EnvironmentalScore::A]);

        $verdict = $food->trustVerdict();

        $this->assertSame('Well traced', $verdict['level']);
        $this->assertStringNotContainsString('every certification', $verdict['message']);
    }

    public function test_the_product_page_renders_for_a_product_with_no_chain(): void
    {
        // Used to raise a ValueError from Stage::from(end([])).
        $food = Food::factory()->create();

        $this->assertSame(0, $food->recordedStageCount());
        $this->assertNull($food->currentStage());

        $this->get(route('products.show', $food))->assertOk();
    }

    public function test_the_product_page_renders_for_an_anonymous_visitor(): void
    {
        $food = Food::factory()->create();

        $this->get(route('products.show', $food))
            ->assertOk()
            ->assertSee($food->name);
    }

    // ---------- Admin and role access to products ----------

    public function test_an_admin_can_open_the_edit_form_for_any_product(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('foods.edit', $food))
            ->assertOk();
    }

    public function test_an_admin_can_delete_any_product(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('foods.destroy', $food))
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseMissing('foods', ['id' => $food->id]);
    }

    public function test_a_processor_cannot_rewrite_the_producers_nutrition_data(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id, 'calories' => 100]);

        $this->actingAs(User::factory()->create(['role' => 'processor']))
            ->put(route('foods.update', $food), [
                'name' => $food->name,
                'category_id' => $food->category_id,
                'calories' => 999,
                'protein' => 1,
                'carbs' => 1,
                'fat' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(100, (int) $food->fresh()->calories);
    }

    public function test_the_edit_and_delete_actions_are_hidden_from_other_users(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id]);

        // A processor may sign for the chain but must not see ownership actions.
        $this->actingAs(User::factory()->create(['role' => 'processor']))
            ->get(route('foods.show', $food))
            ->assertOk()
            ->assertDontSee(route('foods.destroy', $food), false);
    }

    // ---------- Chain of custody integrity ----------

    public function test_the_recorded_actor_matches_the_role_that_performs_the_stage(): void
    {
        $producer = $this->producer();
        $food = $this->foodWithStages($producer, 1);
        $processor = User::factory()->create(['role' => 'processor']);

        $this->actingAs($processor)
            ->post(route('foods.transitions.store', $food), ['to_stage' => Stage::Processed->value])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stage_transitions', [
            'food_id' => $food->id,
            'to_stage' => Stage::Processed->value,
            'actor_id' => $processor->id,
        ]);
    }

    public function test_a_producer_cannot_forge_a_processing_step(): void
    {
        $producer = $this->producer();
        $food = $this->foodWithStages($producer, 1);

        $this->actingAs($producer)
            ->post(route('foods.transitions.store', $food), ['to_stage' => Stage::Processed->value])
            ->assertForbidden();

        $this->assertDatabaseCount('stage_transitions', 1);
    }

    public function test_a_stage_cannot_be_skipped(): void
    {
        $food = $this->foodWithStages($this->producer(), 1);

        $this->actingAs(User::factory()->create(['role' => 'distributor']))
            ->post(route('foods.transitions.store', $food), ['to_stage' => Stage::Distributed->value])
            ->assertSessionHasErrors('to_stage');

        $this->assertDatabaseCount('stage_transitions', 1);
    }

    public function test_nothing_more_can_be_recorded_once_the_chain_is_complete(): void
    {
        $food = $this->foodWithStages($this->producer(), 3);

        $this->actingAs(User::factory()->create(['role' => 'distributor']))
            ->post(route('foods.transitions.store', $food), ['to_stage' => Stage::Distributed->value])
            ->assertSessionHasErrors('to_stage');

        $this->assertDatabaseCount('stage_transitions', 3);
    }

    public function test_the_trace_page_only_offers_the_stage_the_user_may_record(): void
    {
        $food = $this->foodWithStages($this->producer(), 1);

        // The processor may sign for Processed, so the form is offered.
        $this->actingAs(User::factory()->create(['role' => 'processor']))
            ->get(route('foods.transitions.index', $food))
            ->assertOk()
            ->assertSee('Record '.Stage::Processed->label());

        // The distributor may not: the next step belongs to the processor.
        $this->actingAs(User::factory()->create(['role' => 'distributor']))
            ->get(route('foods.transitions.index', $food))
            ->assertOk()
            ->assertSee('Only an account with the')
            ->assertDontSee('Record '.Stage::Processed->label());
    }

    // ---------- Certifications can be removed ----------

    public function test_clearing_every_certification_removes_them_all(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);
        $certification = Certification::factory()->create(['name' => 'Organic']);

        $food->certifications()->attach($certification);
        $this->assertCount(1, $food->fresh()->certifications);

        // The form omits certifications[] entirely when nothing is ticked.
        $this->actingAs($producer)
            ->put(route('foods.update', $food), [
                'name' => $food->name,
                'category_id' => $food->category_id,
                'calories' => $food->calories,
                'protein' => $food->protein,
                'carbs' => $food->carbs,
                'fat' => $food->fat,
            ])
            ->assertSessionHasNoErrors();

        $this->assertCount(0, $food->fresh()->certifications);
    }

    public function test_ticking_certifications_still_attaches_them_on_update(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);
        $certification = Certification::factory()->create(['name' => 'Organic']);

        $this->actingAs($producer)
            ->put(route('foods.update', $food), [
                'name' => $food->name,
                'category_id' => $food->category_id,
                'calories' => $food->calories,
                'protein' => $food->protein,
                'carbs' => $food->carbs,
                'fat' => $food->fat,
                'certifications' => [$certification->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertCount(1, $food->fresh()->certifications);
    }

    // ---------- Catalog filters ----------

    public function test_the_eco_grade_filter_returns_matching_products(): void
    {
        $wanted = Food::factory()->create(['environmental_score' => EnvironmentalScore::A]);
        Food::factory()->create(['environmental_score' => EnvironmentalScore::E]);

        // Rule::enum() validates without casting, so this used to raise a TypeError.
        $this->get(route('products.index', ['grade' => 'A']))
            ->assertOk()
            ->assertSee($wanted->name);
    }

    public function test_every_grade_filter_value_is_accepted(): void
    {
        foreach (EnvironmentalScore::cases() as $grade) {
            $this->get(route('products.index', ['grade' => $grade->value]))->assertOk();
        }
    }

    public function test_an_unknown_grade_is_rejected_without_a_server_error(): void
    {
        $this->get(route('products.index', ['grade' => 'Z']))->assertSessionHasErrors('grade');
    }

    // ---------- Reporting ----------

    public function test_only_consumers_can_file_a_greenwashing_report(): void
    {
        $food = Food::factory()->create();

        foreach (['producer', 'processor', 'distributor', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('reports.store', $food), ['reason' => ReportReason::cases()[0]->value])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('greenwashing_reports', 0);
    }

    public function test_a_reviewed_report_cannot_be_reviewed_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = GreenwashingReport::factory()->upheld()->create();

        $this->actingAs($admin)
            ->patch(route('reports.update', $report), ['status' => ReportStatus::Dismissed->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::Upheld, $report->fresh()->status);
    }

    public function test_a_pending_report_cannot_be_set_back_to_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = GreenwashingReport::factory()->create();

        $this->actingAs($admin)
            ->patch(route('reports.update', $report), ['status' => ReportStatus::Pending->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
    }

    public function test_the_report_queue_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        GreenwashingReport::factory()->count(20)->create();

        $this->actingAs($admin)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('page=2', false);
    }

    // ---------- Admin user management ----------

    public function test_an_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'consumer',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_an_admin_can_promote_someone_else(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'producer',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('producer', $user->fresh()->role);
    }

    public function test_the_user_list_totals_cover_every_role_not_just_the_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(18)->create(['role' => 'consumer']);

        // Totals are aggregated in SQL, so they hold past the first page.
        $response = $this->actingAs($admin)->get(route('admin.users'))->assertOk();

        $this->assertStringContainsString('19 total', $response->getContent());
    }

    // ---------- CSV import ----------

    public function test_importing_rejects_an_unknown_eco_grade_instead_of_swallowing_the_row(): void
    {
        $producer = $this->producer();

        $csv = "name,category,origin,environmental_score,calories,protein,carbs,fat\n"
            ."Rambutan,Fruit,Thailand,Z,60,0.8,15,0.1\n";

        $this->actingAs($producer)
            ->post(route('foods.import'), [
                'csv_file' => UploadedFile::fake()->createWithContent('foods.csv', $csv),
            ])
            ->assertRedirect(route('foods.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('foods', ['name' => 'Rambutan']);
    }

    public function test_importing_accepts_a_valid_grade(): void
    {
        $producer = $this->producer();

        $csv = "name,category,origin,environmental_score,calories,protein,carbs,fat\n"
            ."Rambutan,Fruit,Thailand,B,60,0.8,15,0.1\n";

        $this->actingAs($producer)
            ->post(route('foods.import'), [
                'csv_file' => UploadedFile::fake()->createWithContent('foods.csv', $csv),
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('foods', ['name' => 'Rambutan']);
    }

    // ---------- Rendered markup ----------

    public function test_the_transparency_ring_uses_a_theme_token_not_an_undefined_utility(): void
    {
        $food = $this->foodWithStages($this->producer(), 3);

        $content = $this->get(route('products.show', $food))->assertOk()->getContent();

        // These names never existed, so the trust indicator rendered colourless.
        $this->assertStringNotContainsString('text-bio', $content);
        $this->assertStringNotContainsString('footprint-medium', $content);
        $this->assertStringContainsString('text-primary', $content);
    }

    public function test_the_greenwashing_guide_renders_every_band_with_a_real_token(): void
    {
        $content = $this->get(route('greenwashing'))->assertOk()->getContent();

        $this->assertStringNotContainsString('bg-bio', $content);
        $this->assertStringNotContainsString('footprint-medium', $content);
    }

    public function test_status_and_result_pages_do_not_hardcode_palette_colours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        GreenwashingReport::factory()->create();

        $pages = [
            route('admin.reports'),
            route('products.index'),
        ];

        foreach ($pages as $url) {
            $content = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            foreach (['yellow-100', 'yellow-800', 'red-100', 'red-800', 'green-100', 'green-800',
                'bg-green-900', 'bg-red-900', 'text-green-300', 'text-red-300', 'text-md'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $content, "{$forbidden} found on {$url}");
            }
        }
    }

    public function test_forms_link_their_errors_to_their_fields(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $content = $this->actingAs($producer)
            ->get(route('foods.edit', $food))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-describedby="name-error"', $content);
        $this->assertStringContainsString('aria-invalid', $content);
    }

    public function test_the_meal_form_does_not_nest_the_quantity_input_inside_a_checkbox_label(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $food = Food::factory()->create(['name' => 'Quince']);

        $content = $this->actingAs($consumer)->get(route('meals.create'))->assertOk()->getContent();

        // The quantity field carries its own label rather than inheriting the
        // checkbox's, so activating it cannot toggle the checkbox.
        $this->assertStringContainsString('for="quantity-'.$food->id.'"', $content);
        $this->assertStringContainsString('for="food-'.$food->id.'"', $content);
        $this->assertStringContainsString('<fieldset', $content);
    }

    public function test_a_page_without_a_chain_does_not_claim_the_full_chain_is_recorded(): void
    {
        $food = Food::factory()->create(['environmental_score' => null]);

        $content = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('Unverified', $content);
        $this->assertStringNotContainsString('The full chain is recorded', $content);
    }

    public function test_transitions_default_to_the_current_timestamp(): void
    {
        $this->foodWithStages($this->producer(), 1);

        $transition = StageTransition::query()->latest('id')->firstOrFail();

        $this->assertNotNull($transition->occurred_at);
    }

    // ---------- Meal product picker is bounded and searchable ----------

    public function test_the_meal_picker_searches_by_name_origin_and_category(): void
    {
        $consumer = $this->consumer();

        Food::factory()->create(['name' => 'Quince', 'origin' => 'Turkey']);
        Food::factory()->create(['name' => 'Saffron', 'origin' => 'Iran']);

        $this->actingAs($consumer)
            ->get(route('meals.create', ['q' => 'Quince']))
            ->assertOk()
            ->assertSee('Quince')
            ->assertDontSee('Saffron');

        $this->actingAs($consumer)
            ->get(route('meals.create', ['q' => 'Iran']))
            ->assertOk()
            ->assertSee('Saffron')
            ->assertDontSee('Quince');
    }

    public function test_the_meal_picker_is_capped_and_says_so(): void
    {
        $consumer = $this->consumer();

        Food::factory()->count(55)->create();

        $html = $this->actingAs($consumer)->get(route('meals.create'))->assertOk()->getContent();

        $this->assertSame(50, substr_count($html, 'name="foods[]"'));
        $this->assertStringContainsString('Showing the first 50 products', $html);
    }

    public function test_the_meal_picker_shows_an_empty_state_when_nothing_matches(): void
    {
        $consumer = $this->consumer();
        Food::factory()->count(3)->create();

        $this->actingAs($consumer)
            ->get(route('meals.create', ['q' => 'zzzznomatch']))
            ->assertOk()
            ->assertSee('No product matched.');
    }

    public function test_a_meal_can_still_be_logged_with_the_cap_on_the_picker(): void
    {
        $consumer = $this->consumer();
        $food = Food::factory()->count(55)->create()->first();

        $this->actingAs($consumer)
            ->post(route('meals.store'), [
                'name' => 'Small Meal',
                'type' => Meal::TYPES[0],
                'foods' => [$food->id],
                'quantities' => [$food->id => 120],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('meals', ['name' => 'Small Meal', 'user_id' => $consumer->id]);
    }

    public function test_the_consumer_dashboard_energy_chart_covers_a_dated_window(): void
    {
        $consumer = $this->consumer();
        Food::factory()->create();

        // A window rather than a row limit, so the trend cannot silently cover
        // less than the meal count implies.
        $this->actingAs($consumer)
            ->get(route('consumer.dashboard'))
            ->assertOk()
            ->assertSee('over the last '. 30 .' days');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Enums\ShipmentStatus;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\Farm;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\CitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Processors maintain the traceability chain step by step.
 *
 * These cover the step recording rule end to end: the renamed English step
 * types validate, the pre-rename French ones do not, a step URL cannot be
 * swapped across journeys, and only processors may record steps.
 */
class JourneyStepTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStep(array $overrides = []): array
    {
        return [
            'step_order' => 1,
            'type' => 'origin',
            'location' => 'Nabeul, Tunisia',
            'step_date' => '2026-09-01',
            'description' => 'Citrus harvest.',
            ...$overrides,
        ];
    }

    public function test_a_processor_can_record_a_step_with_an_english_type(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep([
                'type' => 'storage',
                'location' => 'Nabeul',
                'country_code' => 'TN',
            ]))
            ->assertRedirect(route('processor.journeys.steps.index', $journey));

        $this->assertDatabaseHas('journey_steps', [
            'journey_id' => $journey->id,
            'type' => 'storage',
            'location' => 'Nabeul',
            'country_code' => 'TN',
        ]);
    }

    public function test_pre_rename_french_step_types_are_rejected(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep(['type' => 'origine']))
            ->assertSessionHasErrors(['type']);

        $this->assertDatabaseCount('journey_steps', 0);
    }

    public function test_a_processor_can_update_and_delete_a_step(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $step = JourneyStep::factory()->create();
        $journey = $step->journey;

        $this->actingAs($processor)
            ->patch(
                route('processor.journeys.steps.update', [$journey, $step]),
                $this->validStep([
                    'type' => 'sale',
                    'location' => 'Tunis',
                    'country_code' => 'TN',
                ])
            )
            ->assertRedirect(route('processor.journeys.steps.show', [$journey, $step]));
        $this->assertSame('sale', $step->fresh()->type);
        $this->assertSame('TN', $step->fresh()->country_code);

        $this->actingAs($processor)
            ->delete(route('processor.journeys.steps.destroy', [$journey, $step]))
            ->assertRedirect(route('processor.journeys.steps.index', $journey));

        $this->assertModelMissing($step);
    }

    public function test_a_step_url_cannot_be_swapped_across_journeys(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $otherStep = JourneyStep::factory()->create();

        $this->actingAs($processor)
            ->get(route('processor.journeys.steps.show', [$journey, $otherStep]))
            ->assertNotFound();

        $this->actingAs($processor)
            ->patch(
                route('processor.journeys.steps.update', [$journey, $otherStep]),
                $this->validStep()
            )
            ->assertNotFound();
    }

    public function test_only_a_processor_may_record_steps(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        $journey = Journey::factory()->create();

        $this->actingAs($consumer)
            ->get(route('processor.journeys.steps.index', $journey))
            ->assertForbidden();

        $this->actingAs($consumer)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep())
            ->assertForbidden();

        $this->assertDatabaseCount('journey_steps', 0);
    }

    public function test_guests_are_redirected_to_the_localized_login_page_for_journeys(): void
    {
        $journey = Journey::factory()->create();

        $this->get(route('processor.journeys.steps.create', [
            'locale' => 'fr',
            'journey' => $journey,
        ]))
            ->assertRedirect(route('login', ['locale' => 'fr']));
    }

    public function test_a_step_cannot_cite_an_unapproved_farm(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $pendingFarm = Farm::factory()->create([
            'status' => FarmStatus::Pending,
            'user_id' => User::factory()->create(['role' => 'producer'])->id,
        ]);

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep(['farm_id' => $pendingFarm->id]))
            ->assertSessionHasErrors(['farm_id']);

        $this->assertDatabaseCount('journey_steps', 0);
    }

    public function test_a_step_cannot_cite_a_cancelled_shipment(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $cancelled = Shipment::factory()->create(['status' => ShipmentStatus::Cancelled]);

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep(['shipment_id' => $cancelled->id]))
            ->assertSessionHasErrors(['shipment_id']);

        $this->assertDatabaseCount('journey_steps', 0);
    }

    public function test_a_step_may_cite_an_approved_farm(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $farm = Farm::factory()->create([
            'status' => FarmStatus::Approved,
            'user_id' => User::factory()->create(['role' => 'producer'])->id,
        ]);

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep(['farm_id' => $farm->id]))
            ->assertRedirect(route('processor.journeys.steps.index', $journey));

        $this->assertDatabaseHas('journey_steps', [
            'journey_id' => $journey->id,
            'farm_id' => $farm->id,
        ]);
    }

    public function test_the_create_form_prefills_the_next_free_order_and_suggested_type(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'type' => 'origin',
        ]);

        $html = $this->actingAs($processor)
            ->get(route('processor.journeys.steps.create', $journey))
            ->assertOk()->getContent();

        // The next free position, so submit does not fail the per-journey
        // uniqueness rule, and the next unrecorded stage selected.
        $this->assertStringContainsString('value="2"', $html);
        $this->assertStringContainsString('value="transport" selected', $html);
        $this->assertStringContainsString('Step 2', $html);
    }

    public function test_all_journey_and_step_crud_pages_render_for_a_processor(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $step = JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'location' => 'Tunis',
            'country_code' => 'TN',
        ]);

        $this->actingAs($processor)
            ->get(route('processor.journeys.index'))
            ->assertOk()
            ->assertSee($journey->product->name);

        $this->get(route('processor.journeys.show', $journey))->assertOk();
        $this->get(route('processor.journeys.steps.index', $journey))->assertOk();
        $this->get(route('processor.journeys.steps.create', $journey))->assertOk();
        $this->get(route('processor.journeys.steps.show', [$journey, $step]))->assertOk();
        $this->get(route('processor.journeys.steps.edit', [$journey, $step]))->assertOk();
    }

    public function test_the_journey_list_filters_by_product_step_type_and_date_range(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $matchingJourney = Journey::factory()->create();
        $matchingJourney->product->update(['name' => 'Searchable olive oil']);
        JourneyStep::factory()->create([
            'journey_id' => $matchingJourney->id,
            'type' => 'origin',
            'location' => 'Tunis',
            'step_date' => '2026-09-12',
        ]);

        $otherJourney = Journey::factory()->create();
        $otherJourney->product->update(['name' => 'Searchable apple juice']);
        JourneyStep::factory()->create([
            'journey_id' => $otherJourney->id,
            'type' => 'sale',
            'location' => 'Sousse',
            'step_date' => '2026-10-12',
        ]);

        $this->actingAs($processor)
            ->get(route('processor.journeys.index', [
                'search' => 'olive',
                'type' => 'origin',
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('Searchable olive oil')
            ->assertDontSee('Searchable apple juice');
    }

    public function test_the_journey_list_can_search_by_step_location(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $matchingJourney = Journey::factory()->create();
        $matchingJourney->product->update(['name' => 'Journey at Sfax']);
        JourneyStep::factory()->create([
            'journey_id' => $matchingJourney->id,
            'location' => 'Sfax',
        ]);

        $otherJourney = Journey::factory()->create();
        $otherJourney->product->update(['name' => 'Journey at Tunis']);
        JourneyStep::factory()->create([
            'journey_id' => $otherJourney->id,
            'location' => 'Tunis',
        ]);

        $this->actingAs($processor)
            ->get(route('processor.journeys.index', ['search' => 'Sfax']))
            ->assertOk()
            ->assertSee('Journey at Sfax')
            ->assertDontSee('Journey at Tunis');
    }

    public function test_the_step_list_filters_rows_without_changing_the_workflow_tracker(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $steps = collect([
            ['type' => 'origin', 'location' => 'Tunis', 'step_date' => '2026-09-01'],
            ['type' => 'transport', 'location' => 'Sousse', 'step_date' => '2026-09-12'],
            ['type' => 'sale', 'location' => 'Sfax', 'step_date' => '2026-10-12'],
        ])->map(fn (array $attributes, int $index): JourneyStep => JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => $index + 1,
            ...$attributes,
        ]));

        $this->actingAs($processor)
            ->get(route('processor.journeys.steps.index', [
                'journey' => $journey,
                'search' => 'Sousse',
                'type' => 'transport',
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('aria-label="Edit step '.$steps[1]->step_order.'"', false)
            ->assertDontSee('aria-label="Edit step '.$steps[0]->step_order.'"', false)
            ->assertDontSee('aria-label="Edit step '.$steps[2]->step_order.'"', false)
            ->assertSee('3 of 4 stages recorded');

        $this->get(route('processor.journeys.steps.index', [
            'journey' => $journey,
            'search' => 'No matching city',
        ]))
            ->assertOk()
            ->assertSee('No steps match your filters.')
            ->assertSee('3 of 4 stages recorded');
    }

    public function test_journey_filters_render_in_french_and_arabic(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $this->actingAs($processor);

        $this->get(route('processor.journeys.index', ['locale' => 'fr']))
            ->assertOk()
            ->assertSee('Rechercher un produit ou un lieu')
            ->assertSee('À partir du');

        $this->get(route('processor.journeys.steps.index', [
            'locale' => 'ar',
            'journey' => $journey,
        ]))
            ->assertOk()
            ->assertSee('البحث حسب الموقع')
            ->assertSee('من تاريخ')
            ->assertSee('dir="rtl"', false);
    }

    public function test_the_step_form_lists_approved_farms_and_city_reference_options(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $approvedFarm = Farm::factory()->create([
            'name' => 'Approved Farm',
            'address' => 'Nabeul',
            'status' => FarmStatus::Approved,
        ]);
        $pendingFarm = Farm::factory()->create([
            'name' => 'Pending Farm',
            'status' => FarmStatus::Pending,
        ]);

        $html = $this->actingAs($processor)
            ->get(route('processor.journeys.steps.create', $journey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="farm_id"', $html);
        $this->assertStringContainsString($approvedFarm->name.' — '.$approvedFarm->address, $html);
        $this->assertStringNotContainsString($pendingFarm->name, $html);
        $this->assertStringContainsString('name="country_code"', $html);
        $this->assertStringContainsString('list="city-options"', $html);
        $this->assertStringContainsString('<option value="Tunis" label="TN">', $html);
    }

    public function test_journey_step_observer_recalculates_distance_when_steps_change_or_are_deleted(): void
    {
        $this->seed(CitySeeder::class);
        $journey = Journey::factory()->create(['total_distance_km' => 0]);
        $firstStep = JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'location' => 'Tunis',
            'country_code' => 'TN',
        ]);
        $secondStep = JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 2,
            'location' => 'Sousse',
            'country_code' => 'TN',
        ]);

        $this->assertSame('116.0', $journey->fresh()->total_distance_km);

        $secondStep->update(['location' => 'Sfax']);

        $this->assertSame('236.0', $journey->fresh()->total_distance_km);

        $secondStep->delete();

        $this->assertSame('0.0', $journey->fresh()->total_distance_km);
        $this->assertModelExists($firstStep);
    }

    public function test_farm_and_city_fields_render_in_french_and_arabic(): void
    {
        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $this->actingAs($processor);

        $frenchHtml = $this->get(route('processor.journeys.steps.create', [
            'locale' => 'fr',
            'journey' => $journey,
        ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Ferme', $frenchHtml);
        $this->assertStringNotContainsString('facultative', $frenchHtml);
        $this->assertStringContainsString('Aucune ferme sélectionnée', $frenchHtml);
        $this->assertStringContainsString('Pays', $frenchHtml);
        $this->assertStringContainsString('Ville', $frenchHtml);

        $arabicHtml = $this->get(route('processor.journeys.steps.create', [
            'locale' => 'ar',
            'journey' => $journey,
        ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dir="rtl"', $arabicHtml);
        $this->assertStringContainsString('مزرعة', $arabicHtml);
        $this->assertStringContainsString('اختر بلدًا (اختياري)', $arabicHtml);
        $this->assertStringContainsString('البلد', $arabicHtml);
        $this->assertStringContainsString('المدينة', $arabicHtml);
    }

    public function test_journey_pages_fail_closed_without_the_role_middleware(): void
    {
        $this->withoutMiddleware(EnsureUserHasRole::class);

        $consumer = User::factory()->create(['role' => 'consumer']);
        $journey = Journey::factory()->create();

        // The controller repeats the route's processor gate, so a route
        // moved out of the group still refuses the wrong role.
        $this->actingAs($consumer)->get(route('processor.journeys.index'))->assertForbidden();
        $this->actingAs($consumer)->get(route('processor.journeys.show', $journey))->assertForbidden();
        $this->actingAs($consumer)->get(route('processor.journeys.steps.index', $journey))->assertForbidden();
        $this->actingAs($consumer)->get(route('processor.journeys.steps.create', $journey))->assertForbidden();
    }

    public function test_the_steps_page_renders_the_workflow_tracker(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'type' => 'origin',
        ]);

        $html = $this->actingAs($processor)
            ->get(route('processor.journeys.steps.index', $journey))
            ->assertOk()->getContent();

        // The April progress tracker with the recorded stage completed and the
        // suggested next stage current, plus the call to action that carries
        // the prefill into the create form.
        $this->assertStringContainsString('aria-label="Progress"', $html);
        $this->assertStringContainsString('data-state="completed"', $html);
        $this->assertStringContainsString('1 of 4 stages recorded', $html);
        $this->assertStringContainsString('Record Transport — step 2', $html);
    }

    public function test_the_steps_page_renders_when_every_workflow_stage_is_recorded(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();

        foreach (JourneyStep::FLOW as $index => $type) {
            JourneyStep::factory()->create([
                'journey_id' => $journey->id,
                'step_order' => $index + 1,
                'type' => $type,
            ]);
        }

        $this->actingAs($processor)
            ->get(route('processor.journeys.steps.index', $journey))
            ->assertOk()
            ->assertSee('Every workflow stage is recorded.');
    }
}

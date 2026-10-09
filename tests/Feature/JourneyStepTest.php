<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\User;
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
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();

        $this->actingAs($processor)
            ->post(route('processor.journeys.steps.store', $journey), $this->validStep(['type' => 'storage']))
            ->assertRedirect(route('processor.journeys.steps.index', $journey));

        $this->assertDatabaseHas('journey_steps', [
            'journey_id' => $journey->id,
            'type' => 'storage',
            'location' => 'Nabeul, Tunisia',
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
        $processor = User::factory()->create(['role' => 'processor']);
        $step = JourneyStep::factory()->create();
        $journey = $step->journey;

        $this->actingAs($processor)
            ->patch(
                route('processor.journeys.steps.update', [$journey, $step]),
                $this->validStep(['type' => 'sale', 'location' => 'Tunis, Tunisia'])
            )
            ->assertRedirect(route('processor.journeys.steps.show', [$journey, $step]));

        $this->assertSame('sale', $step->fresh()->type);

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

    public function test_the_create_form_prefills_the_next_free_order_and_suggested_type(): void
    {
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
}

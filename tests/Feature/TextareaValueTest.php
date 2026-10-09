<?php

namespace Tests\Feature;

use App\Enums\AnalysisDisputeReason;
use App\Enums\FarmStatus;
use App\Enums\Stage;
use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\Food;
use App\Models\Review;
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A textarea keeps its value.
 *
 * April's <april:textarea> renders <textarea ...></textarea> with no
 * {{ $slot }}, so a value written between its tags is discarded silently. The
 * symptom is data loss rather than an error: editing a farm blanked its
 * description, editing a review blanked the body, and a rejected form came back
 * empty.
 *
 * These assert the rendered inner text, not the Blade source.
 */
class TextareaValueTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDetector(): void
    {
        Http::preventStrayRequests();
        $payload = json_encode([
            'findings' => [],
            'summary' => 'Nothing found on record.',
            'risk_score' => 12,
            'verdict' => 'No misleading claims detected',
        ]);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $payload]]]])]);
    }

    /**
     * The text between <textarea name="$name"> and its closing tag.
     */
    private function textareaValue(string $html, string $name): ?string
    {
        if (! preg_match('/<textarea\b[^>]*name="'.preg_quote($name, '/').'"[^>]*>(.*?)<\/textarea>/s', $html, $m)) {
            return null;
        }

        return trim(html_entity_decode($m[1], ENT_QUOTES));
    }

    public function test_the_farm_edit_form_keeps_the_stored_description(): void
    {
        $owner = User::factory()->create(['role' => 'producer']);
        $farm = Farm::factory()->create([
            'user_id' => $owner->id,
            'agricultural_region_id' => AgriculturalRegion::factory()->create()->id,
            'description' => 'Fertile clay loam, irrigated from a seasonal borehole.',
        ]);

        $html = $this->actingAs($owner)->get(route('farms.edit', $farm))->assertOk()->getContent();

        $this->assertSame(
            'Fertile clay loam, irrigated from a seasonal borehole.',
            $this->textareaValue($html, 'description'),
            'The farm description is not rendered, so saving the form would erase it.'
        );
    }

    public function test_the_farm_form_keeps_the_description_through_a_validation_failure(): void
    {
        $owner = User::factory()->create(['role' => 'producer']);
        $farm = Farm::factory()->create([
            'user_id' => $owner->id,
            'agricultural_region_id' => AgriculturalRegion::factory()->create()->id,
        ]);

        $editUrl = route('farms.edit', $farm);

        $this->actingAs($owner)
            ->from($editUrl)
            // Missing name: the description the admin typed must survive.
            ->patch(route('farms.update', $farm), [
                'description' => 'Left here while the name is corrected.',
            ])
            ->assertSessionHasErrors('name')
            ->assertRedirect($editUrl);

        // The redirect carries the typed description back to the form.
        $html = $this->actingAs($owner)->get($editUrl)->assertOk()->getContent();

        $this->assertSame('Left here while the name is corrected.', $this->textareaValue($html, 'description'));
    }

    public function test_an_existing_review_body_is_shown_in_the_review_form(): void
    {
        $this->fakeDetector();

        $consumer = User::factory()->create(['role' => 'consumer']);
        $food = Food::factory()->create();
        Review::factory()->create([
            'food_id' => $food->id,
            'user_id' => $consumer->id,
            'body' => 'The weight matched the label.',
        ]);

        $html = $this->actingAs($consumer)->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertSame(
            'The weight matched the label.',
            $this->textareaValue($html, 'body'),
            'The review body is not rendered, so updating the review would erase it.'
        );
    }

    public function test_the_rejection_reason_survives_a_rejected_submission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $farm = Farm::factory()->create([
            'user_id' => User::factory()->create(['role' => 'producer'])->id,
            'agricultural_region_id' => AgriculturalRegion::factory()->create()->id,
            'status' => FarmStatus::Pending,
        ]);

        $requestsUrl = route('farms.requests');

        $this->actingAs($admin)
            ->from($requestsUrl)
            ->patch(route('farms.reject', $farm), [
                'rejection_reason' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('rejection_reason')
            ->assertRedirect($requestsUrl);

        $html = $this->actingAs($admin)->get($requestsUrl)->assertOk()->getContent();

        $this->assertSame(
            str_repeat('a', 1001),
            $this->textareaValue($html, 'rejection_reason'),
            'A rejected submission must not throw away the reason the admin typed.'
        );
    }

    public function test_the_dispute_comment_survives_a_rejected_submission(): void
    {
        $this->fakeDetector();

        $consumer = User::factory()->create(['role' => 'consumer']);
        $food = Food::factory()->create();

        $productUrl = route('products.show', $food);

        $this->actingAs($consumer)
            ->from($productUrl)
            ->post(route('products.analysis-disputes.store', $food), [
                'reason' => AnalysisDisputeReason::WrongVerdict->value,
                'comment' => 'too short',
            ])
            // The short comment fails validation and bounces back to the
            // product page. Note: no assertSessionHasErrors here — reading the
            // error bag ages the flashed session, and the assertions below
            // need the errors (and the typed values) rendered on the page.
            // The message text itself is the proof validation failed.
            ->assertRedirect($productUrl);

        $html = $this->actingAs($consumer)->get($productUrl)->assertOk()->getContent();

        $this->assertSame('too short', $this->textareaValue($html, 'comment'));
        // And the reason they picked is still selected.
        $this->assertStringContainsString('value="wrong_verdict" selected', $html);
        // With the message that explains why.
        $this->assertStringContainsString('10 characters', $html);
    }

    public function test_the_meal_notes_survive_a_rejected_submission(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);

        $html = $this->actingAs($consumer)
            ->from(route('meals.create'))
            ->followingRedirects()
            ->post(route('meals.store'), [
                'type' => 'breakfast',
                'consumed_on' => now()->format('Y-m-d'),
                'notes' => 'Eaten with the neighbours.',
            ])
            ->getContent();

        $this->assertSame('Eaten with the neighbours.', $this->textareaValue($html, 'notes'));
    }

    public function test_the_transition_notes_survive_a_rejected_submission(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);
        $food = Food::factory()->create(['producer_id' => $producer->id]);
        StageTransition::factory()->create([
            'food_id' => $food->id,
            'actor_id' => $producer->id,
            'to_stage' => Stage::Produced,
        ]);

        // An admin may sign any stage, but the chain expects Processed next,
        // so the order rule refuses and the note must come back with the form
        // rather than vanish. (A distributor would 403 on the policy first,
        // and the form below only renders for whoever may record the next
        // stage, so neither of them could show the note coming back.)
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)
            ->from(route('foods.transitions.index', $food))
            // Skipping a stage is refused, so the note must come back with it.
            ->followingRedirects()
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Distributed->value,
                'notes' => 'Handed straight to the importer.',
            ])
            ->getContent();

        $this->assertSame('Handed straight to the importer.', $this->textareaValue($html, 'notes'));
    }
}

/**
 * No textarea in the view layer may be written as <april:textarea>…value…
 * again: that markup is silently discarded. This is the static guard for the
 * whole codebase, so a new one cannot be added by accident.
 */
class TextareaSlotUsageTest extends TestCase
{
    public function test_no_april_textarea_carries_a_value_as_slot_content(): void
    {
        $offenders = [];

        foreach (file_get_contents(resource_path('views'), false, null, FILE_IGNORE_NEW_LINES) ?: [] as $ignored) {
            // Unreachable; the directory scan below does the work.
        }

        $directory = new \RecursiveDirectoryIterator(resource_path('views'));
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = $file->getContents();

            if (preg_match_all('/<april:textarea\b[^>]*>(.*?)<\/april:textarea>/s', $source, $matches)) {
                foreach ($matches[1] as $inner) {
                    if (trim($inner) === '') {
                        continue;
                    }

                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname())
                        .' → '.trim(preg_replace('/\s+/', ' ', $inner));
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "April's <april:textarea> has no {!! \$slot !!}, so this markup renders an empty box. Use <x-textarea-field :value=\"…\" />:\n"
            .implode("\n", $offenders)
        );
    }
}

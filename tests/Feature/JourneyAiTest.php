<?php

namespace Tests\Feature;

use App\Jobs\GenerateJourneySummary;
use App\Models\Farm;
use App\Models\Food;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\User;
use App\Services\Ai\AiClient;
use Database\Seeders\CitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JourneyAiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configure Groq without exposing a real key.
     */
    private function configureGroq(): void
    {
        Config::set('services.ai.key', 'test-groq-key');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');
    }

    /**
     * Persist a generated public summary from a mocked Groq response.
     */
    public function test_summary_job_generates_and_saves_a_french_narrative(): void
    {
        $this->configureGroq();
        $this->seed(CitySeeder::class);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Les olives sont récoltées à Tunis. Elles sont transportées vers Sousse. Le parcours comprend une étape de stockage et une vente locale.',
                    ],
                ]],
            ]),
        ]);

        $farm = Farm::factory()->create(['name' => 'Ferme de Tunis']);
        $product = Food::factory()->create([
            'name' => 'Huile d’olive bio',
            'origin' => 'Tunisie',
        ]);
        $journey = Journey::factory()->create([
            'product_id' => $product->id,
            'environmental_score' => 82,
            'total_distance_km' => 116,
            'ai_summary' => null,
            'generated_at' => null,
        ]);
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'type' => 'origin',
            'location' => 'Tunis',
            'country_code' => 'TN',
            'step_date' => '2026-10-01',
            'description' => 'Récolte des olives.',
            'farm_id' => $farm->id,
        ]);
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 2,
            'type' => 'transport',
            'location' => 'Sousse',
            'country_code' => 'TN',
            'step_date' => '2026-10-02',
            'description' => 'Transport réfrigéré.',
        ]);

        (new GenerateJourneySummary($journey))->handle(app(AiClient::class));

        $this->assertSame(
            'Les olives sont récoltées à Tunis. Elles sont transportées vers Sousse. Le parcours comprend une étape de stockage et une vente locale.',
            $journey->fresh()->ai_summary,
        );
        $this->assertNotNull($journey->fresh()->generated_at);
        $this->get(route('journeys.public', $journey->qr_code))
            ->assertOk()
            ->assertSee('Les olives sont récoltées à Tunis.');

        $payloads = Http::recorded()
            ->map(fn (array $record): string => json_encode($record[0]->data(), JSON_UNESCAPED_UNICODE) ?: '')
            ->implode("\n");

        $this->assertStringContainsString('Huile d’olive bio', $payloads);
        $this->assertStringContainsString('Ferme de Tunis', $payloads);
        $this->assertStringContainsString('Récolte des olives.', $payloads);
        $this->assertStringContainsString('82', $payloads);
        $this->assertStringContainsString('116', $payloads);
    }

    /**
     * Keep the prior public summary if Groq fails.
     */
    public function test_summary_job_logs_provider_errors_without_erasing_the_existing_summary(): void
    {
        $this->configureGroq();
        Log::spy();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([], 503),
        ]);

        $journey = Journey::factory()->create([
            'ai_summary' => 'Résumé déjà disponible.',
        ]);

        (new GenerateJourneySummary($journey))->handle(app(AiClient::class));

        $this->assertSame('Résumé déjà disponible.', $journey->fresh()->ai_summary);
        Log::shouldHaveReceived('error')->once();
    }

    /**
     * Queue a delayed summary refresh when a step is saved and AI is configured.
     */
    public function test_step_observer_queues_summary_generation_after_saving(): void
    {
        $this->configureGroq();
        $this->seed(CitySeeder::class);
        Queue::fake();

        $journey = Journey::factory()->create();

        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'location' => 'Tunis',
            'country_code' => 'TN',
        ]);

        Queue::assertPushed(GenerateJourneySummary::class);
    }

    /**
     * Keep the step description field available in French and Arabic.
     */
    public function test_step_description_field_renders_in_supported_locales_without_ai_suggestion(): void
    {
        $this->assertFalse(app('router')->has('processor.journeys.steps.suggest'));

        $this->seed(CitySeeder::class);
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        $this->actingAs($processor);

        $this->get(route('processor.journeys.steps.create', [
            'locale' => 'fr',
            'journey' => $journey,
        ]))
            ->assertOk()
            ->assertSee('Description')
            ->assertDontSee('Suggest with AI');

        $this->get(route('processor.journeys.steps.create', [
            'locale' => 'ar',
            'journey' => $journey,
        ]))
            ->assertOk()
            ->assertSee('الوصف')
            ->assertDontSee('Suggest with AI')
            ->assertSee('dir="rtl"', false);
    }
}

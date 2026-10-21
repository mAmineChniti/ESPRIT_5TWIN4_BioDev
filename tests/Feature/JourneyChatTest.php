<?php

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\Farm;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Services\JourneyChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JourneyChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configure Groq with a test-only key.
     */
    private function configureGroq(): void
    {
        Config::set('services.ai.key', 'test-groq-key');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');
    }

    /**
     * Return a successful Groq answer body.
     *
     * @return array<string, mixed>
     */
    private function groqAnswer(string $answer = 'The product came from Tunis.'): array
    {
        return [
            'choices' => [[
                'message' => [
                    'content' => $answer,
                ],
            ]],
        ];
    }

    public function test_a_visitor_can_ask_a_question_about_a_public_journey(): void
    {
        $this->configureGroq();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(
                $this->groqAnswer('The product came from Tunis.'),
            ),
        ]);

        $journey = Journey::factory()->create();
        $journey->product->update([
            'name' => 'Tunisian olive oil',
            'origin' => 'Tunisia',
        ]);
        $journey->forceFill([
            'environmental_score' => 88,
            'total_distance_km' => 124,
            'ai_summary' => 'Olives are harvested in Tunis.',
        ])->save();
        Queue::fake();
        $farm = Farm::factory()->create([
            'name' => 'Approved Tunis farm',
            'status' => FarmStatus::Approved,
        ]);
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'type' => 'origin',
            'location' => 'Tunis',
            'country_code' => 'TN',
            'step_date' => '2026-10-01',
            'description' => 'Olive harvest.',
            'farm_id' => $farm->id,
        ]);

        $this->postJson(route('public.journeys.chat', [
            'locale' => 'en',
            'journey' => $journey,
        ]), [
            'question' => 'Where does this product come from?',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertExactJson(['answer' => 'The product came from Tunis.']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization')
            && str_contains(
                json_encode($request->data(), JSON_UNESCAPED_UNICODE) ?: '',
                'Where does this product come from?'
            )
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE) ?: '', 'Tunisian olive oil')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE) ?: '', 'Approved Tunis farm')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE) ?: '', 'Olive harvest.'));
    }

    public function test_a_question_is_required_and_cannot_exceed_200_characters(): void
    {
        Http::preventStrayRequests();
        $journey = Journey::factory()->create();
        $url = route('public.journeys.chat', ['locale' => 'en', 'journey' => $journey]);

        $this->postJson($url, ['question' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question']);

        $this->postJson($url, ['question' => str_repeat('a', 201)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question']);
    }

    public function test_an_invalid_chat_locale_is_rejected(): void
    {
        Http::preventStrayRequests();
        $journey = Journey::factory()->create();

        $this->postJson(route('public.journeys.chat', ['locale' => 'en', 'journey' => $journey]), [
            'question' => 'Where did this product come from?',
            'locale' => 'de',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_a_groq_failure_returns_a_clear_service_unavailable_response(): void
    {
        $this->configureGroq();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([], 503),
        ]);

        $journey = Journey::factory()->create();

        $this->postJson(route('public.journeys.chat', ['locale' => 'fr', 'journey' => $journey]), [
            'question' => 'D’où vient ce produit ?',
            'locale' => 'fr',
        ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'L’assistant du parcours est temporairement indisponible. Veuillez réessayer.');
    }

    public function test_identical_questions_are_served_from_the_24_hour_cache(): void
    {
        $this->configureGroq();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(
                $this->groqAnswer('Tunis'),
            ),
        ]);
        Cache::flush();
        $journey = Journey::factory()->create();
        $chat = app(JourneyChatService::class);

        $this->assertSame('Tunis', $chat->ask($journey, 'Where is it from?', 'en'));
        $this->assertSame('Tunis', $chat->ask($journey, 'Where is it from?', 'en'));

        Http::assertSentCount(1);
    }

    public function test_the_public_journey_page_renders_the_chat_widget(): void
    {
        $journey = Journey::factory()->create();

        $this->get(route('journeys.public', $journey->qr_code))
            ->assertOk()
            ->assertSee('Journey assistant')
            ->assertSee('Where does this product come from?')
            ->assertSee('How many kilometers?')
            ->assertSee('Is it organic?')
            ->assertSee('journey-chat-messages', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_the_public_chat_is_limited_to_20_requests_per_minute(): void
    {
        $this->configureGroq();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(
                $this->groqAnswer(),
            ),
        ]);
        $journey = Journey::factory()->create();
        $url = route('public.journeys.chat', ['locale' => 'en', 'journey' => $journey]);

        for ($requestNumber = 1; $requestNumber <= 20; $requestNumber++) {
            $this->postJson($url, [
                'question' => 'Question '.$requestNumber,
                'locale' => 'en',
            ])->assertOk();
        }

        $this->postJson($url, [
            'question' => 'Question 21',
            'locale' => 'en',
        ])->assertTooManyRequests();
    }

    public function test_the_chat_widget_is_translated_for_french_and_arabic(): void
    {
        $journey = Journey::factory()->create();

        $this->get(route('journeys.public', ['locale' => 'fr', 'code' => $journey->qr_code]))
            ->assertOk()
            ->assertSee('Assistant du parcours')
            ->assertSee('D’où vient ce produit ?');

        $this->get(route('journeys.public', ['locale' => 'ar', 'code' => $journey->qr_code]))
            ->assertOk()
            ->assertSee('مساعد الرحلة')
            ->assertSee('من أين يأتي هذا المنتج؟')
            ->assertSee('dir="rtl"', false);
    }
}

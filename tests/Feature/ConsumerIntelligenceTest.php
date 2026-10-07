<?php

namespace Tests\Feature;

use App\Enums\EnvironmentalScore;
use App\Enums\FindingCategory;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Models\Category;
use App\Models\Food;
use App\Models\Meal;
use App\Models\User;
use App\Services\Assistant\ProductAssistant;
use App\Services\Greenwashing\GreenwashingDetector;
use App\Services\Recommendations\ProductRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The consumer AI features: greenwashing detection, the assistant,
 * recommendations, and escalating a detected finding.
 *
 * The model is stubbed at the HTTP boundary rather than faked at the service
 * boundary, so the real prompt, the real JSON Schema, the real response parsing
 * and the real error handling are all exercised. Nothing here needs an API key.
 */
class ConsumerIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private const SCHEMA = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['summary', 'verdict', 'findings'],
        'properties' => [
            'summary' => ['type' => 'string'],
            'verdict' => ['type' => 'string'],
            'findings' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['category', 'severity', 'title', 'detail', 'evidence'],
                    'properties' => [
                        'category' => ['type' => 'string'],
                        'severity' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'detail' => ['type' => 'string'],
                        'evidence' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // A stray request would otherwise reach the real provider using the real
        // key from .env, so a missing fake fails loudly instead of quietly
        // spending quota.
        Http::preventStrayRequests();

        config([
            'services.ai.key' => 'test-key',
            'services.ai.provider' => 'groq',
            'services.ai.model' => 'test-model',
            'services.ai.base_url' => 'https://ai.test/v1',
        ]);
    }

    /**
     * Reply to AI calls with a successful OpenAI-shaped payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function fakeAi(array $payload): void
    {
        $body = Http::response([
            'choices' => [
                ['message' => ['content' => json_encode($payload)]],
            ],
        ]);

        // Both hosts are faked because some tests switch the provider mid-test,
        // and a stray request must fail rather than reach the live API.
        Http::fake([
            'ai.test/*' => $body,
            'gemini.test/*' => $body,
        ]);
    }

    /**
     * The requests Laravel sent to the AI provider.
     *
     * Http::recorded() yields [$request, $response] pairs.
     *
     * @return Collection<int, array{0: Request, 1: mixed}>
     */
    private function aiRequests()
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'ai.test')
            || str_contains($request->url(), 'gemini.test'));
    }

    private function lastAiRequest(): Request
    {
        $requests = $this->aiRequests();

        $this->assertGreaterThan(0, $requests->count(), 'no AI request was made');

        return $requests->last()[0];
    }

    private function fakeAiRaw(string $body, int $status = 200): void
    {
        Http::fake([
            'ai.test/*' => Http::response($body, $status),
            'gemini.test/*' => Http::response($body, $status),
        ]);
    }

    /**
     * An OpenAI-shaped body carrying the given JSON payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function chatBody(array $payload): array
    {
        return ['choices' => [['message' => ['content' => json_encode($payload)]]]];
    }

    private function findingPayload(): array
    {
        return [
            'summary' => 'The name promises an eco claim nothing on file supports.',
            'verdict' => 'Likely misleading',
            'findings' => [
                [
                    'category' => 'unverifiable_claim',
                    'severity' => 'high',
                    'title' => 'Eco claim with no certification',
                    'detail' => 'No certification backs the claim in the product name.',
                    'evidence' => 'claims.certifications = []',
                ],
            ],
        ];
    }

    private function consumer(): User
    {
        return User::factory()->create(['role' => 'consumer']);
    }

    /**
     * Record every stage, so the transparency score depends only on what the
     * test varies afterwards.
     */
    private function withFullChain(Food $food): Food
    {
        foreach (Stage::cases() as $index => $stage) {
            $food->transitions()->create([
                'actor_id' => $food->producer_id,
                'from_stage' => $index === 0 ? null : Stage::cases()[$index - 1],
                'to_stage' => $stage,
                'occurred_at' => now(),
            ]);
        }

        return $food->fresh();
    }

    // ---------- Detection ----------

    public function test_the_detector_turns_a_model_response_into_typed_findings(): void
    {
        $this->fakeAi($this->findingPayload());

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertFalse($report->hasFailed());
        $this->assertSame('Likely misleading', $report->verdict);
        $this->assertCount(1, $report->findings);

        $finding = $report->findings[0];
        $this->assertSame(FindingCategory::UnverifiableClaim, $finding->category);
        $this->assertSame('Eco claim with no certification', $finding->title);
        $this->assertSame('claims.certifications = []', $finding->evidence);
    }

    public function test_findings_are_ordered_worst_first(): void
    {
        $this->fakeAi([
            'summary' => 'Several problems.',
            'verdict' => 'Claims not fully supported',
            'findings' => [
                ['category' => 'other', 'severity' => 'low', 'title' => 'Minor', 'detail' => 'x', 'evidence' => 'y'],
                ['category' => 'unverifiable_claim', 'severity' => 'high', 'title' => 'Bad', 'detail' => 'x', 'evidence' => 'y'],
                ['category' => 'missing_traceability', 'severity' => 'medium', 'title' => 'Mid', 'detail' => 'x', 'evidence' => 'y'],
            ],
        ]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertSame(['Bad', 'Mid', 'Minor'], array_map(
            static fn ($f): string => $f->title,
            $report->findings
        ));
    }

    public function test_an_unknown_category_or_severity_does_not_break_the_report(): void
    {
        $this->fakeAi([
            'summary' => 'Odd values from the model.',
            'verdict' => 'Something',
            'findings' => [
                ['category' => 'not_a_real_category', 'severity' => 'catastrophic', 'title' => 'Weird', 'detail' => 'x', 'evidence' => ''],
            ],
        ]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        // Unknown categories are dropped rather than rendered to a consumer.
        $this->assertCount(0, $report->findings);
        $this->assertFalse($report->hasFailed());
    }

    public function test_the_detector_sends_the_product_record_to_the_model(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create(['name' => 'Test Apple', 'origin' => 'France']);

        app(GreenwashingDetector::class)->analyze($food);

        $body = json_decode((string) $this->lastAiRequest()->body(), true);

        $this->assertSame('test-model', $body['model']);

        // The prompt introduces the record with a line of prose, so the payload
        // is the JSON object inside the message rather than the whole string.
        $content = $body['messages'][1]['content'];
        preg_match('/\{.*\}/s', $content, $match);

        $user = json_decode($match[0] ?? '', true);

        $this->assertIsArray($user, 'the product record was not valid JSON in the prompt');
        $this->assertSame('Test Apple', $user['product']['name']);
        $this->assertSame('France', $user['product']['origin']);
        $this->assertArrayHasKey('supply_chain', $user['evidence']);
        $this->assertArrayHasKey('certifications', $user['claims']);
    }

    public function test_the_detector_never_leaks_the_api_key_to_the_client(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create();

        $response = $this->get(route('products.analysis', $food));

        $response->assertOk();
        $this->assertStringNotContainsString('test-key', $response->getContent());
    }

    public function test_an_unreachable_model_reports_failure_rather_than_a_clean_verdict(): void
    {
        $this->fakeAiRaw('upstream exploded', 500);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertTrue($report->hasFailed());
        $this->assertNotEmpty($report->failure);
        // Crucially: no findings, so nothing can be read as an all-clear.
        $this->assertCount(0, $report->findings);
        $this->assertSame('', $report->verdict);
    }

    public function test_a_missing_key_reports_failure_rather_than_clearing_the_product(): void
    {
        config(['services.ai.key' => null]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertTrue($report->hasFailed());
        $this->assertStringContainsString('AI_KEY', (string) $report->failure);
    }

    public function test_markdown_fenced_json_is_still_parsed(): void
    {
        // Wrapped in a markdown fence, which models do often add.
        $fenced = Http::response([
            'choices' => [['message' => [
                'content' => "```json\n".json_encode($this->findingPayload())."\n```",
            ]]],
        ]);

        Http::fake(['ai.test/*' => $fenced, 'gemini.test/*' => $fenced]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertFalse($report->hasFailed());
        $this->assertCount(1, $report->findings);
    }

    public function test_analyses_are_cached_per_record(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create();
        $detector = app(GreenwashingDetector::class);

        $detector->analyze($food);
        $detector->analyze($food);

        // One model call, not two, for an unchanged record.
        Http::assertSentCount(1);
    }

    public function test_recording_a_new_chain_step_invalidates_the_cached_analysis(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create();
        $detector = app(GreenwashingDetector::class);

        $detector->analyze($food);

        $food->transitions()->create([
            'actor_id' => $food->producer_id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);

        $detector->analyze($food->fresh());

        Http::assertSentCount(2);
    }

    public function test_scanning_a_product_does_not_invalidate_its_analysis(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create();
        $detector = app(GreenwashingDetector::class);

        $detector->analyze($food);

        // A scan says nothing about whether the claims are supported, so it must
        // not re-bill the model.
        $food->increment('scans_count');

        $detector->analyze($food->fresh());

        Http::assertSentCount(1);
    }

    public function test_changing_the_product_name_invalidates_the_cached_analysis(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create();
        $detector = app(GreenwashingDetector::class);

        $detector->analyze($food);

        $food->update(['name' => 'Renamed Product']);

        $detector->analyze($food->fresh());

        Http::assertSentCount(2);
    }

    public function test_the_product_page_shows_the_analysis_and_the_assistant(): void
    {
        $this->fakeAi($this->findingPayload());

        $food = Food::factory()->create(['name' => 'Organic Bio Apple']);

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('AI greenwashing audit', $html);
        $this->assertStringContainsString('Eco claim with no certification', $html);
        $this->assertStringContainsString('Ask about this product', $html);
    }

    public function test_the_product_page_does_not_claim_a_clean_bill_when_the_model_fails(): void
    {
        $this->fakeAiRaw('boom', 503);

        $html = $this->get(route('products.show', Food::factory()->create()))->assertOk()->getContent();

        $this->assertStringContainsString('Analysis unavailable', $html);
        $this->assertStringNotContainsString('No issues found', $html);
    }

    // ---------- Assistant ----------

    public function test_the_assistant_answers_from_the_record_and_cites_its_sources(): void
    {
        Http::fake(['ai.test/*' => Http::response($this->chatBody([
            'answer' => 'It was grown in France.',
            'sources' => ['claims.declared_origin'],
            'cannot_answer' => false,
        ]))]);

        $food = Food::factory()->create(['origin' => 'France']);

        $answer = app(ProductAssistant::class)->ask($food, 'Where was this grown?');

        $this->assertSame('It was grown in France.', $answer->body);
        $this->assertSame(['claims.declared_origin'], $answer->sources);
        $this->assertFalse($answer->isRefusal);
    }

    public function test_the_assistant_reports_when_it_cannot_answer(): void
    {
        Http::fake(['ai.test/*' => Http::response($this->chatBody([
            'answer' => 'NutriTrace has no record of this product being organic.',
            'sources' => ['claims.certifications'],
            'cannot_answer' => true,
        ]))]);

        $answer = app(ProductAssistant::class)->ask(Food::factory()->create(), 'Is this really organic?');

        $this->assertTrue($answer->isRefusal);
        $this->assertStringContainsString('no record', $answer->body);
    }

    public function test_the_assistant_endpoint_validates_the_question(): void
    {
        $this->actingAs($this->consumer())
            ->postJson(route('products.ask', Food::factory()->create()), ['question' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');
    }

    public function test_the_assistant_endpoint_returns_a_failure_when_the_model_is_down(): void
    {
        $this->fakeAiRaw('down', 500);

        $response = $this->postJson(
            route('products.ask', Food::factory()->create()),
            ['question' => 'Where was this grown?']
        );

        $response->assertOk();
        $this->assertNotEmpty($response->json('failure'));
    }

    public function test_the_suggestions_endpoint_answers_without_calling_the_model(): void
    {
        $response = $this->getJson(route('products.ask.suggestions', Food::factory()->create()));

        $response->assertOk();
        $this->assertContains('Where was this grown?', $response->json('suggestions'));
    }

    // ---------- Recommendations ----------

    public function test_recommendations_only_offer_products_at_least_as_well_evidenced(): void
    {
        $this->fakeAi(['reasons' => []]);

        $category = Category::factory()->create();

        // Identical chain and certifications on the first two, so the grade is
        // the only difference and the recommender has to break the tie on it.
        $weak = $this->withFullChain(Food::factory()->forCategory($category)->create([
            'environmental_score' => EnvironmentalScore::E,
        ]));

        $strong = $this->withFullChain(Food::factory()->forCategory($category)->create([
            'environmental_score' => EnvironmentalScore::A,
        ]));

        // Worse on both axes: a lower grade and an unrecorded chain.
        $worse = Food::factory()->forCategory($category)->create([
            'environmental_score' => EnvironmentalScore::E,
        ]);

        $alternatives = app(ProductRecommender::class)
            ->alternativesTo($weak->fresh())
            ->pluck('food.id');

        $this->assertTrue($alternatives->contains($strong->id), 'the better grade was not offered');
        $this->assertFalse($alternatives->contains($worse->id), 'a worse product was offered');
    }

    public function test_a_product_with_the_best_grade_gets_no_alternatives(): void
    {
        $this->fakeAi(['reasons' => []]);

        $best = Food::factory()->create([
            'environmental_score' => EnvironmentalScore::A,
        ]);

        // Grade A cannot be beaten, so nothing should be suggested.
        $this->assertCount(0, app(ProductRecommender::class)->alternativesTo($best));
    }

    public function test_recommendations_still_render_when_the_model_is_unavailable(): void
    {
        $this->fakeAiRaw('down', 500);

        $consumer = $this->consumer();
        $category = Category::factory()->create();

        $eaten = Food::factory()->forCategory($category)->create([
            'environmental_score' => EnvironmentalScore::C,
        ]);

        // A second product in the same category is what makes a recommendation possible.
        Food::factory()->forCategory($category)->create([
            'environmental_score' => EnvironmentalScore::A,
        ]);

        Meal::factory()->create(['user_id' => $consumer->id])->foods()->attach($eaten);

        $recommendations = app(ProductRecommender::class)->recommendedFor($consumer);

        $this->assertGreaterThan(0, $recommendations->count());
        // The reason is computed from the record, never blank.
        $this->assertNotSame('', $recommendations->first()->why);
    }

    public function test_recommendations_are_scoped_to_categories_they_already_buy(): void
    {
        $this->fakeAi(['reasons' => []]);

        $consumer = $this->consumer();
        $eatenCategory = Category::factory()->create(['name' => 'Fruit']);
        $otherCategory = Category::factory()->create(['name' => 'Unrelated']);

        $eaten = Food::factory()->forCategory($eatenCategory)->create([
            'environmental_score' => EnvironmentalScore::C,
        ]);

        Meal::factory()->create(['user_id' => $consumer->id])->foods()->attach($eaten);

        $outside = Food::factory()->forCategory($otherCategory)->create([
            'environmental_score' => EnvironmentalScore::A,
        ]);

        $ids = app(ProductRecommender::class)->recommendedFor($consumer)->pluck('food.id');

        $this->assertFalse($ids->contains($outside->id), 'recommended something outside the consumer’s categories');
    }

    public function test_the_recommendations_page_renders(): void
    {
        $this->fakeAi(['reasons' => []]);

        $consumer = $this->consumer();
        $food = Food::factory()->create(['environmental_score' => EnvironmentalScore::A]);
        Meal::factory()->create(['user_id' => $consumer->id])->foods()->attach($food);

        $this->actingAs($consumer)
            ->get(route('consumer.recommendations'))
            ->assertOk()
            ->assertSee('More responsible products');
    }

    // ---------- Escalating a finding ----------

    public function test_a_consumer_can_report_a_detected_finding_in_one_click(): void
    {
        $food = Food::factory()->create();
        $consumer = $this->consumer();

        $response = $this->actingAs($consumer)->post(route('products.reportFinding', $food), [
            'category' => FindingCategory::ExpiredCertification->value,
            'title' => 'Certification has expired',
            'detail' => 'The only certification on file lapsed last month.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('greenwashing_reports', [
            'food_id' => $food->id,
            'user_id' => $consumer->id,
            'reason' => ReportReason::ExpiredCertification->value,
            'status' => ReportStatus::Pending->value,
        ]);
    }

    public function test_every_finding_category_maps_to_a_real_report_reason(): void
    {
        foreach (FindingCategory::cases() as $category) {
            $this->assertContains($category->reason(), ReportReason::cases());
        }
    }

    public function test_only_consumers_can_report_a_finding(): void
    {
        $food = Food::factory()->create();

        foreach (['producer', 'processor', 'distributor', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('products.reportFinding', $food), [
                    'category' => FindingCategory::Other->value,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('greenwashing_reports', 0);
    }

    public function test_reporting_a_finding_requires_a_known_category(): void
    {
        $this->actingAs($this->consumer())
            ->post(route('products.reportFinding', Food::factory()->create()), [
                'category' => 'made_up',
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_a_consumer_cannot_file_two_pending_reports_for_one_product(): void
    {
        $food = Food::factory()->create();
        $consumer = $this->consumer();

        $payload = ['category' => FindingCategory::Other->value];

        $this->actingAs($consumer)->post(route('products.reportFinding', $food), $payload);

        $this->actingAs($consumer)
            ->post(route('products.reportFinding', $food), $payload)
            ->assertSessionHasErrors('reason');

        $this->assertSame(1, $food->reports()->count());
    }

    // ---------- The consumer space ----------

    public function test_the_consumer_space_requires_authentication(): void
    {
        $this->get(route('consumer.space'))->assertRedirect(route('login'));
    }

    public function test_the_consumer_space_audits_the_products_they_actually_eat(): void
    {
        $this->fakeAi($this->findingPayload());

        $consumer = $this->consumer();

        // Separate categories, so the untouched product is not merely unaudited
        // but genuinely unreachable: it cannot even be recommended.
        $eaten = Food::factory()
            ->forCategory(Category::factory()->create(['name' => 'Eaten Shelf']))
            ->create(['name' => 'Scrambled Eggs']);

        $untouched = Food::factory()
            ->forCategory(Category::factory()->create(['name' => 'Other Shelf']))
            ->create(['name' => 'Never Eaten Product']);

        Meal::factory()->create(['user_id' => $consumer->id])->foods()->attach($eaten);

        $html = $this->actingAs($consumer)->get(route('consumer.space'))->assertOk()->getContent();

        $this->assertStringContainsString('Espace Consommateur', $html);
        $this->assertStringContainsString('Scrambled Eggs', $html);
        $this->assertStringNotContainsString('Never Eaten Product', $html);
        $this->assertStringContainsString('AI audit of what you eat', $html);
    }

    public function test_the_consumer_space_says_so_when_nothing_has_been_logged(): void
    {
        $this->fakeAi(['summary' => '', 'verdict' => 'ok', 'findings' => []]);

        $this->actingAs($this->consumer())
            ->get(route('consumer.space'))
            ->assertOk()
            ->assertSee('Nothing to audit yet');
    }

    // ---------- The AI surface itself ----------

    public function test_gemini_is_called_with_the_key_as_a_header_not_a_bearer_token(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.ai.gemini_model' => 'gemini-test',
            'services.ai.gemini_base_url' => 'https://gemini.test/v1beta',
        ]);

        $this->fakeAi($this->findingPayload());

        app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $request = $this->lastAiRequest();

        // Gemini rejects "Authorization: Bearer" outright with a 401.
        $this->assertArrayNotHasKey('Authorization', $request->headers());
        $this->assertSame('test-key', $request->header('x-goog-api-key')[0] ?? null);
        $this->assertStringNotContainsString('key=test-key', $request->url());
    }

    public function test_the_openai_shape_is_used_for_other_providers(): void
    {
        $this->fakeAi($this->findingPayload());

        app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $request = $this->lastAiRequest();
        $this->assertSame('https://ai.test/v1/chat/completions', $request->url());
        $this->assertSame('Bearer test-key', $request->header('Authorization')[0] ?? null);

        $body = json_decode((string) $request->body(), true);
        $this->assertArrayHasKey('response_format', $body);
    }

    public function test_gemini_gets_the_schema_and_no_thinking_budget(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.ai.gemini_model' => 'gemini-test',
            'services.ai.gemini_base_url' => 'https://gemini.test/v1beta',
        ]);

        $this->fakeAi($this->findingPayload());

        app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $body = json_decode((string) $this->lastAiRequest()->body(), true);
        $config = $body['generationConfig'];

        // Without the schema the model invents field names and every field the
        // caller reads back comes back empty.
        $this->assertArrayHasKey('responseSchema', $config);
        $this->assertArrayNotHasKey('additionalProperties', $config['responseSchema']);
        $this->assertSame(0, $config['thinkingConfig']['thinkingBudget']);
        $this->assertSame('application/json', $config['responseMimeType']);
    }

    public function test_a_transient_throttle_is_retried(): void
    {
        $sequence = Http::sequence()
            ->push('busy', 503)
            ->push($this->chatBody($this->findingPayload()));

        Http::fake(['ai.test/*' => $sequence, 'gemini.test/*' => $sequence]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertFalse($report->hasFailed());
        Http::assertSentCount(2);
    }

    public function test_findings_never_contain_markup_from_the_model(): void
    {
        $this->fakeAi([
            'summary' => 'A <script>alert(1)</script> summary   with    spaces.',
            'verdict' => 'Something <b>bad</b>',
            'findings' => [
                [
                    'category' => 'unverifiable_claim',
                    'severity' => 'high',
                    'title' => 'Title with <em>markup</em>',
                    'detail' => "Detail\n\nwith   newlines",
                    'evidence' => 'claims.certifications',
                ],
            ],
        ]);

        $report = app(GreenwashingDetector::class)->analyze(Food::factory()->create());

        $this->assertStringNotContainsString('<script>', $report->summary);
        $this->assertStringNotContainsString('<b>', $report->verdict);
        $this->assertStringNotContainsString('<em>', $report->findings[0]->title);
        $this->assertStringNotContainsString("\n", $report->findings[0]->detail);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\User;
use App\Services\Agriculture\FarmStoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmStoryTest extends TestCase
{
    use RefreshDatabase;

    private User $producer;

    private AgriculturalRegion $region;

    private Farm $farm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create(['role' => 'producer']);

        $this->region = AgriculturalRegion::create([
            'name' => 'Vallée de la Medjerda',
            'code' => 'REG-MEDJ',
            'climate' => 'Continental temperate',
            'soil_type' => 'Deep alluvium and fertile silt',
            'description' => 'Tunisia primary granary and fertile river basin.',
        ]);

        $this->farm = Farm::create([
            'name' => 'Ferme de la Vallée Verte',
            'producer_name' => $this->producer->name,
            'address' => 'Béja Nord, Plaine de la Medjerda',
            'surface_hectares' => 45.00,
            'farming_type' => 'Organic',
            'soil_type' => 'Alluvial silt',
            'agricultural_region_id' => $this->region->id,
            'user_id' => $this->producer->id,
            'status' => 'approved',
            'description' => 'Family-owned holding preserving ancient cereal grains and heritage legumes.',
        ]);
    }

    public function test_verify_page_displays_terroir_storytelling_with_heuristic_fallback(): void
    {
        Config::set('services.ai.key', null);

        // Story shown only on QR verify page
        $response = $this->get(route('farms.verify', $this->farm));
        $response->assertOk();
        $response->assertSee(__('AI Traceability Narrative'));
        $response->assertSee(__('Terroir & Authenticity'));
        $response->assertSee($this->region->name);
    }

    public function test_storytelling_uses_ai_model_when_configured(): void
    {
        Config::set('services.ai.key', 'fake-test-key');
        Config::set('services.ai.provider', 'groq');
        Config::set('services.ai.base_url', 'https://api.groq.com/openai/v1');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');

        Http::preventStrayRequests();

        $aiPayload = [
            'headline' => 'The Gold of the Medjerda: Living Earth and Ancient Grains',
            'story' => "Surrounded by rolling green hills, this certified organic farm brings life back to the historic grain valleys.\n\nEvery seed is nourished by alluvial minerals and honest care, delivering pure nutrition straight to your table.",
            'terroir_highlight' => 'Sun-kissed grains nurtured by deep alluvial silt along the flowing river.',
            'commitments' => [
                [
                    'title' => 'Ancient Biodiversity',
                    'description' => 'Preserving heritage non-GMO seeds adapted to natural local rainfall.',
                ],
                [
                    'title' => 'Chemical-Free Purity',
                    'description' => 'Certified organic without any synthetic pesticides or herbicides.',
                ],
                [
                    'title' => '100% Farm Transparency',
                    'description' => 'Fully verified traceability recorded in the NutriTrace registry.',
                ],
            ],
        ];

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($aiPayload),
                        ],
                    ],
                ],
            ]),
        ]);

        /** @var FarmStoryService $service */
        $service = app(FarmStoryService::class);
        $service->clearCache($this->farm);

        // Story is shown on the public QR verify page, not the certificate
        $response = $this->get(route('farms.verify', $this->farm));

        $response->assertOk();
        $response->assertSee('The Gold of the Medjerda: Living Earth and Ancient Grains');
        $response->assertSee('Ancient Biodiversity');
        $response->assertSee('Chemical-Free Purity');

        // The banner reads the report's terroir highlight; the old tagline
        // property never existed, so this block silently disappeared.
        $response->assertSee('Sun-kissed grains nurtured by deep alluvial silt along the flowing river.');
    }

    public function test_updating_a_farm_invalidates_the_cached_narrative_and_diagnostic(): void
    {
        Config::set('services.ai.key', 'fake-test-key');
        Config::set('services.ai.provider', 'groq');
        Config::set('services.ai.base_url', 'https://api.groq.com/openai/v1');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');

        Http::preventStrayRequests();

        $calls = 0;

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => function () use (&$calls) {
                $calls++;
                $generation = $calls <= 2 ? 'First' : 'Second';

                return Http::response([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode($this->aiPayload($generation)),
                        ],
                    ]],
                ]);
            },
        ]);

        $this->actingAs($this->producer)
            ->get(route('farms.show', $this->farm))
            ->assertOk()
            ->assertSee('Headline First')
            ->assertSee('Verdict First');

        $this->actingAs($this->producer)
            ->put(route('farms.update', $this->farm), [
                'agricultural_region_id' => $this->region->id,
                'name' => 'Ferme Renamed',
                'soil_type' => $this->farm->soil_type,
                'producer_name' => $this->farm->producer_name,
                'address' => $this->farm->address,
                'surface_hectares' => $this->farm->surface_hectares,
                'farming_type' => $this->farm->farming_type,
                'phone' => $this->farm->phone,
                'description' => $this->farm->description,
            ])
            ->assertRedirect(route('farms.index'));

        $this->actingAs($this->producer)
            ->get(route('farms.show', $this->farm))
            ->assertOk()
            ->assertSee('Headline Second')
            ->assertSee('Verdict Second');
    }

    public function test_the_story_prompt_asks_for_the_request_locale(): void
    {
        Config::set('services.ai.key', 'fake-test-key');
        Config::set('services.ai.provider', 'groq');
        Config::set('services.ai.base_url', 'https://api.groq.com/openai/v1');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');

        Http::preventStrayRequests();
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode($this->aiPayload('French')),
                    ],
                ]],
            ]),
        ]);

        $this->get(route('farms.verify', ['farm' => $this->farm, 'locale' => 'fr']))
            ->assertOk();

        $request = Http::recorded()->last()[0];
        $body = json_decode((string) $request->body(), true);

        $this->assertStringContainsString('French', $body['messages'][0]['content']);
    }

    /**
     * @return array<string, mixed>
     */
    private function aiPayload(string $generation): array
    {
        return [
            'score' => 80,
            'verdict' => "Verdict {$generation}",
            'water_risk_level' => 'Low',
            'soil_health_level' => 'Good',
            'strengths' => [],
            'vulnerabilities' => [],
            'recommendations' => [],
            'summary' => "Summary {$generation}",
            'headline' => "Headline {$generation}",
            'story' => "Story {$generation}",
            'terroir_highlight' => "Terroir {$generation}",
            'commitments' => [],
        ];
    }

    public function test_producer_can_regenerate_storytelling(): void
    {
        $response = $this->actingAs($this->producer)
            ->post(route('farms.story', $this->farm));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('Farm consumer storytelling regenerated successfully.'));
    }

    public function test_producer_cannot_regenerate_story_of_another_producer_farm(): void
    {
        $otherProducer = User::factory()->create(['role' => 'producer']);

        $response = $this->actingAs($otherProducer)
            ->post(route('farms.story', $this->farm));

        $response->assertForbidden();
    }
}

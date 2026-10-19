<?php

namespace Tests\Feature;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\User;
use App\Services\Agriculture\FarmAiDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmAiDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $producer;

    private AgriculturalRegion $region;

    private Farm $farm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->producer = User::factory()->create(['role' => 'producer']);

        $this->region = AgriculturalRegion::create([
            'name' => 'Cap Bon Fertile',
            'code' => 'REG-CAPBON',
            'climate' => 'Mediterranean sub-humid',
            'soil_type' => 'Clay-loam fertile',
            'description' => 'Renowned agricultural area with citrus and olive groves.',
        ]);

        $this->farm = Farm::create([
            'name' => 'Domaine Bio Olivier',
            'producer_name' => $this->producer->name,
            'address' => 'Route de Nabeul, Grombalia',
            'surface_hectares' => 24.50,
            'farming_type' => 'Organic',
            'soil_type' => 'Clay-loam',
            'agricultural_region_id' => $this->region->id,
            'user_id' => $this->producer->id,
            'status' => 'approved',
            'description' => 'Certified organic farm focusing on regenerative agriculture.',
        ]);
    }

    public function test_farm_show_page_displays_agro_ecological_diagnostic_with_heuristics_when_ai_unconfigured(): void
    {
        Config::set('services.ai.key', null);

        $response = $this->actingAs($this->producer)
            ->get(route('farms.show', $this->farm));

        $response->assertOk();
        $response->assertSee(__('AI Agro-Ecological Diagnostic'));
        $response->assertSee(__('Water Stress Risk'));
        $response->assertSee(__('Soil Health'));
        $response->assertSee(__('Agro-Ecological Heuristics'));
        $response->assertSee(__('AI Recommendations for Farm Optimization'));
    }

    public function test_farm_diagnostic_uses_ai_model_when_configured(): void
    {
        Config::set('services.ai.key', 'fake-test-key');
        Config::set('services.ai.provider', 'groq');
        Config::set('services.ai.base_url', 'https://api.groq.com/openai/v1');
        Config::set('services.ai.model', 'llama-3.3-70b-versatile');

        Http::preventStrayRequests();

        $aiPayload = [
            'score' => 88,
            'verdict' => 'Outstanding Regenerative Performance',
            'water_risk_level' => 'Low',
            'soil_health_level' => 'Excellent',
            'strengths' => [
                'Excellent organic nitrogen fixation',
                'Comprehensive natural biological pest control',
            ],
            'vulnerabilities' => [
                'Mild sensitivity to mid-summer heat spikes',
            ],
            'recommendations' => [
                'Increase biochar application to optimize water retention',
                'Introduce intercropping with clover and vetches',
            ],
            'summary' => 'This farm represents top-tier agro-ecological excellence.',
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

        /** @var FarmAiDiagnosticService $service */
        $service = app(FarmAiDiagnosticService::class);
        $service->clearCache($this->farm);

        $response = $this->actingAs($this->producer)
            ->get(route('farms.show', $this->farm));

        $response->assertOk();
        $response->assertSee('88/100');
        $response->assertSee('Outstanding Regenerative Performance');
        $response->assertSee('Excellent organic nitrogen fixation');
        $response->assertSee('Increase biochar application to optimize water retention');
        $response->assertSee(__('NutriTrace AI Model'));
    }

    public function test_post_diagnostic_route_refreshes_analysis(): void
    {
        $response = $this->actingAs($this->producer)
            ->post(route('farms.diagnostic', $this->farm));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('AI agro-ecological diagnostic regenerated successfully.'));
    }

    public function test_producer_cannot_view_diagnostic_of_another_producer(): void
    {
        $otherProducer = User::factory()->create(['role' => 'producer']);

        $response = $this->actingAs($otherProducer)
            ->get(route('farms.show', $this->farm));

        $response->assertForbidden();
    }

    public function test_admin_can_view_and_refresh_diagnostic_of_any_farm(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('farms.show', $this->farm));

        $response->assertOk();
        $response->assertSee(__('AI Agro-Ecological Diagnostic'));

        $refresh = $this->actingAs($this->admin)
            ->post(route('farms.diagnostic', $this->farm));

        $refresh->assertRedirect();
    }
}

<?php

namespace App\Services\Agriculture;

use App\Enums\Locale;
use App\Models\Farm;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FarmAiDiagnosticService
{
    private const MAX_OUTPUT_TOKENS = 1500;

    public function __construct(private readonly AiClient $ai) {}

    /**
     * Run an agro-ecological diagnostic on the given farm.
     *
     * Cached for 12 hours unless $fresh is true. Never throws: if the AI provider
     * is unreachable, unconfigured or hits a limit, an intelligent deterministic
     * agronomic heuristic takes over honestly without breaking the application.
     */
    public function analyze(Farm $farm, bool $fresh = false): FarmAiDiagnosticReport
    {
        $cacheKey = $this->cacheKey($farm);

        if (! $fresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return FarmAiDiagnosticReport::fromArray($cached);
            }
        }

        if ($this->ai->isConfigured()) {
            try {
                $response = $this->ai->json(
                    $this->messages($farm),
                    $this->schema(),
                    self::MAX_OUTPUT_TOKENS
                );

                $report = FarmAiDiagnosticReport::fromModelResponse($response);
                Cache::put($cacheKey, $report->toArray(), now()->addHours(12));

                return $report;
            } catch (AiException $e) {
                Log::warning('Farm AI diagnostic call failed, falling back to agronomic heuristic.', [
                    'farm_id' => $farm->id,
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        // Deterministic agronomic evaluation fallback
        $report = $this->generateHeuristicReport($farm);
        Cache::put($cacheKey, $report->toArray(), now()->addHours(12));

        return $report;
    }

    public function clearCache(Farm $farm): void
    {
        // A farm is cached once per language, so an update made while the
        // producer is working in English must not leave the French page
        // showing the old diagnostic.
        foreach (Locale::values() as $locale) {
            Cache::forget($this->cacheKey($farm, $locale));
        }
    }

    private function cacheKey(Farm $farm, ?string $locale = null): string
    {
        $variant = sha1('groq|'.$this->ai->model());

        return "farm_ai_diagnostic_{$farm->id}_".($locale ?? app()->getLocale()).'_'.substr($variant, 0, 12);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function messages(Farm $farm): array
    {
        $region = $farm->region;
        $regionName = $region?->name ?? 'Not assigned';
        $regionClimate = $region?->climate ?? 'Temperate';
        $regionSoil = $region?->soil_type ?? 'Standard agricultural soil';

        $prompt = implode("\n", [
            'Analyze this agricultural farm and assess its agro-ecological sustainability and climate resilience.',
            '',
            "Farm Name: {$farm->name}",
            "Farming Type: {$farm->farming_type}",
            "Surface: {$farm->surface_hectares} hectares",
            'Soil Type: '.($farm->soil_type ?? $regionSoil),
            "Agricultural Region: {$regionName}",
            "Regional Climate: {$regionClimate}",
            "Producer / Owner: {$farm->producer_name}",
            'Description: '.($farm->description ?? 'No specific description provided.'),
            '',
            'Provide a rigorous agro-ecological assessment:',
            '1. Score (0 to 100): overall sustainability and ecological resilience.',
            '2. Verdict: concise summary of the assessment (e.g. "Exemplary Organic Resilience", "Promising Sustainable Transition").',
            '3. Water Risk Level: "Low", "Medium", or "High" given the regional climate and soil type.',
            '4. Soil Health Level: "Excellent", "Good", or "Degraded" according to the farming practices and soil.',
            '5. Strengths: 2 to 4 bullet points highlighting ecological advantages and virtuous practices.',
            '6. Vulnerabilities: 1 to 3 risks (e.g. water scarcity, monoculture risk, erosion sensitivity).',
            '7. Recommendations: 3 actionable agronomic improvements to optimize soil health, yield, and biodiversity.',
            '8. Summary: 2-3 sentences summarizing the farm profile.',
        ]);

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are an expert agro-ecologist and agronomic sustainability auditor for NutriTrace.',
                    'You evaluate agricultural farms based on their farming type (Organic, Sustainable, Biodynamic, Traditional),',
                    'soil characteristics, surface, and regional climate context.',
                    'Always provide realistic, actionable, and science-backed agricultural insights.',
                    'Write every field in '.Locale::resolve(app()->getLocale())->promptLabel().'.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'score',
                'verdict',
                'water_risk_level',
                'soil_health_level',
                'strengths',
                'vulnerabilities',
                'recommendations',
                'summary',
            ],
            'properties' => [
                'score' => [
                    'type' => 'integer',
                    'description' => 'Agro-ecological score between 0 and 100.',
                ],
                'verdict' => [
                    'type' => 'string',
                    'description' => 'Concise verdict describing the farm sustainability profile.',
                ],
                'water_risk_level' => [
                    'type' => 'string',
                    'enum' => ['Low', 'Medium', 'High'],
                    'description' => 'Risk level of water stress or irrigation inefficiency.',
                ],
                'soil_health_level' => [
                    'type' => 'string',
                    'enum' => ['Excellent', 'Good', 'Degraded'],
                    'description' => 'Estimated biological soil health and fertility.',
                ],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Ecological strengths and virtuous agricultural practices.',
                ],
                'vulnerabilities' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Environmental risks or areas of vulnerability.',
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Concrete agronomic recommendations for improvements.',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'Brief executive summary of the evaluation.',
                ],
            ],
        ];
    }

    /**
     * Deterministic agronomic calculation when AI is unavailable.
     */
    private function generateHeuristicReport(Farm $farm): FarmAiDiagnosticReport
    {
        $baseScore = match ($farm->farming_type) {
            'Biodynamic' => 92,
            'Organic' => 86,
            'Sustainable' => 74,
            default => 58,
        };

        $soil = strtolower($farm->soil_type ?? $farm->region?->soil_type ?? '');
        $climate = strtolower($farm->region?->climate ?? '');

        $waterRisk = 'Medium';
        if (str_contains($climate, 'arid') || str_contains($climate, 'dry') || str_contains($climate, 'mediterranean')) {
            $waterRisk = str_contains($soil, 'sandy') ? 'High' : 'Medium';
        } elseif (str_contains($climate, 'oceanic') || str_contains($climate, 'humid')) {
            $waterRisk = 'Low';
        }

        $soilHealth = match ($farm->farming_type) {
            'Biodynamic', 'Organic' => 'Excellent',
            'Sustainable' => 'Good',
            default => 'Good',
        };

        $strengths = [];
        if ($farm->farming_type === 'Organic' || $farm->farming_type === 'Biodynamic') {
            $strengths[] = __('Zero synthetic chemical pesticides, protecting groundwater and soil biodiversity.');
            $strengths[] = __('Certified organic practices supporting natural pollination and beneficial insects.');
        } elseif ($farm->farming_type === 'Sustainable') {
            $strengths[] = __('Integrated pest management minimizing chemical interventions.');
            $strengths[] = __('Rational soil and nutrient management preserving fertility.');
        } else {
            $strengths[] = __('Established local agricultural production with stable seasonal output.');
        }

        if ((float) $farm->surface_hectares >= 50) {
            $strengths[] = __('Large continuous plot offering great potential for agroforestry corridors.');
        } else {
            $strengths[] = __('Human-scale farm allowing close monitoring of crop health and soil dynamics.');
        }

        $vulnerabilities = [];
        if ($waterRisk === 'High') {
            $vulnerabilities[] = __('High sensitivity to seasonal drought and summer water restrictions.');
        }
        if (str_contains($soil, 'clay')) {
            $vulnerabilities[] = __('Heavy clay soil subject to compaction during wet harvest periods.');
        } elseif (str_contains($soil, 'sandy')) {
            $vulnerabilities[] = __('Sandy substrate with high leaching rate requiring frequent organic matter inputs.');
        } else {
            $vulnerabilities[] = __('Vulnerability to climatic variability and extreme weather fluctuations.');
        }

        $recommendations = [
            __('Implement continuous cover cropping and organic mulching to prevent erosion and retain soil moisture.'),
            __('Introduce nitrogen-fixing legumes in crop rotation to naturally enrich soil nitrogen reserves.'),
            __('Install drip micro-irrigation and rainwater harvesting ponds to safeguard against dry spells.'),
        ];

        $verdict = match (true) {
            $baseScore >= 85 => __('Exemplary Agro-Ecological Resilience'),
            $baseScore >= 70 => __('High Environmental Performance'),
            default => __('Satisfactory with High Potential for Ecological Transition'),
        };

        $summary = __('The farm demonstrates a reliable agronomic profile in the :region region, with strong opportunities to strengthen soil resilience.', [
            'region' => $farm->region?->name ?? __('local'),
        ]);

        return new FarmAiDiagnosticReport(
            score: $baseScore,
            verdict: $verdict,
            waterRiskLevel: $waterRisk,
            soilHealthLevel: $soilHealth,
            strengths: $strengths,
            vulnerabilities: $vulnerabilities,
            recommendations: $recommendations,
            summary: $summary,
            isAiGenerated: false,
            analyzedAt: now()->toIso8601String(),
        );
    }
}

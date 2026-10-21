<?php

namespace App\Services\Agriculture;

use App\Enums\Locale;
use App\LocalizedNumber;
use App\Models\Farm;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FarmStoryService
{
    private const MAX_OUTPUT_TOKENS = 1200;

    public function __construct(private readonly AiClient $ai) {}

    /**
     * Generate or retrieve the certified storytelling narrative for a farm.
     *
     * Cached for 24 hours unless $fresh is requested. Falls back to a deterministic
     * narrative when AI is unconfigured or unreachable.
     */
    public function generate(Farm $farm, bool $fresh = false): FarmStoryReport
    {
        $cacheKey = $this->cacheKey($farm);

        if (! $fresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return FarmStoryReport::fromArray($cached);
            }
        }

        if ($this->ai->isConfigured()) {
            try {
                $response = $this->ai->json(
                    $this->messages($farm),
                    $this->schema(),
                    self::MAX_OUTPUT_TOKENS
                );

                $story = FarmStoryReport::fromModelResponse($response);
                Cache::put($cacheKey, $story->toArray(), now()->addHours(24));

                return $story;
            } catch (AiException $e) {
                Log::warning('Farm AI storytelling generation failed, falling back to terroir generator.', [
                    'farm_id' => $farm->id,
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        $story = $this->generateHeuristicStory($farm);
        Cache::put($cacheKey, $story->toArray(), now()->addHours(24));

        return $story;
    }

    public function clearCache(Farm $farm): void
    {
        // A farm is cached once per language, so an update made while the
        // producer is working in English must not leave the French page
        // showing the old narrative.
        foreach (Locale::values() as $locale) {
            Cache::forget($this->cacheKey($farm, $locale));
        }
    }

    private function cacheKey(Farm $farm, ?string $locale = null): string
    {
        $variant = sha1('groq|'.$this->ai->model());

        return "farm_story_{$farm->id}_".($locale ?? app()->getLocale()).'_'.substr($variant, 0, 12);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function messages(Farm $farm): array
    {
        $region = $farm->region;
        $regionName = $region?->name ?? 'Local Terroir';
        $regionClimate = $region?->climate ?? 'Mediterranean';
        $soil = $farm->soil_type ?? $region?->soil_type ?? 'Rich agricultural soil';

        $prompt = implode("\n", [
            'Create an authentic and inspiring consumer-facing story for an agricultural food traceability platform.',
            'A consumer has just scanned the QR code on a food product and wants to know where it comes from.',
            '',
            "Farm Name: {$farm->name}",
            "Producer / Farmer: {$farm->producer_name}",
            "Agricultural Region: {$regionName}",
            "Climate: {$regionClimate}",
            "Soil Character: {$soil}",
            "Farming Philosophy: {$farm->farming_type}",
            "Surface: {$farm->surface_hectares} hectares",
            'Farmer Notes: '.($farm->description ?? 'Dedicated to wholesome regional agricultural production.'),
            '',
            'Provide:',
            '1. headline: an engaging, poetic and authentic title (e.g. "Rooted in the Fertile Lands of Cap Bon: A Journey of Living Soil").',
            '2. story: 2 evocative paragraphs telling the story of this farm, its land, the hands that nurture it, and its respect for nature and seasonal rhythm.',
            '3. terroir_highlight: a short sensory sentence highlighting the unique taste and quality imparted by the local soil and climate.',
            '4. commitments: exactly 3 distinct consumer commitments with a clear title and description (e.g. "Purity & Nature", "Total Traceability", "Local Terroir Pride").',
        ]);

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are the lead agricultural storyteller for NutriTrace.',
                    'You craft vivid, authentic, transparent, and evocative stories connecting consumers to real farmers and terroirs.',
                    'Never fabricate false claims; stay grounded in the farming type and regional climate provided.',
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
            'required' => ['headline', 'story', 'terroir_highlight', 'commitments'],
            'properties' => [
                'headline' => [
                    'type' => 'string',
                    'description' => 'Evocative and inspiring headline for the farm story.',
                ],
                'story' => [
                    'type' => 'string',
                    'description' => 'Two narrative paragraphs connecting the consumer to the farm terroir and farmer.',
                ],
                'terroir_highlight' => [
                    'type' => 'string',
                    'description' => 'Sensory summary of the soil, sun and climate impact on the harvest.',
                ],
                'commitments' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'description'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Deterministic storytelling when AI provider is unconfigured.
     */
    private function generateHeuristicStory(Farm $farm): FarmStoryReport
    {
        $regionName = $farm->region?->name ?? __('our fertile regions');
        $soil = $farm->soil_type ?? $farm->region?->soil_type ?? __('nourishing soils');
        $farmingType = Farm::farmingTypeLabel($farm->farming_type);

        $headline = __('Rooted in :region: The Heritage of :name', [
            'region' => $regionName,
            'name' => $farm->name,
        ]);

        $p1 = __('Nestled in the heart of the :region agricultural area, :name embodies a deep harmony between agricultural tradition and ecological stewardship. Cultivated across :surface hectares of :soil, each harvest reflects the unique climate and living biological heritage of its native soil.', [
            'region' => $regionName,
            'name' => $farm->name,
            'surface' => LocalizedNumber::format($farm->surface_hectares, 1),
            'soil' => $soil,
        ]);

        $p2 = match ($farm->farming_type) {
            'Organic', 'Biodynamic' => __('Guided by strict :type principles, this holding rejects synthetic chemicals, allowing natural ecosystems and beneficial soil micro-organisms to flourish. From our fields directly to your plate, every harvest carries the uncompromising purity and rich flavor of certified organic farming.', ['type' => $farmingType]),
            'Sustainable' => __('Through certified :type practices, the farmer prioritizes soil conservation, water management, and biodiversity protection. This sustainable commitment ensures that nutritious, high-quality food reaches your table while safeguarding the land for generations to come.', ['type' => $farmingType]),
            default => __('With dedication to genuine agricultural craftsmanship, the farm adheres to rigorous standards of transparency and quality, ensuring full traceability from the fertile furrow all the way to your dining table.', ['type' => $farmingType]),
        };

        $story = $p1."\n\n".$p2;

        $terroirHighlight = __('Nurtured by the unique climate of :region and healthy :soil, yielding food with authentic taste and certified integrity.', [
            'region' => $regionName,
            'soil' => $soil,
        ]);

        $commitments = [
            [
                'title' => __('Terroir & Authenticity'),
                'description' => __('100% grown and harvested locally in :region with complete origin verification.', ['region' => $regionName]),
            ],
            [
                'title' => __('Ecological Responsibility'),
                'description' => __(':type certified cultivation respecting soil life, water resources and native pollinators.', ['type' => $farmingType]),
            ],
            [
                'title' => __('Transparent Traceability'),
                'description' => __('Directly verifiable via NutriTrace official agricultural registry records.'),
            ],
        ];

        return new FarmStoryReport(
            headline: $headline,
            story: $story,
            terroirHighlight: $terroirHighlight,
            commitments: $commitments,
            isAiGenerated: false,
            generatedAt: now()->toIso8601String(),
        );
    }
}

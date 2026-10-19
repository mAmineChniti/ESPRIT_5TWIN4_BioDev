<?php

namespace App\Services\Agriculture;

class FarmStoryReport
{
    /**
     * @param  list<array{title: string, description: string}>  $commitments
     */
    public function __construct(
        public readonly string $headline,
        public readonly string $story,
        public readonly string $terroirHighlight,
        public readonly array $commitments,
        public readonly bool $isAiGenerated,
        public readonly string $generatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromModelResponse(array $data): self
    {
        $commitments = [];
        if (isset($data['commitments']) && is_array($data['commitments'])) {
            foreach ($data['commitments'] as $item) {
                if (is_array($item)) {
                    $commitments[] = [
                        'title' => (string) ($item['title'] ?? ''),
                        'description' => (string) ($item['description'] ?? ''),
                    ];
                }
            }
        }

        return new self(
            headline: (string) ($data['headline'] ?? __('From Our Soil to Your Plate')),
            story: (string) ($data['story'] ?? ''),
            terroirHighlight: (string) ($data['terroir_highlight'] ?? ''),
            commitments: $commitments,
            isAiGenerated: true,
            generatedAt: now()->toIso8601String(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            headline: (string) ($payload['headline'] ?? ''),
            story: (string) ($payload['story'] ?? ''),
            terroirHighlight: (string) ($payload['terroir_highlight'] ?? ''),
            commitments: (array) ($payload['commitments'] ?? []),
            isAiGenerated: (bool) ($payload['is_ai_generated'] ?? false),
            generatedAt: (string) ($payload['generated_at'] ?? now()->toIso8601String()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'headline' => $this->headline,
            'story' => $this->story,
            'terroir_highlight' => $this->terroirHighlight,
            'commitments' => $this->commitments,
            'is_ai_generated' => $this->isAiGenerated,
            'generated_at' => $this->generatedAt,
        ];
    }
}

<?php

namespace App\Services\Recommendations;

use App\Models\Food;

/**
 * One suggested product, with the reason it was suggested.
 *
 * The rank is computed from the record; only the wording of `why` is written by
 * the model, because explaining a comparison is a language task while choosing
 * the comparison is a data one.
 */
final readonly class Recommendation
{
    /**
     * @param  array<string, mixed>  $food
     */
    public function __construct(
        public Food $food,
        public string $why,
        public int $transparencyScore,
        public ?string $grade,
        public string $reason,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->food->id,
            'name' => $this->food->name,
            'category' => $this->food->category?->name,
            'origin' => $this->food->origin,
            'grade' => $this->grade,
            'transparency_score' => $this->transparencyScore,
            'verdict' => $this->food->trustVerdict()['level'],
            'why' => $this->why,
            'reason' => $this->reason,
            'certifications' => $this->food->certifications->pluck('name')->all(),
        ];
    }
}

<?php

namespace App\Services\Greenwashing;

use App\Models\Food;

/**
 * The identity of the product an analysis belongs to, carried through the
 * result so a failure can still be rendered against the right product.
 */
final readonly class FoodSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $origin,
    ) {}

    public static function fromFood(Food $food): self
    {
        return new self(
            id: $food->id,
            name: $food->name,
            origin: $food->origin,
        );
    }
}

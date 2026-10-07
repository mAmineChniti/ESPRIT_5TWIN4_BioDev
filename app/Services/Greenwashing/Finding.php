<?php

namespace App\Services\Greenwashing;

use App\Enums\FindingCategory;
use App\Enums\FindingSeverity;

/**
 * One problem the detector found, in the consumer's own terms.
 *
 * Findings are produced by the model but always carry the product data that
 * prompted them, so a consumer can check the claim against the record rather
 * than taking it on trust.
 */
final readonly class Finding
{
    public function __construct(
        public FindingCategory $category,
        public FindingSeverity $severity,
        public string $title,
        public string $detail,
        public string $evidence = '',
    ) {}

    /**
     * @param  array<string, mixed>  $row  one entry of the model's JSON
     */
    public static function fromArray(array $row): ?self
    {
        $category = is_string($row['category'] ?? null)
            ? FindingCategory::tryFrom($row['category'])
            : null;

        if ($category === null || ($category === FindingCategory::Other && blank($row['detail'] ?? null))) {
            return null;
        }

        return $category->toFinding(
            $row['title'] ?? null,
            $row['detail'] ?? null,
            $row['severity'] ?? null,
            $row['evidence'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'severity' => $this->severity->value,
            'title' => $this->title,
            'detail' => $this->detail,
            'evidence' => $this->evidence,
        ];
    }
}

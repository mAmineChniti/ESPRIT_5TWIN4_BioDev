<?php

namespace App\Services\Logistics;

use App\Enums\TransportMode;
use App\LocalizedNumber;

/**
 * The carbon analysis of one warehouse: figures computed by the server, and
 * the text that explains them.
 *
 * Every number comes from the recorded shipments. Only the summary and the
 * recommendations may be written by the AI model.
 */
final readonly class WarehouseCarbonReport
{
    /**
     * @param  array{count: int, total_kg: float, average_kg: float, saving_kg: float, by_mode: list<array{mode: TransportMode, count: int, kg: float, share: float}>, top: list<array{reference: string, destination: string, mode: TransportMode, kg: float, share: float}>}  $stats
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public array $stats,
        public string $summary,
        public array $recommendations,
        public bool $fromAi,
    ) {}

    /**
     * The JSON the warehouse page reads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'empty' => false,
            'summary' => $this->summary,
            'recommendations' => $this->recommendations,
            'source' => $this->fromAi ? 'ai' : 'rule',
            'shipments_count' => $this->stats['count'],
            'total_kg' => $this->stats['total_kg'],
            'total_text' => LocalizedNumber::format($this->stats['total_kg'], 2),
            'average_text' => LocalizedNumber::format($this->stats['average_kg'], 2),
            'saving_kg' => $this->stats['saving_kg'],
            'saving_text' => LocalizedNumber::format($this->stats['saving_kg'], 2),
            'by_mode' => array_map(fn (array $row): array => [
                'mode' => $row['mode']->value,
                'label' => $row['mode']->label(),
                'count' => $row['count'],
                'kg_text' => LocalizedNumber::format($row['kg'], 2),
                'share' => $row['share'],
                'share_text' => LocalizedNumber::format($row['share'], 1),
            ], $this->stats['by_mode']),
            'top' => array_map(fn (array $row): array => [
                'reference' => $row['reference'],
                'destination' => $row['destination'],
                'mode_label' => $row['mode']->label(),
                'kg_text' => LocalizedNumber::format($row['kg'], 2),
                'share_text' => LocalizedNumber::format($row['share'], 1),
            ], $this->stats['top']),
        ];
    }
}

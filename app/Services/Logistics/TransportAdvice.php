<?php

namespace App\Services\Logistics;

use App\Enums\TransportMode;
use App\LocalizedNumber;

/**
 * The transport mode suggested for a load, with the footprints behind it.
 *
 * Every footprint is computed by CarbonFootprintCalculator. Only the choice of
 * mode and its explanation may come from the AI model, so a figure shown to the
 * distributor can never be invented.
 */
final readonly class TransportAdvice
{
    /**
     * @param  list<array{mode: TransportMode, kg: float}>  $comparison  Every mode, lowest footprint first
     */
    public function __construct(
        public TransportMode $mode,
        public string $reason,
        public bool $fromAi,
        public float $recommendedKg,
        public ?float $currentKg,
        public ?TransportMode $currentMode,
        public array $comparison,
    ) {}

    /**
     * Footprint avoided by switching from the current mode, negative when the
     * recommendation emits more. Null when no current mode was chosen yet.
     */
    public function savingKg(): ?float
    {
        if ($this->currentKg === null) {
            return null;
        }

        return round($this->currentKg - $this->recommendedKg, 2);
    }

    public function savingPercent(): ?float
    {
        $saving = $this->savingKg();

        if ($saving === null || $this->currentKg <= 0.0) {
            return null;
        }

        return round(($saving / $this->currentKg) * 100, 1);
    }

    /**
     * The sentence that compares the recommendation with the current choice.
     */
    public function savingSentence(): ?string
    {
        $saving = $this->savingKg();

        if ($saving === null || $this->currentMode === null) {
            return null;
        }

        if ($saving > 0.0) {
            return __('Switching saves about :kg kg CO₂e (:percent%) compared with :mode.', [
                'kg' => LocalizedNumber::format($saving, 2),
                'percent' => LocalizedNumber::format($this->savingPercent() ?? 0.0, 0),
                'mode' => $this->currentMode->label(),
            ]);
        }

        if ($saving < 0.0) {
            return __('This option emits about :kg kg CO₂e more than your current choice; it is suggested for operational reasons.', [
                'kg' => LocalizedNumber::format(abs($saving), 2),
            ]);
        }

        return __('Your current choice is already the best option for this load.');
    }

    /**
     * The JSON the shipment form reads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'mode_label' => $this->mode->label(),
            'reason' => $this->reason,
            'source' => $this->fromAi ? 'ai' : 'rule',
            'recommended_kg' => $this->recommendedKg,
            'recommended_text' => LocalizedNumber::format($this->recommendedKg, 2),
            'current_kg' => $this->currentKg,
            'saving_kg' => $this->savingKg(),
            'saving_text' => $this->savingSentence(),
            'comparison' => array_map(fn (array $row): array => [
                'mode' => $row['mode']->value,
                'label' => $row['mode']->label(),
                'kg_text' => LocalizedNumber::format($row['kg'], 2),
                'recommended' => $row['mode'] === $this->mode,
                'current' => $row['mode'] === $this->currentMode,
            ], $this->comparison),
        ];
    }
}

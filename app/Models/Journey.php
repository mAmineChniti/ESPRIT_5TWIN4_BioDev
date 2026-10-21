<?php

namespace App\Models;

use App\Services\DistanceCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Journey extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'qr_code',
        'environmental_score',
        'total_distance_km',
        'ai_summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'environmental_score' => 'decimal:2',
            'total_distance_km' => 'decimal:1',
            'generated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Food::class, 'product_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(JourneyStep::class)->orderBy('step_order');
    }

    /**
     * The first free position in this journey's workflow.
     *
     * Step orders are unique per journey, so prefilling this number is what
     * keeps the create form from failing validation on submit.
     */
    public function nextStepOrder(): int
    {
        return (int) ($this->steps->max('step_order') ?? 0) + 1;
    }

    /**
     * Recalculate route distance from consecutive step locations.
     */
    public function recalculateDistance(): int
    {
        $stepsWithLocations = $this->steps()->get()->filter(function (JourneyStep $step): bool {
            if (trim((string) $step->location) === '') {
                Log::warning('Journey step without a location was ignored during distance calculation.', [
                    'journey_id' => $this->id,
                    'journey_step_id' => $step->id,
                ]);

                return false;
            }

            return true;
        })->values();

        $distanceCalculator = app(DistanceCalculator::class);
        $distanceInKilometers = 0;

        for ($index = 1; $index < $stepsWithLocations->count(); $index++) {
            $previousStep = $stepsWithLocations[$index - 1];
            $currentStep = $stepsWithLocations[$index];

            $distanceInKilometers += $distanceCalculator->distanceBetween(
                $previousStep->location,
                $currentStep->location,
                $previousStep->country_code,
                $currentStep->country_code,
            );
        }

        $this->forceFill(['total_distance_km' => $distanceInKilometers])->save();

        return $distanceInKilometers;
    }
}

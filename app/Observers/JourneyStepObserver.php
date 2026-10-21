<?php

namespace App\Observers;

use App\Jobs\GenerateJourneySummary;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Services\Ai\AiClient;

class JourneyStepObserver
{
    /**
     * Recalculate the parent journey after a step is created or updated.
     */
    public function saved(JourneyStep $journeyStep): void
    {
        $this->recalculateJourneyDistance($journeyStep);
    }

    /**
     * Recalculate the parent journey after a step is deleted.
     */
    public function deleted(JourneyStep $journeyStep): void
    {
        $this->recalculateJourneyDistance($journeyStep);
    }

    /**
     * Recalculate and persist the distance for this step's journey.
     */
    private function recalculateJourneyDistance(JourneyStep $journeyStep): Journey
    {
        $journey = $journeyStep->journey()->firstOrFail();
        $journey->recalculateDistance();

        if (app(AiClient::class)->isConfigured()) {
            GenerateJourneySummary::dispatch($journey)
                ->delay(now()->addSeconds(5));
        }

        return $journey;
    }
}

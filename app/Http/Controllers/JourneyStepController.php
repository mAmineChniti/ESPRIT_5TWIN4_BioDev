<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJourneyStepRequest;
use App\Http\Requests\UpdateJourneyStepRequest;
use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JourneyStepController extends Controller
{
    public function index(Journey $journey): View
    {
        $journey->load(['product', 'steps']);

        return view('processor.journeys.steps.index', compact('journey'));
    }

    public function create(Journey $journey): View
    {
        return view('processor.journeys.steps.create', compact('journey'));
    }

    public function store(StoreJourneyStepRequest $request, Journey $journey): RedirectResponse
    {
        $journey->steps()->create($request->validated());

        return redirect()
            ->route('processor.journeys.steps.index', $journey)
            ->with('success', 'Step added successfully.');
    }

    public function show(Journey $journey, JourneyStep $step): View
    {
        $this->ensureBelongsToJourney($journey, $step);

        return view('processor.journeys.steps.show', compact('journey', 'step'));
    }

    public function edit(Journey $journey, JourneyStep $step): View
    {
        $this->ensureBelongsToJourney($journey, $step);

        return view('processor.journeys.steps.edit', compact('journey', 'step'));
    }

    public function update(
        UpdateJourneyStepRequest $request,
        Journey $journey,
        JourneyStep $step
    ): RedirectResponse {
        $this->ensureBelongsToJourney($journey, $step);
        $step->update($request->validated());

        return redirect()
            ->route('processor.journeys.steps.show', [$journey, $step])
            ->with('success', 'Step updated successfully.');
    }

    public function destroy(Journey $journey, JourneyStep $step): RedirectResponse
    {
        $this->ensureBelongsToJourney($journey, $step);
        $step->delete();

        return redirect()
            ->route('processor.journeys.steps.index', $journey)
            ->with('success', 'Step deleted successfully.');
    }

    private function ensureBelongsToJourney(Journey $journey, JourneyStep $step): void
    {
        abort_unless($step->journey_id === $journey->id, 404);
    }
}

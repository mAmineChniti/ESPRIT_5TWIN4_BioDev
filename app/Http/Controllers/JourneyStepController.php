<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJourneyStepRequest;
use App\Http\Requests\UpdateJourneyStepRequest;
use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JourneyStepController extends Controller
{
    public function index(Journey $journey): View
    {
        $journey->load(['product', 'steps']);

        return view('back.processor.journeys.steps.index', compact('journey'));
    }

    public function create(Request $request, Journey $journey): View
    {
        $journey->load(['product', 'steps']);

        // The form opens on the next free position with the next unrecorded
        // stage selected, so recording a journey is one continuous workflow
        // instead of inventing an order number. Explicit query values (from the
        // workflow's call to action) win; anything invalid falls back, and the
        // store validation still has the final word.
        $order = $request->query('step_order');
        $order = is_numeric($order) && (int) $order >= 1 ? (int) $order : $journey->nextStepOrder();

        $type = $request->query('type');
        $type = in_array($type, JourneyStep::FLOW, true)
            ? $type
            : JourneyStep::suggestedType($journey->steps->pluck('type'));

        $step = new JourneyStep([
            'step_order' => $order,
            'type' => $type,
            'step_date' => now()->toDateString(),
        ]);

        return view('back.processor.journeys.steps.create', compact('journey', 'step'));
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

        return view('back.processor.journeys.steps.show', compact('journey', 'step'));
    }

    public function edit(Journey $journey, JourneyStep $step): View
    {
        $this->ensureBelongsToJourney($journey, $step);

        return view('back.processor.journeys.steps.edit', compact('journey', 'step'));
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

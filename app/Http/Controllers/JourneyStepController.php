<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJourneyStepRequest;
use App\Http\Requests\UpdateJourneyStepRequest;
use App\Models\City;
use App\Models\Farm;
use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JourneyStepController extends Controller
{
    public function index(Request $request, Journey $journey): View
    {
        $this->ensureProcessor();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(JourneyStep::FLOW)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $journey->load(['product', 'steps']);
        $steps = $journey->steps()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('location', 'like', "%{$search}%"))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('step_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('step_date', '<=', $date))
            ->paginate(10)
            ->withQueryString();
        $hasFilters = collect($filters)->contains(fn (?string $value): bool => filled($value));

        return view('back.processor.journeys.steps.index', compact('journey', 'steps', 'filters', 'hasFilters'));
    }

    public function create(Request $request, Journey $journey): View
    {
        $this->ensureProcessor();
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

        $farms = Farm::approved()
            ->orderBy('name')
            ->get(['id', 'name', 'address']);
        $cities = City::query()->orderBy('name')->get(['name', 'country_code']);
        $countryCodes = City::query()->distinct()->orderBy('country_code')->pluck('country_code');

        return view('back.processor.journeys.steps.create', compact('journey', 'step', 'farms', 'cities', 'countryCodes'));
    }

    public function store(StoreJourneyStepRequest $request, Journey $journey): RedirectResponse
    {
        $this->ensureProcessor();

        $journey->steps()->create($request->validated());

        return redirect()
            ->route('processor.journeys.steps.index', $journey)
            ->with('success', __('Step added successfully.'));
    }

    public function show(Journey $journey, JourneyStep $step): View
    {
        $this->ensureProcessor();
        $this->ensureBelongsToJourney($journey, $step);

        return view('back.processor.journeys.steps.show', compact('journey', 'step'));
    }

    public function edit(Journey $journey, JourneyStep $step): View
    {
        $this->ensureProcessor();
        $this->ensureBelongsToJourney($journey, $step);

        $farms = Farm::approved()
            ->orderBy('name')
            ->get(['id', 'name', 'address']);
        $cities = City::query()->orderBy('name')->get(['name', 'country_code']);
        $countryCodes = City::query()->distinct()->orderBy('country_code')->pluck('country_code');

        return view('back.processor.journeys.steps.edit', compact('journey', 'step', 'farms', 'cities', 'countryCodes'));
    }

    public function update(
        UpdateJourneyStepRequest $request,
        Journey $journey,
        JourneyStep $step
    ): RedirectResponse {
        $this->ensureProcessor();
        $this->ensureBelongsToJourney($journey, $step);
        $step->update($request->validated());

        return redirect()
            ->route('processor.journeys.steps.show', [$journey, $step])
            ->with('success', __('Step updated successfully.'));
    }

    public function destroy(Journey $journey, JourneyStep $step): RedirectResponse
    {
        $this->ensureProcessor();
        $this->ensureBelongsToJourney($journey, $step);
        $step->delete();

        return redirect()
            ->route('processor.journeys.steps.index', $journey)
            ->with('success', __('Step deleted successfully.'));
    }

    private function ensureBelongsToJourney(Journey $journey, JourneyStep $step): void
    {
        abort_unless($step->journey_id === $journey->id, 404);
    }

    /**
     * Second layer over the route's processor gate (store/update repeat it
     * in their FormRequests), so an action moved out of the group still
     * fails closed.
     */
    private function ensureProcessor(): void
    {
        abort_unless(auth()->user()?->role === 'processor', 403, __('Only processor accounts can manage journey steps.'));
    }
}

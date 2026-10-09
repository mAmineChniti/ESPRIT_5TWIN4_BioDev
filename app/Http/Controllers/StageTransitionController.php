<?php

namespace App\Http\Controllers;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\StageTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StageTransitionController extends Controller
{
    /**
     * Show the supply chain recorded for a product.
     */
    public function index(Request $request, Food $food): View
    {
        // Every signed in role may read the chain, but say so through the policy
        // rather than by leaving the action ungated.
        $this->authorize('view', $food);

        $food->load('transitions.actor');

        return view('back.foods.transitions.index', [
            'food' => $food,
            'nextStage' => Stage::next($food->currentStage()),
        ]);
    }

    /**
     * Record the next step in a product's journey.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $validated = $request->validate([
            'to_stage' => ['required', Rule::enum(Stage::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $next = Stage::from($validated['to_stage']);

        // Only the role that performs a stage may sign for it, so the recorded
        // actor always matches the hand-off being claimed.
        $this->authorize('recordTransition', [$food, $next]);

        DB::transaction(function () use ($food, $next, $validated, $request): void {
            $lockedFood = Food::query()->whereKey($food->getKey())->lockForUpdate()->firstOrFail();
            $current = $lockedFood->currentStage();
            $expected = Stage::next($current);

            if ($expected === null) {
                throw ValidationException::withMessages([
                    'to_stage' => 'This product has already completed every supply chain stage.',
                ]);
            }

            if ($next !== $expected) {
                throw ValidationException::withMessages([
                    'to_stage' => "A product must move to {$expected->label()} next, not {$next->label()}.",
                ]);
            }

            StageTransition::create([
                'food_id' => $lockedFood->id,
                'actor_id' => $request->user()->id,
                'from_stage' => $current?->value,
                'to_stage' => $next->value,
                'notes' => $validated['notes'] ?? null,
                'occurred_at' => now(),
            ]);
        });

        return back()->with('success', "Step recorded: {$next->label()}.");
    }
}

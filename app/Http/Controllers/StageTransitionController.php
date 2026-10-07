<?php

namespace App\Http\Controllers;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\StageTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StageTransitionController extends Controller
{
    /**
     * Show the supply chain recorded for a product.
     */
    public function index(Food $food): View
    {
        $food->load('transitions.actor');

        return view('foods.transitions.index', [
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
        $current = $food->currentStage();
        $expected = Stage::next($current);

        // Only the role that performs a stage may sign for it, so the recorded
        // actor always matches the hand-off being claimed.
        $this->authorize('recordTransition', [$food, $next]);

        if ($expected === null) {
            return back()->withErrors([
                'to_stage' => 'This product has already completed every supply chain stage.',
            ]);
        }

        if ($next !== $expected) {
            return back()->withErrors([
                'to_stage' => "A product must move to {$expected->label()} next, not {$next->label()}.",
            ]);
        }

        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $request->user()->id,
            'from_stage' => $current?->value,
            'to_stage' => $next->value,
            'notes' => $validated['notes'] ?? null,
            'occurred_at' => now(),
        ]);

        return back()->with('success', "Step recorded: {$next->label()}.");
    }
}

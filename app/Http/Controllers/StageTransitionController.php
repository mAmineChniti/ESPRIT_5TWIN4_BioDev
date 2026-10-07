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
        $this->authorize('view', $food);

        $food->load('transitions.actor');

        return view('foods.transitions.index', ['food' => $food]);
    }

    /**
     * Record the next step in a product's journey.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);

        $food->load('transitions');

        $current = $food->currentStage();

        $validated = $request->validate([
            'to_stage' => ['required', Rule::enum(Stage::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $next = Stage::from($validated['to_stage']);

        if ($next === $current) {
            return back()->withErrors([
                'to_stage' => "This product is already marked as {$next->label()}.",
            ]);
        }

        if ($current !== null && $next->position() <= $current->position()) {
            return back()->withErrors([
                'to_stage' => "A product cannot move back from {$current->label()} to {$next->label()}.",
            ]);
        }

        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $request->user()->id,
            'from_stage' => $current,
            'to_stage' => $next,
            'notes' => $validated['notes'] ?? null,
            'occurred_at' => now(),
        ]);

        return back()->with('success', "Step recorded: {$next->label()}.");
    }
}

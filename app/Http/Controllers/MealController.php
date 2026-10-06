<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Meal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MealController extends Controller
{
    /**
     * Meals logged by the signed in consumer.
     */
    public function index(Request $request): View
    {
        $meals = $request->user()->meals()
            ->with('foods.category')
            ->latest('consumed_on')
            ->latest('id')
            ->get();

        return view('meals.index', compact('meals'));
    }

    public function create(): View
    {
        return view('meals.create', [
            'foods' => Food::with('category')->orderBy('name')->get(),
            'types' => Meal::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', Meal::TYPES)],
            'consumed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'foods' => ['required', 'array', 'min:1'],
            'foods.*' => ['integer', 'exists:foods,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        $meal = $request->user()->meals()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'consumed_on' => $validated['consumed_on'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $quantities = $validated['quantities'] ?? [];

        $meal->foods()->sync(
            collect($validated['foods'])
                ->mapWithKeys(fn (int $foodId): array => [
                    $foodId => ['quantity' => $quantities[$foodId] ?? 100],
                ])
                ->all()
        );

        return redirect()->route('meals.index')
            ->with('success', 'Meal logged successfully.');
    }

    public function show(Request $request, Meal $meal): View|RedirectResponse
    {
        if ($meal->user_id !== $request->user()->id) {
            abort(403);
        }

        $meal->load('foods.category');

        return view('meals.show', compact('meal'));
    }

    public function destroy(Request $request, Meal $meal): RedirectResponse
    {
        if ($meal->user_id !== $request->user()->id) {
            abort(403);
        }

        $meal->delete();

        return redirect()->route('meals.index')
            ->with('success', 'Meal deleted.');
    }
}

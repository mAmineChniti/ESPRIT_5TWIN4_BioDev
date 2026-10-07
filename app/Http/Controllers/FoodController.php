<?php

namespace App\Http\Controllers;

use App\Enums\Stage;
use App\Http\Requests\FoodRequest;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\StageTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource. Consumers see the whole catalog;
     * professionals see what they registered.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $this->authorize('viewAny', Food::class);

        $foods = Food::query()
            ->with(['category', 'producer'])
            ->when($user->isProfessional(), fn ($query) => $query->where('producer_id', $user->id))
            ->latest()
            ->get();

        return view('foods.index', compact('foods'));
    }

    public function create(): View
    {
        $this->authorize('create', Food::class);

        return view('foods.create', [
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
        ]);
    }

    public function store(FoodRequest $request): RedirectResponse
    {
        $this->authorize('create', Food::class);

        $user = $request->user();

        // The product, its certifications and its first supply chain step form
        // one unit: a partial failure must not leave a product with no chain.
        $food = DB::transaction(function () use ($request, $user): Food {
            $food = Food::create([
                ...$request->foodPayload(),
                'producer_id' => $user->id,
            ]);

            $this->syncCertifications($food, $request->certificationIds());

            StageTransition::create([
                'food_id' => $food->id,
                'actor_id' => $user->id,
                'from_stage' => null,
                'to_stage' => Stage::Produced,
                'notes' => 'Product registered',
                'occurred_at' => now(),
            ]);

            return $food;
        });

        return redirect()->route('foods.index')
            ->with('success', 'Product added successfully.');
    }

    public function show(Food $food): View
    {
        $this->authorize('view', $food);

        $food->load(['category', 'producer', 'transitions.actor', 'certifications']);

        return view('foods.show', compact('food'));
    }

    public function edit(Food $food): View
    {
        $this->authorize('update', $food);

        return view('foods.edit', [
            'food' => $food,
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
            'selectedCertifications' => $food->certifications->pluck('id')->all(),
        ]);
    }

    public function update(FoodRequest $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);

        DB::transaction(function () use ($request, $food): void {
            $food->update($request->foodPayload());

            // Always synced, never conditional on the key being present: this
            // is the only way to clear every certification from the product.
            $this->syncCertifications($food, $request->certificationIds());
        });

        return redirect()->route('foods.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->authorize('delete', $food);

        $food->delete();

        return redirect()->route('foods.index')
            ->with('success', 'Product deleted.');
    }

    /**
     * Replace the product's certifications outright.
     *
     * @param  list<int>  $certificationIds
     */
    private function syncCertifications(Food $food, array $certificationIds): void
    {
        $food->certifications()->sync(
            collect($certificationIds)
                ->unique()
                ->mapWithKeys(fn (int $id): array => [$id => ['obtained_on' => now()->toDateString()]])
                ->all()
        );
    }
}

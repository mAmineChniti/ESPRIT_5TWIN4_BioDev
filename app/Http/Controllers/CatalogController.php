<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use App\Models\Meal;
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Serve a role dashboard with data scoped to the signed in user.
     */
    public function __invoke(Request $request): View
    {
        return view('back.dashboard', self::forUser($request->user()));
    }

    /**
     * Catalog wide statistics shared by the landing page and every dashboard.
     *
     * @return array<string, mixed>
     */
    public static function catalogData(): array
    {
        return [
            'foodCount' => Food::count(),
            'mealCount' => Meal::count(),
            'categoryCount' => Category::count(),
            'certifiedCount' => Food::whereHas('certifications')->count(),
            'avgCalories' => (int) round(Food::avg('calories') ?? 0),
            'latestFoods' => Food::with(['category', 'producer', 'certifications'])->latest()->take(6)->get(),
            'topCategories' => Food::query()
                ->join('categories', 'foods.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category, COUNT(*) as total')
                ->groupBy('categories.name', 'categories.id')
                ->orderByDesc('total')
                ->take(4)
                ->get(),
            'scoreDistribution' => Food::query()
                ->whereNotNull('environmental_score')
                ->selectRaw('environmental_score as score, COUNT(*) as total')
                ->groupBy('environmental_score')
                ->orderBy('environmental_score')
                ->pluck('total', 'score'),
        ];
    }

    /**
     * Statistics that depend on who is asking. This is what makes each role
     * dashboard show something different.
     *
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        return [
            ...self::catalogData(),
            'myFoods' => $user->isProfessional() && ! $user->isAdmin()
                ? Food::where('producer_id', $user->id)->with('category')->latest()->take(6)->get()
                : new Collection,
            'myFoodCount' => $user->isProfessional() && ! $user->isAdmin()
                ? Food::where('producer_id', $user->id)->count()
                : 0,
            'myMeals' => $user->role === 'consumer'
                ? $user->meals()->with('foods')->latest('consumed_on')->take(5)->get()
                : new Collection,
            'pendingStage' => self::pendingByStage(),
            'recentTransitions' => StageTransition::with(['food', 'actor'])
                ->latest('occurred_at')
                ->take(6)
                ->get(),
        ];
    }

    /**
     * How many products currently sit at each stage of the chain.
     *
     * @return Collection<string, int>
     */
    private static function pendingByStage(): Collection
    {
        $latest = StageTransition::query()
            ->selectRaw('food_id, MAX(id) as last_id')
            ->groupBy('food_id');

        return StageTransition::query()
            ->joinSub($latest, 'latest', 'latest.last_id', '=', 'stage_transitions.id')
            ->selectRaw('stage_transitions.to_stage, COUNT(*) as total')
            ->groupBy('stage_transitions.to_stage')
            ->pluck('total', 'to_stage');
    }
}

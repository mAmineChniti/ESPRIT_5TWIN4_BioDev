<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentalScore;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Meal;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ConsumerDashboardController extends Controller
{
    /**
     * The consumer's own space: what they ate, what they rated, and what they
     * flagged. Everything here is scoped to the signed in user.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $meals = $user->meals()
            ->with('foods')
            ->latest('consumed_on')
            ->latest('id')
            ->take(30)
            ->get();

        // Energy per day, for the line chart.
        $energy = $meals
            ->filter(fn (Meal $meal): bool => $meal->consumed_on !== null)
            ->groupBy(fn (Meal $meal): string => $meal->consumed_on->toDateString())
            ->map(fn ($group) => [
                'date' => $group->first()->consumed_on->format('j M'),
                'calories' => (int) round($group->sum(fn (Meal $meal): float => $meal->totalCalories())),
            ])
            ->sortKeysDesc()
            ->reverse()
            ->values();

        // Grade mix of what this consumer actually ate.
        $eatenFoodIds = Meal::where('user_id', $user->id)
            ->join('meal_food', 'meal_food.meal_id', '=', 'meals.id')
            ->pluck('meal_food.food_id')
            ->unique();

        $grades = Food::whereIn('id', $eatenFoodIds)
            ->whereNotNull('environmental_score')
            ->selectRaw('environmental_score as grade, COUNT(*) as total')
            ->groupBy('environmental_score')
            ->orderBy('environmental_score')
            ->get()
            ->map(fn ($row): array => [
                'grade' => $row->grade,
                'total' => (int) $row->total,
            ]);

        $certifiedEaten = Food::whereIn('id', $eatenFoodIds)
            ->whereHas('certifications')
            ->count();

        $myReviews = Review::where('user_id', $user->id)
            ->with('food')
            ->latest()
            ->take(5)
            ->get();

        $myReports = GreenwashingReport::where('user_id', $user->id)
            ->with('food')
            ->latest()
            ->take(5)
            ->get();

        return view('back.consumer-dashboard', [
            'meals' => $meals,
            'myReviews' => $myReviews,
            'myReports' => $myReports,
            'stats' => [
                'mealsLogged' => $user->meals()->count(),
                'reviewsWritten' => Review::where('user_id', $user->id)->count(),
                'reportsFiled' => GreenwashingReport::where('user_id', $user->id)->count(),
                'reportsUpheld' => GreenwashingReport::where('user_id', $user->id)
                    ->where('status', ReportStatus::Upheld->value)
                    ->count(),
            ],
            'energy' => $energy,
            'grades' => $grades,
            'certifiedShare' => $eatenFoodIds->isEmpty()
                ? 0
                : (int) round($certifiedEaten / $eatenFoodIds->count() * 100),
            'totalProducts' => Food::count(),
            'gradeLegend' => EnvironmentalScore::cases(),
        ]);
    }
}

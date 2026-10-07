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
     * How far back the energy trend reaches. A window rather than a row limit,
     * so the chart cannot silently cover less than the meal count implies.
     */
    private const ENERGY_CHART_DAYS = 30;

    /**
     * The consumer's own space: what they ate, what they rated, and what they
     * flagged. Everything here is scoped to the signed in user.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // The chart covers a rolling window rather than the last N rows: taking
        // a row limit made the trend silently disagree with the meal count.
        $chartFrom = now()->subDays(self::ENERGY_CHART_DAYS)->startOfDay();

        $meals = $user->meals()
            ->with('foods')
            ->where('consumed_on', '>=', $chartFrom->toDateString())
            ->latest('consumed_on')
            ->latest('id')
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
                'chartWindowDays' => self::ENERGY_CHART_DAYS,
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

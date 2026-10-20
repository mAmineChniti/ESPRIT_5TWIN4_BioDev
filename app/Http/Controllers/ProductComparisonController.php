<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Product Benchmark and Comparison Controller.
 *
 * Allows consumers and producers to compare up to 3 products side-by-side:
 * nutritional values, Nutri-Score, Eco-Score, certifications, and supply chain transparency.
 */
class ProductComparisonController extends Controller
{
    /**
     * Display side-by-side comparison matrix.
     */
    public function index(Request $request): View
    {
        $rawIds = (array) $request->input('ids', []);
        $selectedIds = array_slice(array_unique(array_filter(array_map('intval', $rawIds))), 0, 3);

        $comparedFoods = Food::query()
            ->with(['category', 'producer', 'certifications', 'transitions'])
            ->whereIn('id', $selectedIds)
            ->get();

        // Calculate benchmark highlights if at least 2 products are compared
        $highlights = [];
        if ($comparedFoods->count() >= 2) {
            $highestProtein = $comparedFoods->sortByDesc('protein')->first();
            $lowestCalories = $comparedFoods->sortBy('calories')->first();
            $highestTransparency = $comparedFoods->sortByDesc(fn (Food $f) => $f->transparencyScore())->first();

            $highlights = [
                'highest_protein_id' => $highestProtein?->id,
                'lowest_calories_id' => $lowestCalories?->id,
                'highest_transparency_id' => $highestTransparency?->id,
            ];
        }

        return view('front.products.compare', [
            'comparedFoods' => $comparedFoods,
            'selectedIds' => $selectedIds,
            'allFoods' => Food::orderBy('name')->take(100)->get(['id', 'name', 'origin', 'environmental_score']),
            'categories' => Category::orderBy('name')->get(),
            'highlights' => $highlights,
        ]);
    }
}

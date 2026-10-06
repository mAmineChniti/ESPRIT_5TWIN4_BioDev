<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentalScore;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Models\Category;
use App\Models\Food;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsumerSearchController extends Controller
{
    /**
     * Public search. This is the page a consumer lands on after scanning a code.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'grade' => ['nullable', Rule::enum(EnvironmentalScore::class)],
            'certified' => ['nullable', 'boolean'],
        ]);

        $query = Food::query()
            ->with(['category', 'certifications', 'producer'])
            ->withCount([
                'reviews',
                'reports as upheld_reports_count' => fn (Builder $reports) => $reports->where('status', ReportStatus::Upheld->value),
            ])
            ->when($validated['q'] ?? null, function (Builder $search, string $term): void {
                $search->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('origin', 'like', "%{$term}%")
                        ->orWhereHas('producer', fn (Builder $producer) => $producer->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('certifications', fn (Builder $certification) => $certification->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($validated['category'] ?? null, fn (Builder $query, $id) => $query->where('category_id', $id))
            ->when($validated['grade'] ?? null, fn (Builder $query, EnvironmentalScore $grade) => $query->where('environmental_score', $grade->value))
            ->when($request->boolean('certified'), fn (Builder $query) => $query->whereHas('certifications'));

        match ($request->query('sort', 'recent')) {
            'scanned' => $query->orderByDesc('scans_count'),
            'grade' => $query->orderBy('environmental_score'),
            'rated' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $foods = $query->paginate(12)->withQueryString();

        return view('front.products.index', [
            'foods' => $foods,
            'categories' => Category::orderBy('name')->get(),
            'grades' => EnvironmentalScore::cases(),
            'filters' => $validated,
            'sort' => $request->query('sort', 'recent'),
        ]);
    }

    /**
     * Resolve a scanned code or search term to a product.
     */
    public function scan(Request $request): View|RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
        ]);

        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return redirect()->route('products.index');
        }

        // A scan may be a numeric id, or a free text term.
        $food = Food::query()
            ->with(['category', 'certifications', 'producer', 'transitions.actor'])
            ->when(
                ctype_digit($code),
                fn (Builder $query) => $query->whereKey((int) $code),
                fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$code}%")
                    ->orWhere('origin', 'like', "%{$code}%"))
            )
            ->first();

        if ($food === null) {
            return redirect()
                ->route('products.index', ['q' => $code])
                ->withErrors(['code' => 'No product matched that code. Showing search results instead.']);
        }

        $food->increment('scans_count');

        return redirect()->route('products.show', $food);
    }

    /**
     * The public traceability page for a single product.
     */
    public function show(Request $request, Food $food): View
    {
        $food->load([
            'category',
            'producer',
            'certifications',
            'transitions.actor',
            'reviews.user',
            'reports' => fn (Relation $reports) => $reports->latest()->limit(5),
        ]);

        $steps = $food->transitions;
        $timeline = [];

        foreach ($steps as $index => $transition) {
            $next = $steps->get($index + 1);

            $timeline[] = [
                'stage' => $transition->to_stage?->label() ?? 'Unknown',
                'actor' => $transition->actor?->name,
                'role' => $transition->actor?->role,
                'date' => $transition->occurred_at->toDateString(),
                'notes' => $transition->notes,
                'daysInStage' => $next
                    ? max(1, (int) $transition->occurred_at->diffInDays($next->occurred_at))
                    : 0,
            ];
        }

        return view('front.products.show', [
            'food' => $food,
            'timeline' => $timeline,
            'verdict' => $food->trustVerdict(),
            'transparency' => $food->transparencyScore(),
            'averageRating' => $food->averageRating(),
            'reasons' => ReportReason::cases(),
            'canReview' => $request->user()?->role === 'consumer',
            'myReview' => $request->user()
                ? Review::where('food_id', $food->id)->where('user_id', $request->user()->id)->first()
                : null,
            'completedStages' => $steps->pluck('to_stage')->filter()->pluck('value')->all(),
            'totalStages' => count(Stage::order()),
        ]);
    }
}

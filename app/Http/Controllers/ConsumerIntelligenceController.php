<?php

namespace App\Http\Controllers;

use App\Enums\FindingCategory;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Services\Assistant\ProductAssistant;
use App\Services\Greenwashing\GreenwashingDetector;
use App\Services\Recommendations\ProductRecommender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The consumer-facing AI features: greenwashing detection, the product
 * assistant, recommendations, and escalating a detected finding as a report.
 */
class ConsumerIntelligenceController extends Controller
{
    /**
     * How many of the consumer's own products the hub audits at once.
     *
     * Every one of these is a paid model call, and hosted tiers rate-limit
     * aggressively, so the hub audits a shortlist rather than the whole
     * shopping history. The product pages are where anything else gets checked.
     */
    private const WATCH_LIST_LIMIT = 3;

    public function __construct(
        private readonly GreenwashingDetector $detector,
        private readonly ProductAssistant $assistant,
        private readonly ProductRecommender $recommender,
    ) {}

    /**
     * The consumer's own hub, tying the four features together.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $recommendations = $this->recommender->recommendedFor($user);

        // Analyse the products this consumer actually eats, so the hub surfaces
        // problems in their own basket rather than an arbitrary sample.
        $eatenIds = $user->meals()
            ->join('meal_food', 'meal_food.meal_id', '=', 'meals.id')
            ->distinct()
            ->pluck('meal_food.food_id');

        $watchList = Food::with(['category', 'producer', 'certifications', 'transitions', 'reports'])
            ->whereIn('id', $eatenIds)
            ->latest('id')
            ->take(self::WATCH_LIST_LIMIT)
            ->get()
            ->map(fn (Food $food) => [
                'food' => $food,
                'report' => $this->detector->analyze($food),
            ]);

        return view('front.consumer.space', [
            'recommendations' => $recommendations,
            'watchList' => $watchList,
            'reportsFiled' => $user->reports()->count(),
            'mealsLogged' => $user->meals()->count(),
            'assistantEnabled' => $this->assistantEnabled(),
        ]);
    }

    /**
     * The AI analysis for one product, as JSON for the product page.
     */
    public function analyze(Request $request, Food $food): JsonResponse
    {
        $report = $this->detector->analyze($food);

        return response()->json([
            'product' => ['id' => $food->id, 'name' => $food->name],
            'failed' => $report->hasFailed(),
            'failure' => $report->failure,
            'verdict' => $report->verdict,
            'summary' => $report->summary,
            'risk_score' => $report->riskScore,
            'findings' => array_map(
                static fn ($finding): array => $finding->toArray() + [
                    'severity_label' => $finding->severity->label(),
                    'report_reason' => $finding->category->reason()->value,
                    'report_label' => $finding->category->reportLabel(),
                ],
                $report->findings
            ),
        ]);
    }

    /**
     * Ask the assistant about a product.
     */
    public function ask(Request $request, Food $food): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $answer = $this->assistant->ask($food, $validated['question']);

        return response()->json($answer->toArray() + [
            'suggestions' => $this->assistant->suggestions($food),
        ]);
    }

    /**
     * Suggested questions for a product.
     */
    public function suggestions(Food $food): JsonResponse
    {
        return response()->json([
            'suggestions' => $this->assistant->suggestions($food),
            'enabled' => $this->assistantEnabled(),
        ]);
    }

    /**
     * Responsible alternatives to a product.
     */
    public function alternatives(Food $food): JsonResponse
    {
        return response()->json([
            'compared_to' => ['id' => $food->id, 'name' => $food->name],
            'alternatives' => $this->recommender
                ->alternativesTo($food)
                ->map(static fn ($recommendation): array => $recommendation->toArray())
                ->all(),
        ]);
    }

    /**
     * File a report seeded from a detected finding, so a consumer does not have
     * to work out which category their concern falls into.
     */
    public function reportFinding(Request $request, Food $food): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->role === 'consumer', 403, 'Only consumers can report greenwashing claims.');

        $validated = $request->validate([
            'category' => ['required', Rule::enum(FindingCategory::class)],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var FindingCategory $category */
        $category = FindingCategory::from($validated['category']);
        $reason = $category->reason();

        $details = trim(sprintf(
            "%s\n\nReported from the NutriTrace detector. Category: %s%s",
            $validated['title'] ?? $reason->label(),
            $category->value,
            $request->filled('detail') ? "\n".$request->string('detail')->toString() : ''
        ));

        $alreadyPending = $food->reports()
            ->where('user_id', $user->id)
            ->where('status', ReportStatus::Pending->value)
            ->exists();

        if ($alreadyPending) {
            return back()->withErrors([
                'reason' => 'You already have a report awaiting review on this product.',
            ]);
        }

        $food->reports()->create([
            'user_id' => $user->id,
            'reason' => $reason,
            'details' => mb_substr($details, 0, 2000),
            'status' => ReportStatus::Pending,
        ]);

        return back()->with('success', 'Report received. A reviewer will look at it.');
    }

    /**
     * Recommendations for the signed in consumer.
     */
    public function recommendations(Request $request): View
    {
        return view('front.consumer.recommendations', [
            'recommendations' => $this->recommender->recommendedFor($request->user()),
        ]);
    }

    private function assistantEnabled(): bool
    {
        return filled(config('services.ai.key'));
    }
}

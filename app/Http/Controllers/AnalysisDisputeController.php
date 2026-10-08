<?php

namespace App\Http\Controllers;

use App\Enums\AnalysisDisputeStatus;
use App\Http\Requests\StoreAnalysisDisputeRequest;
use App\Http\Requests\UpdateAnalysisDisputeRequest;
use App\Models\AnalysisDispute;
use App\Models\Food;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalysisDisputeController extends Controller
{
    /**
     * How many disputes one admin queue page shows.
     */
    private const PER_PAGE = 15;

    /**
     * Anyone signed in can say the detector got a product wrong.
     *
     * This is feedback about NutriTrace's own analysis, not a claim against the
     * product, so it is not restricted to consumers: a producer disputing a
     * verdict about their own product is the clearest case of all.
     */
    public function store(StoreAnalysisDisputeRequest $request, Food $food): RedirectResponse
    {
        $user = $request->user();

        // One open dispute per account per product, for the same reason the
        // greenwashing queue allows one: a queue admin reads has to be a queue.
        // The food row is locked so two concurrent submissions cannot both pass.
        DB::transaction(function () use ($request, $user, $food): void {
            Food::query()->whereKey($food->getKey())->lockForUpdate()->first();

            $alreadyPending = AnalysisDispute::query()
                ->where('food_id', $food->id)
                ->where('user_id', $user->id)
                ->pending()
                ->exists();

            if ($alreadyPending) {
                throw ValidationException::withMessages([
                    'reason' => 'You already have an open report about this analysis.',
                ]);
            }

            AnalysisDispute::create($request->disputePayload());
        });

        return back()->with('success', 'Thank you. An administrator will review the analysis.');
    }

    /**
     * The admin moderation queue: what users believe the detector got wrong.
     */
    public function index(Request $request): View
    {
        $statusInput = $request->query('status');
        $status = is_string($statusInput) && AnalysisDisputeStatus::tryFrom($statusInput) !== null
            ? $statusInput
            : null;

        $disputes = AnalysisDispute::query()
            ->with(['food', 'user', 'reviewer'])
            ->when($status, fn ($query) => $query->where('status', $status))
            // Oldest first inside a status: a pending dispute has been waiting
            // the longest, and that is what needs deciding.
            ->oldest('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('back.analysis-disputes.index', [
            'disputes' => $disputes,
            'statuses' => AnalysisDisputeStatus::cases(),
            'status' => $status,
            // Counted in SQL rather than from the page, so the tab totals stay
            // correct on every page of the queue.
            'counts' => AnalysisDispute::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'pendingCount' => AnalysisDispute::query()->pending()->count(),
        ]);
    }

    /**
     * An admin records a decision.
     *
     * Upholding says NutriTrace's analysis is wrong; dismissing says it stands.
     * Neither changes the product's trust score — that would punish the product
     * for a mistake NutriTrace made.
     */
    public function update(UpdateAnalysisDisputeRequest $request, AnalysisDispute $analysisDispute): RedirectResponse
    {
        $dispute = $analysisDispute;

        abort_if(
            ! $dispute->isPending(),
            422,
            'This report has already been reviewed. Refresh the queue to see the decision.'
        );

        $validated = $request->validated();

        $dispute->update([
            'status' => AnalysisDisputeStatus::from($validated['status']),
            'resolution_note' => $validated['resolution_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Decision recorded and the reporter can now see it.');
    }
}

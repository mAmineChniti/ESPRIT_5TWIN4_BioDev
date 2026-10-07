<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Models\GreenwashingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GreenwashingReportController extends Controller
{
    /**
     * List reports for the admin's moderation queue.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only admins can view reports.');

        $reports = GreenwashingReport::with(['food', 'user'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('back.reports', compact('reports'));
    }

    /**
     * A consumer flags a claim they believe is misleading.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->role === 'consumer', 403, 'Only consumers can report greenwashing claims.');

        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $alreadyPending = GreenwashingReport::where('food_id', $food->id)
            ->where('user_id', $user->id)
            ->where('status', ReportStatus::Pending->value)
            ->exists();

        if ($alreadyPending) {
            return back()->withErrors([
                'reason' => 'You already have a report awaiting review on this product.',
            ]);
        }

        GreenwashingReport::create([
            'food_id' => $food->id,
            'user_id' => $user->id,
            'reason' => ReportReason::from($validated['reason']),
            'details' => $validated['details'] ?? null,
            'status' => ReportStatus::Pending,
        ]);

        return back()->with('success', 'Report received. A reviewer will look at it.');
    }

    /**
     * An admin records a decision on a report. Upholding one lowers the
     * product's transparency score, which is what makes the signal meaningful.
     */
    public function update(Request $request, GreenwashingReport $report): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin(), 403, 'Only admins can review reports.');

        if ($report->status !== ReportStatus::Pending) {
            return back()->withErrors([
                'status' => "This report was already reviewed as \"{$report->status->label()}\".",
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class), Rule::notIn([ReportStatus::Pending->value])],
        ]);

        $status = ReportStatus::from($validated['status']);

        $report->update([
            'status' => $status,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Report marked as {$status->label()}.");
    }
}

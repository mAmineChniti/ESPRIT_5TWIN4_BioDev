<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Models\GreenwashingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GreenwashingReportController extends Controller
{
    /**
     * A consumer flags a claim they believe is misleading.
     *
     * Only a consumer reaches this action; the route carries the role gate.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        // One open report per consumer per product. The product row is locked
        // for the duration so two concurrent submissions cannot both pass the
        // check and open a duplicate.
        DB::transaction(function () use ($request, $food, $validated): void {
            Food::query()->whereKey($food->getKey())->lockForUpdate()->first();

            $alreadyPending = GreenwashingReport::query()
                ->where('food_id', $food->id)
                ->where('user_id', $request->user()->id)
                ->where('status', ReportStatus::Pending)
                ->exists();

            if ($alreadyPending) {
                throw ValidationException::withMessages([
                    'reason' => 'You already have a report awaiting review on this product.',
                ]);
            }

            GreenwashingReport::create([
                'food_id' => $food->id,
                'user_id' => $request->user()->id,
                'reason' => ReportReason::from($validated['reason']),
                'details' => $validated['details'] ?? null,
                'status' => ReportStatus::Pending,
            ]);
        });

        return back()->with('success', 'Report received. A reviewer will look at it.');
    }

    /**
     * An admin records a decision on a report. Upholding one lowers the
     * product's transparency score, which is what makes the signal meaningful.
     *
     * @see Food::upheldReportCount() the score this decision feeds
     */
    public function update(Request $request, GreenwashingReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
        ]);

        $status = ReportStatus::from($validated['status']);

        $report->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Report marked as {$status->label()}.");
    }
}

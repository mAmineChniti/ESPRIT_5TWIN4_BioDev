<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Journeys are a shared processor pool: every processor may read and extend
 * any journey, because traceability recording is the processors' joint work
 * rather than per-account property (journeys carry no user_id; they belong to
 * products via product_id). Per-step binding is still enforced in
 * JourneyStepController::ensureBelongsToJourney, so a step URL cannot be
 * rewired to another journey.
 */
class JourneyController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureProcessor();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(JourneyStep::FLOW)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $stepFilters = static function (Builder $query) use ($filters): void {
            $query
                ->when($filters['type'] ?? null, fn (Builder $steps, string $type) => $steps->where('type', $type))
                ->when($filters['date_from'] ?? null, fn (Builder $steps, string $date) => $steps->whereDate('step_date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn (Builder $steps, string $date) => $steps->whereDate('step_date', '<=', $date));
        };

        $hasStepFilters = filled($filters['type'] ?? null)
            || filled($filters['date_from'] ?? null)
            || filled($filters['date_to'] ?? null);

        $journeys = Journey::query()
            ->with('product')
            ->withCount('steps')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($stepFilters): void {
                $query->where(function (Builder $matches) use ($search, $stepFilters): void {
                    $matches
                        ->whereHas('product', fn (Builder $product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('steps', function (Builder $steps) use ($search, $stepFilters): void {
                            $steps->where('location', 'like', "%{$search}%");
                            $stepFilters($steps);
                        });
                });
            })
            ->when($hasStepFilters, fn (Builder $query) => $query->whereHas('steps', $stepFilters))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $hasFilters = collect($filters)->contains(fn (?string $value): bool => filled($value));

        return view('back.processor.journeys.index', compact('journeys', 'filters', 'hasFilters'));
    }

    public function show(Journey $journey): View
    {
        $this->ensureProcessor();

        $journey->load(['product', 'steps']);

        return view('back.processor.journeys.show', compact('journey'));
    }

    public function qrCode(Journey $journey): View
    {
        $this->ensureProcessor();

        abort_unless($journey->qr_code, 404, __('This journey does not have a QR code yet.'));

        $publicUrl = route('journeys.public', $journey->qr_code);
        // The quiet zone lives in the image itself: the generator paints a
        // white background with a 4-module margin, so the code stays
        // scannable in either theme and the panel can use theme tokens.
        $qrSvg = QrCode::format('svg')
            ->size(320)
            ->margin(4)
            ->generate($publicUrl);

        return view('back.processor.journeys.qr', compact('journey', 'publicUrl', 'qrSvg'));
    }

    /**
     * Second layer over the route's processor gate, so an action moved out
     * of the group still fails closed. Journeys are a shared pool, so the
     * check is the role, not ownership.
     */
    private function ensureProcessor(): void
    {
        abort_unless(auth()->user()?->role === 'processor', 403, __('Only processor accounts can manage traceability journeys.'));
    }
}

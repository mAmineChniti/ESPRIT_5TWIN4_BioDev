<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use Illuminate\Http\Request;
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
        $journeys = Journey::query()
            ->with('product')
            ->withCount('steps')
            ->latest()
            ->paginate(10);

        return view('back.processor.journeys.index', compact('journeys'));
    }

    public function show(Journey $journey): View
    {
        $journey->load(['product', 'steps']);

        return view('back.processor.journeys.show', compact('journey'));
    }

    public function qrCode(Journey $journey): View
    {
        abort_unless($journey->qr_code, 404, 'This journey does not have a QR code yet.');

        $publicUrl = route('journeys.public', $journey->qr_code);
        $qrSvg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->generate($publicUrl);

        return view('back.processor.journeys.qr', compact('journey', 'publicUrl', 'qrSvg'));
    }
}

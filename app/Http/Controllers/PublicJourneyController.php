<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use Illuminate\View\View;

class PublicJourneyController extends Controller
{
    public function show(string $code): View
    {
        $journey = Journey::query()
            ->where('qr_code', $code)
            ->with(['product', 'steps'])
            ->firstOrFail();

        return view('journeys.public', compact('journey'));
    }
}

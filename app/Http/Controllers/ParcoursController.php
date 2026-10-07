<?php

namespace App\Http\Controllers;

use App\Models\Parcours;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ParcoursController extends Controller
{
    public function index(Request $request): View
    {
        $parcours = Parcours::query()
            ->with('produit')
            ->withCount('etapes')
            ->latest()
            ->paginate(10);

        return view('parcours.index', compact('parcours'));
    }

    public function show(Parcours $parcours): View
    {
        $parcours->load(['produit', 'etapes']);

        return view('processor.parcours.show', compact('parcours'));
    }

    public function qrCode(Parcours $parcours): View
    {
        abort_unless($parcours->code_qr, 404, 'Ce parcours ne possède pas encore de code QR.');

        $publicUrl = route('parcours.public', $parcours->code_qr);
        $qrSvg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->generate($publicUrl);

        return view('processor.parcours.qr', compact('parcours', 'publicUrl', 'qrSvg'));
    }
}

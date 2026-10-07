<?php

namespace App\Http\Controllers;

use App\Models\Parcours;
use Illuminate\View\View;

class PublicParcoursController extends Controller
{
    public function show(string $code): View
    {
        $parcours = Parcours::query()
            ->where('code_qr', $code)
            ->with(['produit', 'etapes'])
            ->firstOrFail();

        return view('parcours.public', compact('parcours'));
    }
}

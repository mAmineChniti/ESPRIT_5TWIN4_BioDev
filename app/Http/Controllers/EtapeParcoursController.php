<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEtapeRequest;
use App\Http\Requests\UpdateEtapeRequest;
use App\Models\EtapeParcours;
use App\Models\Parcours;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EtapeParcoursController extends Controller
{
    public function index(Parcours $parcours): View
    {
        $parcours->load(['produit', 'etapes']);

        return view('processor.etapes.index', compact('parcours'));
    }

    public function create(Parcours $parcours): View
    {
        return view('processor.etapes.create', compact('parcours'));
    }

    public function store(StoreEtapeRequest $request, Parcours $parcours): RedirectResponse
    {
        $parcours->etapes()->create($request->validated());

        return redirect()
            ->route('processor.parcours.etapes.index', $parcours)
            ->with('success', 'Étape ajoutée avec succès.');
    }

    public function show(Parcours $parcours, EtapeParcours $etape): View
    {
        $this->ensureBelongsToParcours($parcours, $etape);

        return view('processor.etapes.show', compact('parcours', 'etape'));
    }

    public function edit(Parcours $parcours, EtapeParcours $etape): View
    {
        $this->ensureBelongsToParcours($parcours, $etape);

        return view('processor.etapes.edit', compact('parcours', 'etape'));
    }

    public function update(
        UpdateEtapeRequest $request,
        Parcours $parcours,
        EtapeParcours $etape
    ): RedirectResponse {
        $this->ensureBelongsToParcours($parcours, $etape);
        $etape->update($request->validated());

        return redirect()
            ->route('processor.parcours.etapes.show', [$parcours, $etape])
            ->with('success', 'Étape modifiée avec succès.');
    }

    public function destroy(Parcours $parcours, EtapeParcours $etape): RedirectResponse
    {
        $this->ensureBelongsToParcours($parcours, $etape);
        $etape->delete();

        return redirect()
            ->route('processor.parcours.etapes.index', $parcours)
            ->with('success', 'Étape supprimée avec succès.');
    }

    private function ensureBelongsToParcours(Parcours $parcours, EtapeParcours $etape): void
    {
        abort_unless($etape->parcours_id === $parcours->id, 404);
    }
}

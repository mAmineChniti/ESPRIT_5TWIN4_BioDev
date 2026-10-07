<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgriculturalRegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Both Admin and Producer can see all created regions to allow selection
        $regions = AgriculturalRegion::withCount('farms')->latest()->paginate(10);

        return view('back.regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Seul l\'Administrateur peut créer une région agricole.');
        }

        return view('back.regions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Seul l\'Administrateur peut créer une région agricole.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:agricultural_regions,code',
            'climate' => 'nullable|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        AgriculturalRegion::create($validated);

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AgriculturalRegion $region)
    {
        $user = Auth::user();

        $region->load(['farms' => function ($q) use ($user) {
            if (! $user?->isAdmin()) {
                $q->where('user_id', $user->id);
            }
        }]);

        return view('back.regions.show', compact('region'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgriculturalRegion $region)
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Seul l\'Administrateur peut modifier une région agricole.');
        }

        return view('back.regions.edit', compact('region'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AgriculturalRegion $region)
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Seul l\'Administrateur peut modifier une région agricole.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:agricultural_regions,code,'.$region->id,
            'climate' => 'nullable|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $region->update($validated);

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgriculturalRegion $region)
    {
        // Only Admin can delete a global agricultural region
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Seul un Administrateur peut supprimer une région agricole.');
        }

        $region->delete();

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole supprimée avec succès.');
    }
}

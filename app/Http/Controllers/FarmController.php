<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusFilter = $request->query('status');

        if ($user?->isAdmin()) {
            // Admin sees ALL farms, with optional status filtering
            $query = Farm::with(['region', 'user'])->latest();
            if ($statusFilter && in_array($statusFilter, ['en_attente', 'validee', 'refusee'], true)) {
                $query->where('status', $statusFilter);
            }
            $farms = $query->paginate(10)->withQueryString();

            // Statistics for admin
            $stats = [
                'total'     => Farm::count(),
                'validees'  => Farm::where('status', 'validee')->count(),
                'en_attente'=> Farm::where('status', 'en_attente')->count(),
                'refusees'  => Farm::where('status', 'refusee')->count(),
                'surface'   => Farm::where('status', 'validee')->sum('surface_hectares'),
            ];
        } else {
            // Producer sees ONLY their own farms
            $query = Farm::where('user_id', $user->id)->with('region')->latest();
            if ($statusFilter && in_array($statusFilter, ['en_attente', 'validee', 'refusee'], true)) {
                $query->where('status', $statusFilter);
            }
            $farms = $query->paginate(10)->withQueryString();

            // Statistics for producer
            $stats = [
                'total'     => Farm::where('user_id', $user->id)->count(),
                'validees'  => Farm::where('user_id', $user->id)->where('status', 'validee')->count(),
                'en_attente'=> Farm::where('user_id', $user->id)->where('status', 'en_attente')->count(),
                'refusees'  => Farm::where('user_id', $user->id)->where('status', 'refusee')->count(),
                'surface'   => Farm::where('user_id', $user->id)->where('status', 'validee')->sum('surface_hectares'),
            ];
        }

        $pendingCount = $user?->isAdmin() ? Farm::where('status', 'en_attente')->count() : 0;

        return view('back.farms.index', compact('farms', 'statusFilter', 'pendingCount', 'stats'));
    }

    /**
     * Display pending farm validation requests for Admin.
     */
    public function requests()
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            abort(403, 'Accès réservé à l\'Administrateur.');
        }

        $requests = Farm::where('status', 'en_attente')
            ->with(['region', 'user'])
            ->latest()
            ->paginate(10);

        return view('back.farms.requests', compact('requests'));
    }

    /**
     * Approve a farm validation request (Admin only).
     */
    public function approve(Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            abort(403, 'Seul un Administrateur peut valider une ferme.');
        }

        $farm->update(['status' => 'validee']);

        return back()->with('success', "La ferme « {$farm->name} » a été acceptée et validée.");
    }

    /**
     * Reject a farm validation request (Admin only).
     */
    public function reject(Request $request, Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            abort(403, 'Seul un Administrateur peut refuser une ferme.');
        }

        $farm->update([
            'status' => 'refusee',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return back()->with('success', "La ferme « {$farm->name} » a été refusée.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $regions = AgriculturalRegion::orderBy('name')->get();
        return view('back.farms.create', compact('regions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agricultural_region_id' => 'required|exists:agricultural_regions,id',
            'name' => 'required|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'producer_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'surface_hectares' => 'required|numeric|min:0',
            'farming_type' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validated['user_id'] = $user->id;

        if (empty($validated['producer_name'])) {
            $validated['producer_name'] = $user->name;
        }

        // When a producer adds a farm, status is automatically 'en_attente'
        // If an admin creates a farm directly, status is 'validee'
        $validated['status'] = $user->isAdmin() ? 'validee' : 'en_attente';

        Farm::create($validated);

        if ($user->isAdmin()) {
            return redirect()->route('back.farms.index')
                ->with('success', 'Ferme créée et validée avec succès.');
        }

        return redirect()->route('back.farms.index')
            ->with('success', 'Votre ferme a été enregistrée et envoyée pour validation auprès de l\'administrateur.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin() && $farm->user_id !== $user->id) {
            abort(403, 'Accès refusé. Vous ne pouvez consulter que vos propres fermes.');
        }

        $farm->load(['region', 'user']);
        return view('back.farms.show', compact('farm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin() && $farm->user_id !== $user->id) {
            abort(403, 'Accès refusé. Vous ne pouvez modifier que vos propres fermes.');
        }

        $regions = AgriculturalRegion::orderBy('name')->get();
        return view('back.farms.edit', compact('farm', 'regions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin() && $farm->user_id !== $user->id) {
            abort(403, 'Accès refusé.');
        }

        $validated = $request->validate([
            'agricultural_region_id' => 'required|exists:agricultural_regions,id',
            'name' => 'required|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'producer_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'surface_hectares' => 'required|numeric|min:0',
            'farming_type' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $farm->update($validated);

        return redirect()->route('back.farms.index')
            ->with('success', 'Ferme mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Farm $farm)
    {
        $user = Auth::user();

        if (! $user?->isAdmin() && $farm->user_id !== $user->id) {
            abort(403, 'Accès refusé. Vous ne pouvez supprimer que vos propres fermes.');
        }

        $farm->delete();

        return redirect()->route('back.farms.index')
            ->with('success', 'Ferme supprimée avec succès.');
    }
}

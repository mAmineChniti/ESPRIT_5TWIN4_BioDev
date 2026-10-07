<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgriculturalRegionController extends Controller
{
    /**
     * How many regions one back-office page shows.
     */
    private const PER_PAGE = 10;

    /**
     * @return array<string, mixed>
     */
    private function regionRules(?AgriculturalRegion $region = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:agricultural_regions,code'.($region ? ','.$region->id : '')],
            'climate' => ['nullable', 'string', 'max:255'],
            'soil_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * Both admins and producers see every region: a producer needs the list in
     * order to attach a farm to one.
     */
    public function index(): View
    {
        $regions = AgriculturalRegion::query()
            ->withCount('farms')
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('back.regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('back.regions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        AgriculturalRegion::create($request->validate($this->regionRules()));

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole créée avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * A producer sees only their own farms on the page; an admin sees all.
     */
    public function show(Request $request, AgriculturalRegion $region): View
    {
        $user = $request->user();

        $region->load([
            'farms' => fn (HasMany $farms) => $farms
                ->with('user')
                ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('user_id', $user->id))
                ->latest(),
        ]);

        return view('back.regions.show', compact('region'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgriculturalRegion $region): View
    {
        return view('back.regions.edit', compact('region'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AgriculturalRegion $region): RedirectResponse
    {
        $region->update($request->validate($this->regionRules($region)));

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgriculturalRegion $region): RedirectResponse
    {
        $region->delete();

        return redirect()->route('back.regions.index')
            ->with('success', 'Région agricole supprimée avec succès.');
    }
}

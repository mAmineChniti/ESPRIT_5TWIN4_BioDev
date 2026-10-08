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

        return view('back.agricultural-regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('back.agricultural-regions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        AgriculturalRegion::create($request->validate($this->regionRules()));

        return redirect()->route('regions.index')
            ->with('success', 'Agricultural region created successfully.');
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

        return view('back.agricultural-regions.show', compact('region'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, AgriculturalRegion $region): View
    {
        $this->ensureAdmin($request);

        return view('back.agricultural-regions.edit', compact('region'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AgriculturalRegion $region): RedirectResponse
    {
        $this->ensureAdmin($request);

        $region->update($request->validate($this->regionRules($region)));

        return redirect()->route('regions.index')
            ->with('success', 'Agricultural region updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, AgriculturalRegion $region): RedirectResponse
    {
        $this->ensureAdmin($request);

        $region->delete();

        return redirect()->route('regions.index')
            ->with('success', 'Agricultural region deleted successfully.');
    }

    /**
     * Route-level middleware already restricts these actions to an admin; this
     * is the second layer, so a route accidentally moved out of the group still
     * fails closed.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only an administrator can manage agricultural regions.');
    }
}

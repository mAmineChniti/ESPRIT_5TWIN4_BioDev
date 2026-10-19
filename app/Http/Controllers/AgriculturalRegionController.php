<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function index(Request $request): View
    {
        $user = $request->user();

        // A producer sees only their own farms on the show page, so the index
        // counts are scoped the same way; otherwise the listing advertises
        // farms the controller will not show.
        $regions = AgriculturalRegion::query()
            ->withCount(['farms' => fn (Builder $query) => $query
                ->when(! $user->isAdmin(), fn (Builder $inner) => $inner->where('user_id', $user->id))])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('back.regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('back.regions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        AgriculturalRegion::create($request->validate($this->regionRules()));

        return redirect()->route('regions.index')
            ->with('success', __('Agricultural region created successfully.'));
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
     * Export farms in a specific agricultural region to CSV (Excel compatible).
     */
    public function exportFarmsCsv(Request $request, AgriculturalRegion $region): StreamedResponse
    {
        $user = $request->user();

        $farms = $region->farms()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->with('user')
            ->latest()
            ->get();

        $filename = 'nutritrace-region-'.$region->code.'-farms-'.now()->format('Y-m-d').'.csv';

        return new StreamedResponse(function () use ($region, $farms): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accents properly without encoding issues
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Certificate Ref',
                'Farm Name',
                'Producer Name',
                'Region Name',
                'Region Code',
                'Regional Climate',
                'Farming Type',
                'Soil Type',
                'Surface (ha)',
                'Status',
                'Phone',
                'Address',
                'Created Date',
            ]);

            foreach ($farms as $farm) {
                fputcsv($handle, [
                    $farm->id,
                    $farm->certificateRef(),
                    $farm->name,
                    $farm->producer_name ?? ($farm->user?->name ?? ''),
                    $region->name,
                    $region->code,
                    $region->climate ?? '',
                    $farm->farming_type,
                    $farm->soil_type ?? ($region->soil_type ?? ''),
                    $farm->surface_hectares,
                    $farm->status->value,
                    $farm->phone ?? '',
                    $farm->address,
                    $farm->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, AgriculturalRegion $region): View
    {
        $this->ensureAdmin($request);

        return view('back.regions.edit', compact('region'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AgriculturalRegion $region): RedirectResponse
    {
        $this->ensureAdmin($request);

        $region->update($request->validate($this->regionRules($region)));

        return redirect()->route('regions.index')
            ->with('success', __('Agricultural region updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, AgriculturalRegion $region): RedirectResponse
    {
        $this->ensureAdmin($request);

        $region->delete();

        return redirect()->route('regions.index')
            ->with('success', __('Agricultural region deleted successfully.'));
    }

    /**
     * Route-level middleware already restricts these actions to an admin; this
     * is the second layer, so a route accidentally moved out of the group still
     * fails closed.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, __('Only an administrator can manage agricultural regions.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use Illuminate\Contracts\View\View;

class FrontRegionController extends Controller
{
    /**
     * How many farms one region page shows before it is paginated.
     */
    private const FARMS_PER_PAGE = 12;

    /**
     * Display a public listing of agricultural regions and farms.
     *
     * Only farms that cleared administrative review are published here: a
     * pending or rejected farm stays in the back office until an admin acts on
     * it. See Farm::scopeApproved().
     */
    public function index(): View
    {
        $regions = AgriculturalRegion::query()
            ->withCount('approvedFarms')
            ->with(['approvedFarms' => fn ($farms) => $farms->latest()])
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('front.agricultural-regions.index', [
            'regions' => $regions,
            'totalFarms' => Farm::query()->approved()->count(),
        ]);
    }

    /**
     * Display details of a specific agricultural region and its approved farms.
     */
    public function show(AgriculturalRegion $agriculturalRegion): View
    {
        $farms = $agriculturalRegion->approvedFarms()
            ->latest()
            ->paginate(self::FARMS_PER_PAGE);

        return view('front.agricultural-regions.show', [
            'region' => $agriculturalRegion,
            'farms' => $farms,
        ]);
    }
}

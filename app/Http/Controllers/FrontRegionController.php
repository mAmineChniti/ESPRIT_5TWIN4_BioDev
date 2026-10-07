<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use Illuminate\Http\Request;

class FrontRegionController extends Controller
{
    /**
     * Display a public listing of agricultural regions and farms.
     */
    public function index()
    {
        $regions = AgriculturalRegion::with('farms')->withCount('farms')->get();
        $totalFarms = Farm::count();

        return view('front.regions.index', compact('regions', 'totalFarms'));
    }

    /**
     * Display details of a specific agricultural region and its farms.
     */
    public function show(AgriculturalRegion $agriculturalRegion)
    {
        $agriculturalRegion->load('farms');
        return view('front.regions.show', ['region' => $agriculturalRegion]);
    }
}

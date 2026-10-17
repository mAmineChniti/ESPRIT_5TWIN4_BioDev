<?php

namespace App\Http\Controllers;

use App\Services\Geo\VisitorLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resolving where the visitor is, for the origin map.
 *
 * The server already has an approximate position from the request address by
 * the time a product page renders. This covers the case that leaves: the
 * address was private, the provider was unavailable, or the visitor turned the
 * browser prompt down. In that case the browser still knows its own timezone,
 * which is coarse but free and needs no permission.
 *
 * It is deliberately a read of something the visitor's own browser reports. It
 * stores nothing: the answer goes back to the page that asked and nowhere else.
 */
class VisitorLocationController extends Controller
{
    public function __construct(private readonly VisitorLocation $locations) {}

    /**
     * The country a browser timezone names, as JSON.
     */
    public function store(Request $request): JsonResponse
    {
        // Validated rather than trusted: the value decides where a map line is
        // drawn, and anything that is not a real zone has no country to give.
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $location = $this->locations->fromTimezone($validated['timezone']);

        // An honest 200 with a null body beats a 404 the page has to treat as
        // an error: a zone that names no country is a normal outcome.
        return response()->json([
            'location' => $location?->toArray(),
        ]);
    }
}

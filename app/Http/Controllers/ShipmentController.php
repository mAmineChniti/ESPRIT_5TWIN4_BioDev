<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Http\Requests\ShipmentRequest;
use App\Models\Food;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\CarbonFootprintCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $statusInput = $request->query('status');
        $modeInput = $request->query('mode');
        $status = is_string($statusInput) && ShipmentStatus::tryFrom($statusInput) !== null
            ? $statusInput
            : null;
        $mode = is_string($modeInput) && TransportMode::tryFrom($modeInput) !== null
            ? $modeInput
            : null;

        // A distributor only ever sees their own shipments, because
        // ensureOwnership() refuses every other record. Scoping the query is
        // what keeps the listing honest: a row that 403s when clicked is a
        // broken link, and the footprint totals below must not count another
        // distributor's freight as this distributor's.
        $filteredShipments = Shipment::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($mode, fn ($query) => $query->where('transport_mode', $mode));

        $shipments = (clone $filteredShipments)
            ->with(['warehouse', 'food'])
            ->latest('shipped_on')
            ->paginate(10)
            ->withQueryString();

        // Value added: total footprint and breakdown by transport mode.
        $totalCo2 = (float) (clone $filteredShipments)->sum('carbon_footprint_kg');
        $byMode = (clone $filteredShipments)
            ->selectRaw('transport_mode, COUNT(*) as shipments_count, SUM(carbon_footprint_kg) as co2')
            ->groupBy('transport_mode')
            ->orderByDesc('co2')
            ->get();

        return view('back.logistics.shipments.index', [
            'shipments' => $shipments,
            'totalCo2' => $totalCo2,
            'byMode' => $byMode,
            'statuses' => ShipmentStatus::cases(),
            'modes' => TransportMode::cases(),
            'filters' => ['status' => $status, 'mode' => $mode],
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('back.logistics.shipments.create', $this->formData() + ['shipment' => new Shipment]);
    }

    public function store(ShipmentRequest $request): RedirectResponse
    {
        Shipment::create([
            ...$request->shipmentPayload(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('logistics.shipments.index')
            ->with('success', 'Shipment added successfully.');
    }

    public function show(Request $request, Shipment $shipment): View
    {
        $this->ensureOwnership($request, $shipment);

        $shipment->load(['warehouse', 'food', 'creator']);

        $comparison = CarbonFootprintCalculator::compare(
            (float) $shipment->weight_kg,
            (float) $shipment->distance_km,
        );

        return view('back.logistics.shipments.show', compact('shipment', 'comparison'));
    }

    public function edit(Request $request, Shipment $shipment): View
    {
        $this->ensureOwnership($request, $shipment);

        return view('back.logistics.shipments.edit', $this->formData() + compact('shipment'));
    }

    public function update(ShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->ensureOwnership($request, $shipment);

        $shipment->update($request->shipmentPayload());

        return redirect()->route('logistics.shipments.index')
            ->with('success', 'Shipment updated successfully.');
    }

    public function destroy(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->ensureOwnership($request, $shipment);

        $shipment->delete();

        return redirect()->route('logistics.shipments.index')
            ->with('success', 'Shipment deleted.');
    }

    /**
     * A shipment is recorded against the distributor who created it.
     *
     * The route group admits both `distributor` and `admin`, so middleware
     * alone lets any distributor reach any shipment. An admin supervises the
     * whole area; a distributor only sees and edits their own records, which is
     * why index() scopes its query the same way.
     */
    private function ensureOwnership(Request $request, Shipment $shipment): void
    {
        $isAdmin = $request->user()?->isAdmin() ?? false;

        abort_unless(
            $isAdmin || $shipment->user_id === $request->user()?->id,
            403,
            'You can only manage your own shipments.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::orderBy('name')->get(),
            'foods' => Food::orderBy('name')->get(),
            'modes' => TransportMode::cases(),
            'statuses' => ShipmentStatus::cases(),
        ];
    }
}

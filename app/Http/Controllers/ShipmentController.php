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
        $statusInput = $request->query('status');
        $modeInput = $request->query('mode');
        $status = is_string($statusInput) && ShipmentStatus::tryFrom($statusInput) !== null
            ? $statusInput
            : null;
        $mode = is_string($modeInput) && TransportMode::tryFrom($modeInput) !== null
            ? $modeInput
            : null;

        $filteredShipments = Shipment::query()
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

        return view('logistics.shipments.index', [
            'shipments' => $shipments,
            'totalCo2' => $totalCo2,
            'byMode' => $byMode,
            'statuses' => ShipmentStatus::cases(),
            'modes' => TransportMode::cases(),
            'filters' => ['status' => $status, 'mode' => $mode],
        ]);
    }

    public function create(): View
    {
        return view('logistics.shipments.create', $this->formData() + ['shipment' => new Shipment]);
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

    public function show(Shipment $shipment): View
    {
        $shipment->load(['warehouse', 'food', 'creator']);

        $comparison = CarbonFootprintCalculator::compare(
            (float) $shipment->weight_kg,
            (float) $shipment->distance_km,
        );

        return view('logistics.shipments.show', compact('shipment', 'comparison'));
    }

    public function edit(Shipment $shipment): View
    {
        return view('logistics.shipments.edit', $this->formData() + compact('shipment'));
    }

    public function update(ShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $shipment->update($request->shipmentPayload());

        return redirect()->route('logistics.shipments.index')
            ->with('success', 'Shipment updated successfully.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $shipment->delete();

        return redirect()->route('logistics.shipments.index')
            ->with('success', 'Shipment deleted.');
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

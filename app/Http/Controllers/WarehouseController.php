<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::withCount('shipments')->orderBy('name')->paginate(10);

        return view('back.logistics.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('back.logistics.warehouses.create', ['warehouse' => new Warehouse]);
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse added successfully.');
    }

    public function show(Request $request, Warehouse $warehouse): View
    {
        $user = $request->user();

        // A distributor only sees their own shipments here, so the page never
        // offers a row the shipment controller would refuse.
        $shipments = $warehouse->shipments()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest('shipped_on')
            ->paginate(10);

        return view('back.logistics.warehouses.show', compact('warehouse', 'shipments'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('back.logistics.warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $this->ensureAdmin($request);

        // Its shipments are deleted too (cascade on the foreign key).
        $warehouse->delete();

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse deleted.');
    }

    /**
     * Warehouses are shared infrastructure with no owner, and deleting one
     * cascades to every shipment stored in it. Only an administrator can take
     * that decision; a distributor reaching this route any other way fails
     * closed.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only an administrator can delete a warehouse.');
    }
}

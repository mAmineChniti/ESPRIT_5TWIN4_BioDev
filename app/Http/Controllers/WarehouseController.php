<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::withCount('shipments')->orderBy('name')->paginate(10);

        return view('logistics.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('logistics.warehouses.create', ['warehouse' => new Warehouse]);
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse added successfully.');
    }

    public function show(Warehouse $warehouse): View
    {
        $warehouse->load(['shipments' => fn ($query) => $query->latest('shipped_on')]);

        return view('logistics.warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('logistics.warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        // Its shipments are deleted too (cascade on the foreign key).
        $warehouse->delete();

        return redirect()->route('logistics.warehouses.index')
            ->with('success', 'Warehouse deleted.');
    }
}

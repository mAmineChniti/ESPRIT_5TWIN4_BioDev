<?php

use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\WarehouseController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

// Back office for distributors and admins.
// Route names: logistics.warehouses.index, logistics.shipments.create, ...
Route::middleware(['auth', EnsureUserHasRole::class.':distributor,admin'])
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function (): void {
        Route::resource('warehouses', WarehouseController::class);

        // The AI carbon analysis on the warehouse page. It calls a paid
        // provider, so it is throttled.
        Route::post('warehouses/{warehouse}/carbon-analysis', [WarehouseController::class, 'analyze'])
            ->middleware('throttle:10,1')
            ->name('warehouses.analyze');

        // The AI transport advisor behind the shipment form. It calls a paid
        // provider, so it is throttled; declared before the resource so the
        // path is never read as a shipment id.
        Route::post('shipments/advise-transport', [ShipmentController::class, 'advise'])
            ->middleware('throttle:20,1')
            ->name('shipments.advise');

        Route::resource('shipments', ShipmentController::class);
    });

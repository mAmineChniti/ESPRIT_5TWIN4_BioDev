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
        Route::resource('shipments', ShipmentController::class);
    });
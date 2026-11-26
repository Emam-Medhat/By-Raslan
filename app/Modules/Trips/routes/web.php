<?php

use App\Modules\Trips\Http\Controllers\CrateController;
use App\Modules\Trips\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');

    Route::middleware('perm:settings.edit')->group(function () {
        Route::get('/crates', [CrateController::class, 'index'])->name('crates.index');
        Route::post('/crates', [CrateController::class, 'store'])->name('crates.store');
        Route::post('/crates/{crate}/toggle', [CrateController::class, 'toggle'])->name('crates.toggle');
    });

    Route::middleware('perm:trips.load')->group(function () {
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
        Route::post('/trips/{trip}/crates', [TripController::class, 'crateOpen'])->name('trips.crates.open');
        Route::post('/trips/{trip}/load', [TripController::class, 'load'])->name('trips.load');
        Route::post('/trips/{trip}/unload', [TripController::class, 'unload'])->name('trips.unload');
        Route::post('/trips/{trip}/depart', [TripController::class, 'depart'])->name('trips.depart');
    });
});
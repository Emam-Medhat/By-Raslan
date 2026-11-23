<?php

use App\Modules\Organization\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/locations', [LocationController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('locations.index');

    Route::get('/branches/{location}', [LocationController::class, 'branch'])
        ->middleware('perm:stock.view')
        ->name('branches.show');

    Route::post('/locations', [LocationController::class, 'store'])
        ->middleware('perm:settings.edit')
        ->name('locations.store');

    Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])
        ->middleware('perm:settings.edit')
        ->name('locations.edit');

    Route::put('/locations/{location}', [LocationController::class, 'update'])
        ->middleware('perm:settings.edit')
        ->name('locations.update');

    Route::post('/locations/{location}/toggle', [LocationController::class, 'toggle'])
        ->middleware('perm:settings.edit')
        ->name('locations.toggle');
});
<?php

use App\Modules\Organization\Http\Controllers\LocationController;
use App\Modules\Organization\Http\Controllers\LocationUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
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

    // Location-User management
    Route::get('/locations/{location}/users', [LocationUserController::class, 'index'])
        ->middleware('perm:users.manage')
        ->name('locations.users.index');

    Route::post('/locations/{location}/users', [LocationUserController::class, 'store'])
        ->middleware('perm:users.manage')
        ->name('locations.users.store');

    Route::put('/locations/{location}/users/{assignment}', [LocationUserController::class, 'update'])
        ->middleware('perm:users.manage')
        ->name('locations.users.update');

    Route::delete('/locations/{location}/users/{assignment}', [LocationUserController::class, 'destroy'])
        ->middleware('perm:users.manage')
        ->name('locations.users.destroy');
});
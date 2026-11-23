<?php

use App\Modules\Production\Http\Controllers\ProductionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    // Static routes first (before /production/{order} to avoid shadowing).
    Route::get('/production', [ProductionController::class, 'index'])
        ->middleware('perm:stock.view')->name('production.index');

    Route::middleware('perm:items.edit')->group(function () {
        Route::get('/production/routes', [ProductionController::class, 'routes'])->name('production.routes');
        Route::post('/production/items/{item}/route', [ProductionController::class, 'setRoute'])->name('production.setRoute');
    });

    Route::middleware('perm:prod.pack')->group(function () {
        Route::get('/pack', [ProductionController::class, 'packPage'])->name('pack.index');
        Route::post('/pack', [ProductionController::class, 'pack'])->name('pack.store');
        Route::post('/pack/finish', [ProductionController::class, 'packFinish'])->name('pack.finish');
    });

    Route::middleware('perm:prod.orders')->group(function () {
        Route::get('/production/create', [ProductionController::class, 'create'])->name('production.create');
        Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
        Route::get('/production/{order}', [ProductionController::class, 'show'])->name('production.show');
        Route::post('/production/{order}/steps/{step}/start', [ProductionController::class, 'start'])->name('production.start');
        Route::post('/production/{order}/steps/{step}/finish', [ProductionController::class, 'finish'])->name('production.finish');
    });
});
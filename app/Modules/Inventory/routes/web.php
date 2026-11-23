<?php

use App\Modules\Inventory\Http\Controllers\BoxController;
use App\Modules\Inventory\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/stock', [StockController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('stock.index');

    Route::get('/stock/export', [StockController::class, 'export'])
        ->middleware('perm:stock.view')
        ->name('stock.export');

    Route::post('/stock/adjust', [StockController::class, 'adjust'])
        ->middleware('perm:stock.adjust')
        ->name('stock.adjust');

    Route::get('/stock/dispose', [StockController::class, 'disposePage'])
        ->middleware('perm:stock.view')
        ->name('stock.dispose');

    Route::post('/stock/dispose', [StockController::class, 'dispose'])
        ->name('stock.dispose.store');

    Route::post('/boxes/resolve-missing', [BoxController::class, 'resolveMissing'])
        ->middleware('perm:stock.adjust')
        ->name('boxes.resolve-missing');

    Route::get('/boxes', [BoxController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('boxes.index');
});
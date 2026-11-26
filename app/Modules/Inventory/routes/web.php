<?php

use App\Modules\Inventory\Http\Controllers\BoxController;
use App\Modules\Inventory\Http\Controllers\StockController;
use App\Modules\Inventory\Http\Controllers\StockCountController;
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

    Route::get('/boxes/{box}/sticker', [BoxController::class, 'sticker'])
        ->middleware('perm:stock.view')
        ->name('boxes.sticker');

    // Stock Counts (Stocktaking)
    Route::get('/stock-counts', [StockCountController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('stock-counts.index');

    Route::get('/stock-counts/create', [StockCountController::class, 'create'])
        ->middleware('perm:stock.adjust')
        ->name('stock-counts.create');

    Route::post('/stock-counts', [StockCountController::class, 'store'])
        ->middleware('perm:stock.adjust')
        ->name('stock-counts.store');

    Route::get('/stock-counts/{stockCount}', [StockCountController::class, 'show'])
        ->middleware('perm:stock.view')
        ->name('stock-counts.show');

    Route::post('/stock-counts/{stockCount}/items', [StockCountController::class, 'addItem'])
        ->middleware('perm:stock.adjust')
        ->name('stock-counts.add-item');

    Route::put('/stock-count-items/{item}', [StockCountController::class, 'updateItem'])
        ->middleware('perm:stock.adjust')
        ->name('stock-count-items.update');

    Route::post('/stock-counts/{stockCount}/approve', [StockCountController::class, 'approve'])
        ->middleware('perm:stock.adjust')
        ->name('stock-counts.approve');
});
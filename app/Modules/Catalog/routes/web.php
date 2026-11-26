<?php

use App\Modules\Catalog\Http\Controllers\ItemController;
use App\Modules\Catalog\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    // Items — read for stock viewers, write for catalog editors.
    Route::get('/items', [ItemController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('items.index');

    Route::middleware('perm:items.edit')->group(function () {
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::post('/items/families', [ItemController::class, 'storeFamily'])->name('items.families.store');
    });

    // Prices.
    Route::middleware('perm:prices.edit')->group(function () {
        Route::get('/prices', [PriceController::class, 'index'])->name('prices.index');
        Route::post('/prices', [PriceController::class, 'save'])->name('prices.save');
    });
});
<?php

use App\Modules\Returns\Http\Controllers\PurchaseReturnController;
use App\Modules\Returns\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    Route::get('/returns', [ReturnController::class, 'index'])
        ->middleware('perm:acc.view')->name('returns.index');

    Route::middleware('perm:pos.shift')->group(function () {
        Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
        Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
        Route::post('/returns/{return}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
        Route::post('/returns/{return}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
    });

    // Purchase returns (to supplier)
    Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])
        ->middleware('perm:acc.view')->name('purchase-returns.index');
    Route::middleware('perm:purchase.edit')->group(function () {
        Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
        Route::post('/purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve'])->name('purchase-returns.approve');
    });
});
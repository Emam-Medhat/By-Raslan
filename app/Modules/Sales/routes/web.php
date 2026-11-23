<?php

use App\Modules\Sales\Http\Controllers\PosController;
use App\Modules\Sales\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::middleware('perm:pos.sell')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/open', [PosController::class, 'open'])->name('pos.open');
        Route::post('/pos/lookup', [PosController::class, 'lookup'])->name('pos.lookup');
        Route::post('/pos/search', [PosController::class, 'search'])->name('pos.search');
        Route::post('/pos/sale', [PosController::class, 'sale'])
            ->middleware('shift')
            ->name('pos.sale');
        Route::post('/pos/close', [PosController::class, 'close'])->name('pos.close');
    });

    Route::middleware('perm:acc.view')->group(function () {
        Route::get('/sales', [PosController::class, 'sales'])->name('sales.index');
        Route::get('/sales/export', [PosController::class, 'salesExport'])->name('sales.export');
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/export', [ShiftController::class, 'export'])->name('shifts.export');
        Route::get('/shifts/{shift}/movements', [ShiftController::class, 'movements'])->name('shifts.movements');
    });
});
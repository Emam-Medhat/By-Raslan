<?php

use App\Modules\Sales\Http\Controllers\CashTransferController;
use App\Modules\Sales\Http\Controllers\PosController;
use App\Modules\Sales\Http\Controllers\ShiftController;
use App\Modules\Sales\Http\Controllers\ShiftExpenseController;
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

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
        Route::get('/shifts/export', [ShiftController::class, 'export'])->name('shifts.export');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
        Route::get('/shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
        Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
        Route::get('/shifts/{shift}/movements', [ShiftController::class, 'movements'])->name('shifts.movements');

        // Shift Expenses
        Route::get('/shift-expenses', [ShiftExpenseController::class, 'index'])->name('shift-expenses.index');
        Route::get('/shifts/{shift}/expenses/create', [ShiftExpenseController::class, 'create'])->name('shift-expenses.create');
        Route::post('/shifts/{shift}/expenses', [ShiftExpenseController::class, 'store'])->name('shift-expenses.store');
        Route::post('/shift-expenses/{expense}/approve', [ShiftExpenseController::class, 'approve'])->name('shift-expenses.approve');
        Route::post('/shift-expenses/{expense}/reject', [ShiftExpenseController::class, 'reject'])->name('shift-expenses.reject');

        // Cash Transfers
        Route::get('/cash-transfers', [CashTransferController::class, 'index'])->name('cash-transfers.index');
        Route::get('/shifts/{shift}/transfers/create', [CashTransferController::class, 'create'])->name('cash-transfers.create');
        Route::post('/shifts/{shift}/transfers', [CashTransferController::class, 'store'])->name('cash-transfers.store');
        Route::post('/cash-transfers/{transfer}/approve', [CashTransferController::class, 'approve'])->name('cash-transfers.approve');
        Route::post('/cash-transfers/{transfer}/reject', [CashTransferController::class, 'reject'])->name('cash-transfers.reject');
    });
});
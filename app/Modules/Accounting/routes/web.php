<?php

use App\Modules\Accounting\Http\Controllers\AccountController;
use App\Modules\Accounting\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('perm:acc.view')->name('accounts.index');

    Route::get('/accounts/expenses', [ExpenseController::class, 'index'])
        ->middleware('perm:acc.view')->name('expenses.index');
    Route::post('/accounts/expenses', [ExpenseController::class, 'store'])
        ->middleware('perm:acc.post')->name('expenses.store');
    Route::post('/accounts/expenses/{expense}/decide', [ExpenseController::class, 'decide'])
        ->middleware('perm:acc.approve')->name('expenses.decide');

    Route::get('/accounts/suppliers', [AccountController::class, 'suppliers'])
        ->middleware('perm:acc.view')->name('accounts.suppliers.index');
    Route::get('/accounts/suppliers/{supplier}', [AccountController::class, 'supplier'])
        ->middleware('perm:acc.view')->name('accounts.suppliers.show');
    Route::post('/accounts/suppliers/{supplier}/pay', [AccountController::class, 'paySupplier'])
        ->middleware('perm:acc.pay')->name('accounts.suppliers.pay');
});

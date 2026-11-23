<?php

use App\Modules\Transfers\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/transfers', [TransferController::class, 'index'])
        ->middleware('perm:stock.view')
        ->name('transfers.index');

    Route::get('/transfers/create', [TransferController::class, 'create'])
        ->middleware('perm:stock.edit')
        ->name('transfers.create');

    Route::get('/transfers/new', [TransferController::class, 'create'])
        ->middleware('perm:stock.edit')
        ->name('transfers.new');

    Route::post('/transfers', [TransferController::class, 'send'])
        ->middleware('perm:stock.edit')
        ->name('transfers.store');

    Route::get('/transfers/{transfer}', [TransferController::class, 'show'])
        ->middleware('perm:stock.view')
        ->name('transfers.show');

    Route::post('/transfers/{transfer}/receive', [TransferController::class, 'receive'])
        ->middleware('perm:stock.edit')
        ->name('transfers.receive');
});
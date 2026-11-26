<?php

use App\Modules\Reporting\Http\Controllers\DashboardController;
use App\Modules\Reporting\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->middleware('perm:acc.view')->name('statistics.index');
    Route::get('/', fn () => redirect()->route('dashboard'));
});
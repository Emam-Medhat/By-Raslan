<?php

use App\Modules\Settings\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('perm:settings.edit')->name('settings.index');

    Route::post('/settings', [SettingController::class, 'update'])
        ->middleware('perm:settings.edit')->name('settings.update');
});
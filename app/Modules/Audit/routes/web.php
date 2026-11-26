<?php

use App\Modules\Audit\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('perm:audit.view')
        ->name('audit.index');
});
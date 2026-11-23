<?php

use App\Modules\Orders\Http\Controllers\PublicOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::view('/soon', 'soon')->name('soon');
Route::get('/order', [PublicOrderController::class, 'shop'])->name('public.order');
Route::get('/t/{token}', [PublicOrderController::class, 'trackPage'])->name('public.track');

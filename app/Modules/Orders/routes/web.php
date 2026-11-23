<?php

use App\Modules\Orders\Http\Controllers\OrderController;
use App\Modules\Orders\Http\Controllers\PublicOrderController;
use App\Modules\Orders\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Public customer pages (no auth).
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/track/{token}', [ShopController::class, 'track'])->name('shop.track');

/*
|--------------------------------------------------------------------------
| Orders module routes (staff) + public customer API
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('perm:orders.handle')->name('orders.index');

    Route::middleware('perm:orders.handle')->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [OrderController::class, 'status'])->name('orders.status');
        Route::post('/orders/{order}/scan', [OrderController::class, 'scan'])->name('orders.scan');
        Route::post('/orders/{order}/unscan', [OrderController::class, 'unscan'])->name('orders.unscan');
        Route::post('/orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
    });
});

// Public customer API (no auth; CSRF excluded for /api/*).
Route::prefix('api')->group(function () {
    Route::get('/branches', [PublicOrderController::class, 'branches']);
    Route::get('/menu', [PublicOrderController::class, 'menu']);
    Route::post('/orders', [PublicOrderController::class, 'place']);
    Route::get('/track/{token}', [PublicOrderController::class, 'track']);
    Route::post('/push-token', [PublicOrderController::class, 'pushToken']);
});
// Mobile app compatibility endpoints (RPC-style)
Route::prefix('api/app')->group(function () {
    Route::get('/config', [PublicOrderController::class, 'appConfig']);
    Route::get('/menu', [PublicOrderController::class, 'appMenu']);
    Route::post('/place-order', [PublicOrderController::class, 'appPlaceOrder']);
    Route::get('/track', [PublicOrderController::class, 'appTrack']);
});

// Supabase RPC compatibility shim for mobile app (best-effort)
Route::prefix('rest/v1/rpc')->group(function () {
    Route::post('/app_config', [PublicOrderController::class, 'rpcAppConfig']);
    Route::post('/app_menu', [PublicOrderController::class, 'rpcAppMenu']);
    Route::post('/app_place_order', [PublicOrderController::class, 'rpcAppPlaceOrder']);
    Route::post('/track_order', [PublicOrderController::class, 'rpcTrackOrder']);
    Route::post('/app_register_push', [PublicOrderController::class, 'rpcRegisterPush']);
    Route::post('/app_me', [PublicOrderController::class, 'rpcAppMe']);
    Route::post('/app_save_name', [PublicOrderController::class, 'rpcSaveName']);
    Route::post('/app_save_address', [PublicOrderController::class, 'rpcSaveAddress']);
    Route::post('/app_remove_address', [PublicOrderController::class, 'rpcRemoveAddress']);
    Route::post('/app_my_orders', [PublicOrderController::class, 'rpcMyOrders']);
    Route::post('/app_request_delete', [PublicOrderController::class, 'rpcRequestDelete']);
    Route::post('/app_test_login', [PublicOrderController::class, 'rpcTestLogin']);
});

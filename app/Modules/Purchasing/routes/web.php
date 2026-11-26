<?php

use App\Modules\Purchasing\Http\Controllers\OverrideController;
use App\Modules\Purchasing\Http\Controllers\PurchaseOrderController;
use App\Modules\Purchasing\Http\Controllers\SpecController;
use App\Modules\Purchasing\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'nav.access'])->group(function () {
    // Suppliers
    Route::get('/purchasing/suppliers', [SupplierController::class, 'index'])
        ->middleware('perm:stock.view')->name('purchasing.suppliers.index');
    Route::post('/purchasing/suppliers', [SupplierController::class, 'store'])
        ->middleware('perm:purchase.edit')->name('purchasing.suppliers.store');
    Route::post('/purchasing/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])
        ->middleware('perm:purchase.edit')->name('purchasing.suppliers.toggle');
    Route::post('/purchasing/suppliers/{supplier}/pay', [SupplierController::class, 'pay'])
        ->middleware('perm:acc.pay')->name('purchasing.suppliers.pay');

    // Receipts
    Route::get('/purchasing/receipts', [PurchaseOrderController::class, 'receipts'])
        ->middleware('perm:stock.view')->name('purchasing.receipts.index');
    Route::get('/purchasing/receipts/{receipt}', [PurchaseOrderController::class, 'receiptShow'])
        ->middleware('perm:stock.view')->name('purchasing.receipts.show');

    // Purchase orders
    Route::get('/purchasing', [PurchaseOrderController::class, 'index'])
        ->middleware('perm:stock.view')->name('purchasing.index');
    Route::get('/purchasing/create', [PurchaseOrderController::class, 'create'])
        ->middleware('perm:purchase.edit')->name('purchasing.create');

    // QA specs (item spec versions)
    Route::get('/purchasing/specs', [SpecController::class, 'index'])
        ->middleware('perm:stock.view')->name('purchasing.specs.index');
    Route::get('/purchasing/specs/{item}', [SpecController::class, 'show'])
        ->middleware('perm:stock.view')->name('purchasing.specs.show');
    Route::post('/purchasing/specs/{item}', [SpecController::class, 'save'])
        ->middleware('perm:purchase.edit')->name('purchasing.specs.save');
    Route::post('/purchasing', [PurchaseOrderController::class, 'store'])
        ->middleware('perm:purchase.edit')->name('purchasing.store');
    Route::get('/purchasing/receive/{purchaseOrder}', [PurchaseOrderController::class, 'receivePage'])
        ->middleware('perm:purchase.edit')->name('purchasing.receive.page');
    Route::get('/purchasing/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->middleware('perm:stock.view')->name('purchasing.show');
    Route::post('/purchasing/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->middleware('perm:purchase.edit')->name('purchasing.cancel');
    Route::post('/purchasing/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('perm:purchase.edit')->name('purchasing.receive');
    Route::post('/purchasing/{purchaseOrder}/request-override', [PurchaseOrderController::class, 'requestOverride'])
        ->middleware('perm:purchase.edit')->name('purchasing.requestOverride');

    // Overrides
    Route::get('/overrides', [OverrideController::class, 'index'])
        ->middleware('perm:stock.view')->name('overrides.index');
    Route::post('/overrides/{override}/decide', [OverrideController::class, 'decide'])
        ->middleware('perm:override.recv')->name('overrides.decide');
});
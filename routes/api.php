<?php

use App\Http\Controllers\Api\ErpWebhookController;
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\Api\ProductSyncController;
use App\Http\Controllers\Api\RetryFailedSyncController;
use App\Http\Controllers\Api\StockSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('webhooks/erp', ErpWebhookController::class)->name('webhooks.erp');

    Route::middleware('integration.token')->group(function (): void {
        Route::post('sync/products', ProductSyncController::class)->name('sync.products');
        Route::post('sync/stock', StockSyncController::class)->name('sync.stock');
        Route::post('sync/orders/{erpOrderNumber}', OrderSyncController::class)->name('sync.orders');
        Route::post('sync/retry-failed', RetryFailedSyncController::class)->name('sync.retry-failed');
    });
});

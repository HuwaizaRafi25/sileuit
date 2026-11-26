<?php

use App\Modules\BillingAccountsReceivable\Http\Controllers\PaymentWebhookController;
use App\Modules\ProcurementReceiving\Http\Controllers\OfflineSyncController;
use App\Modules\ProcurementReceiving\Http\Controllers\ReceiptActionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    Route::post('/receipts/{receiptId}/pay', [ReceiptActionController::class, 'pay']);
    Route::post('/receipts/{receiptId}/approve', [ReceiptActionController::class, 'approve']);
    Route::post('/procurement/sync', [OfflineSyncController::class, 'sync']);
});

Route::post('/v1/payments/webhook', [PaymentWebhookController::class, 'handle']);

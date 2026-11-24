<?php

use App\Modules\ProcurementReceiving\Http\Controllers\ReceiptActionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    Route::post('/receipts/{receiptId}/pay', [ReceiptActionController::class, 'pay']);
    Route::post('/receipts/{receiptId}/approve', [ReceiptActionController::class, 'approve']);
});

// POST /api/v1/payments/webhook -> Fase 4 (FR-12, TC-13)
// POST /api/v1/procurement/sync -> Fase 6 (FR-18, TC-14)

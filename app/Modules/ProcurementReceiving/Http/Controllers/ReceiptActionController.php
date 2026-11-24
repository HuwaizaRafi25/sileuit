<?php

namespace App\Modules\ProcurementReceiving\Http\Controllers;

use App\Modules\ProcurementReceiving\Actions\ApproveDiscrepancyAction;
use App\Modules\ProcurementReceiving\Actions\PayFarmerAction;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReceiptActionController
{
    private function cari(int $id): Receipt
    {
        // Sengaja tanpa global scope: agar penolakan berupa 403 + audit, bukan 404 senyap.
        return Receipt::withoutGlobalScopes()->findOrFail($id);
    }

    public function pay(Request $request, int $receiptId, PayFarmerAction $aksi): JsonResponse
    {
        $r = $this->cari($receiptId);
        Gate::authorize('pay', $r);

        $data = $request->validate([
            'metode' => 'required|in:TUNAI,TRANSFER',
            'referensi' => 'required|string|max:80',
            'idempotency_key' => 'required|string|max:64',
        ]);

        $payment = $aksi->execute($r, $request->user(), $data['metode'], $data['referensi'], $data['idempotency_key']);

        return response()->json(['payment_id' => $payment->id, 'jumlah' => $payment->jumlah, 'status' => $r->fresh()->status]);
    }

    public function approve(Request $request, int $receiptId, ApproveDiscrepancyAction $aksi): JsonResponse
    {
        $r = $this->cari($receiptId);
        Gate::authorize('approve', $r);

        $data = $request->validate([
            'setuju' => 'required|boolean',
            'alasan' => 'required|string|max:500',
        ]);

        $r = $aksi->execute($r, $request->user(), $data['setuju'], $data['alasan']);

        return response()->json(['status' => $r->status]);
    }
}

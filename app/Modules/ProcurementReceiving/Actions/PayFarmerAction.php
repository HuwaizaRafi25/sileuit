<?php

namespace App\Modules\ProcurementReceiving\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayFarmerAction
{
    /** BR-07 / IR-02 / IR-15: satu penerimaan dibayar sekali, idempoten terhadap kunci yang sama. */
    public function execute(Receipt $receipt, User $oleh, string $metode, string $referensi, string $idempotencyKey): Payment
    {
        return DB::transaction(function () use ($receipt, $oleh, $metode, $referensi, $idempotencyKey) {
            $sudah = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($sudah) {
                return $sudah;
            }

            $r = Receipt::withoutGlobalScopes()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($r->status !== 'CONFIRMED') {
                throw ValidationException::withMessages(['status' => 'Penerimaan sudah dibayar atau belum dikonfirmasi.']);
            }
            if ((int) $r->nilai_bayar <= 0) {
                throw ValidationException::withMessages(['nilai' => 'Penerimaan ini tidak memiliki nilai bayar (karantina/ditolak).']);
            }
            if (PaymentAllocation::where('receipt_id', $r->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Penerimaan ini sudah pernah dibayar (BR-07).']);
            }
            if (! in_array($metode, ['TUNAI', 'TRANSFER'], true) || trim($referensi) === '') {
                throw ValidationException::withMessages(['referensi' => 'Transfer wajib bukti transfer; tunai wajib nomor tanda terima (BR-07).']);
            }

            $payment = Payment::create([
                'arah' => 'KELUAR',
                'partner_id' => $r->contract->partner_id,
                'metode' => $metode,
                'jumlah' => (int) $r->nilai_bayar,
                'referensi' => $referensi,
                'idempotency_key' => $idempotencyKey,
                'dicatat_oleh' => $oleh->id,
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'receipt_id' => $r->id,
                'jumlah_alokasi' => (int) $r->nilai_bayar,
            ]);

            $r->update(['status' => 'PAID']);

            AuditLogger::log('PEMBAYARAN_PETANI', 'payments', $payment->id, ['status' => 'CONFIRMED'],
                ['status' => 'PAID', 'jumlah' => $payment->jumlah, 'metode' => $metode]);

            return $payment;
        });
    }
}

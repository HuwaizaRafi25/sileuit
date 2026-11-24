<?php

namespace App\Modules\BillingAccountsReceivable\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\IdentityAccess\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordInvoicePaymentAction
{
    /**
     * BR-08 / TC-11, TC-21, TC-22: alokasi FIFO ke invoice tertua; pembayaran > saldo ditolak (A-17);
     * idempotency key mencegah duplikasi.
     *
     * @param  array<int, int>|null  $invoiceIds  urutan pilihan Keuangan; bila null → otomatis tertua
     */
    public function execute(User $oleh, int $partnerId, int $jumlah, string $metode, string $referensi, string $idempotencyKey, ?array $invoiceIds = null): Payment
    {
        return DB::transaction(function () use ($oleh, $partnerId, $jumlah, $metode, $referensi, $idempotencyKey, $invoiceIds) {
            $sudah = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($sudah) {
                return $sudah;
            }
            if ($jumlah <= 0) {
                throw ValidationException::withMessages(['jumlah' => 'Jumlah pembayaran harus positif.']);
            }

            $q = Invoice::withoutGlobalScopes()
                ->where('partner_id', $partnerId)
                ->lockForUpdate();

            if ($invoiceIds !== null && count($invoiceIds) > 0) {
                $ids = array_map('intval', $invoiceIds);
                $q->whereIn('id', $ids)
                    ->orderByRaw('FIELD(id, '.implode(',', $ids).')');
            } else {
                $q->orderBy('tgl_invoice')->orderBy('id');
            }

            $candidates = $q->get()->filter(fn (Invoice $i) => $i->saldo() > 0);

            if ($candidates->isEmpty()) {
                throw ValidationException::withMessages(['partner' => 'Tidak ada invoice terbuka untuk mitra ini.']);
            }

            $totalSaldo = (int) $candidates->sum(fn (Invoice $i) => $i->saldo());
            if ($jumlah > $totalSaldo) {
                // A-17: tolak, bukan catat deposit.
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah melebihi saldo seluruh invoice mitra (Rp'.number_format($totalSaldo, 0, ',', '.').').',
                ]);
            }

            $payment = Payment::create([
                'arah' => 'MASUK',
                'partner_id' => $partnerId,
                'metode' => $metode,
                'jumlah' => $jumlah,
                'referensi' => $referensi,
                'idempotency_key' => $idempotencyKey,
                'dicatat_oleh' => $oleh->id,
            ]);

            $sisa = $jumlah;
            foreach ($candidates as $inv) {
                if ($sisa <= 0) {
                    break;
                }
                $saldo = $inv->saldo();
                $ambil = min($sisa, $saldo);
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $inv->id,
                    'jumlah_alokasi' => $ambil,
                ]);
                $sisa -= $ambil;
            }

            AuditLogger::log('PEMBAYARAN_INVOICE', 'payments', $payment->id, null, [
                'jumlah' => $jumlah,
                'metode' => $metode,
                'referensi' => $referensi,
            ]);

            return $payment;
        });
    }
}

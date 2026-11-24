<?php

namespace App\Modules\BillingAccountsReceivable\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Models\InvoiceLine;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\PointOfSale\Models\HetConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueInvoiceAction
{
    /**
     * Q-D4 / BR-08: invoice terbit setelah pesanan DELIVERED.
     * Harga per kg diambil dari HET aktif bila komoditas punya HET, bila tidak pakai Rp10.000 placeholder (MVP).
     */
    public function execute(SalesOrder $order, User $oleh, bool $paksaLewatiBatasKredit = false): Invoice
    {
        if (! $oleh->hasRole('KEUANGAN', 'ADMIN', 'PEMILIK')) {
            abort(403, 'Hanya Keuangan/Admin/Pemilik yang boleh menerbitkan invoice.');
        }

        return DB::transaction(function () use ($order, $oleh, $paksaLewatiBatasKredit) {
            $o = SalesOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($o->status !== 'DELIVERED') {
                throw ValidationException::withMessages(['status' => 'Invoice hanya bisa terbit untuk pesanan DELIVERED.']);
            }
            if (Invoice::withoutGlobalScopes()->where('sales_order_id', $o->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Pesanan ini sudah punya invoice.']);
            }
            if (! $o->partner_id) {
                throw ValidationException::withMessages(['partner' => 'Pesanan tanpa mitra tidak bisa di-invoice (walk-in pakai nota kasir).']);
            }

            $partner = $o->partner;
            $termin = (int) ($partner->termin_hari ?: 14);
            $total = 0;
            $lines = [];

            foreach ($o->lines as $line) {
                $het = HetConfig::where('commodity_id', $line->commodity_id)->where('aktif', true)->first();
                $harga = $het ? (int) $het->harga_per_kg : 10000;
                $subtotal = (int) round((float) $line->qty_terpenuhi * $harga);
                $total += $subtotal;
                $lines[] = [
                    'commodity_id' => $line->commodity_id, 'grade_id' => $line->grade_id,
                    'qty_kg' => (float) $line->qty_terpenuhi, 'harga_per_kg' => $harga, 'subtotal' => $subtotal,
                ];
            }

            // BR-08 / TC-15: batas kredit = saldo piutang berjalan + total invoice baru.
            $saldoBerjalan = (int) Invoice::withoutGlobalScopes()->where('partner_id', $partner->id)
                ->get()->sum(fn (Invoice $i) => $i->saldo());
            $batas = (int) $partner->batas_kredit;

            if ($batas > 0 && $saldoBerjalan + $total > $batas && ! $paksaLewatiBatasKredit) {
                throw ValidationException::withMessages([
                    'kredit' => 'Melebihi batas kredit Rp'.number_format($batas, 0, ',', '.').'. Saldo berjalan Rp'.number_format($saldoBerjalan, 0, ',', '.').'. Butuh persetujuan.',
                ]);
            }

            $invoice = Invoice::create([
                'no' => $this->noInvoice(),
                'partner_id' => $partner->id,
                'sales_order_id' => $o->id,
                'location_id' => $o->location_id,
                'tgl_invoice' => today(),
                'jatuh_tempo' => today()->copy()->addDays($termin),
                'total' => $total,
                'dibuat_oleh' => $oleh->id,
                'disetujui' => $paksaLewatiBatasKredit ? false : true,
            ]);

            foreach ($lines as $l) {
                InvoiceLine::create(array_merge($l, ['invoice_id' => $invoice->id]));
            }

            AuditLogger::log('INVOICE_TERBIT', 'invoices', $invoice->id, null, [
                'no' => $invoice->no, 'total' => $total, 'jatuh_tempo' => $invoice->jatuh_tempo->toDateString(),
                'paksa_lewati_kredit' => $paksaLewatiBatasKredit,
            ]);

            return $invoice;
        });
    }

    private function noInvoice(): string
    {
        return sprintf('INV-%s-%03d', now()->format('ymd'), Invoice::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1);
    }
}

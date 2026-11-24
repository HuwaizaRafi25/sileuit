<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveOrderAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    public function execute(Shipment $shipment, User $oleh, float $diterimaKg, ?string $buktiPath = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $oleh, $diterimaKg, $buktiPath) {
            $s = Shipment::withoutGlobalScopes()->whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            if ($s->status !== 'DIKIRIM') {
                throw ValidationException::withMessages(['status' => 'Hanya pengiriman DIKIRIM yang bisa diterima.']);
            }

            $dikirim = (float) $s->jumlah_dikirim_kg;
            if ($diterimaKg < 0) {
                throw ValidationException::withMessages(['terima' => 'Jumlah diterima tidak boleh negatif.']);
            }
            if ($diterimaKg > $dikirim) {
                throw ValidationException::withMessages(['terima' => 'Jumlah diterima melebihi jumlah dikirim.']);
            }

            $kekurangan = round($dikirim - $diterimaKg, 3);
            $buktiId = null;
            if ($buktiPath) {
                $buktiId = Attachment::create(['path' => $buktiPath, 'diunggah_oleh' => $oleh->id])->id;
            }

            $o = SalesOrder::withoutGlobalScopes()->whereKey($s->sales_order_id)->lockForUpdate()->firstOrFail();

            // Q-D1 / DEV-003: pesanan ke cabang sendiri masuk ledger tujuan saat DELIVERED.
            if ($o->lokasi_tujuan_id && $diterimaKg > 0) {
                $sisa = $diterimaKg;
                $reservations = Reservation::whereIn('sales_order_line_id', $o->lines()->pluck('id'))->where('status', 'TERPENUHI')->get();
                foreach ($reservations as $r) {
                    if ($sisa <= 0) {
                        break;
                    }
                    $ambil = round(min($sisa, (float) $r->qty_kg), 3);
                    $this->ledger->post($r->lot, $o->lokasi_tujuan_id, 'TERIMA_CABANG', $ambil, 'SHIPMENT', $s->id, $oleh);
                    $sisa = round($sisa - $ambil, 3);
                }
            }

            $s->update([
                'status' => 'DITERIMA', 'jumlah_diterima_kg' => $diterimaKg, 'kekurangan_kg' => $kekurangan,
                'waktu_terima' => now(), 'bukti_attachment_id' => $buktiId,
            ]);
            $o->update(['status' => 'DELIVERED']);

            AuditLogger::log('PENGIRIMAN_DITERIMA', 'shipments', $s->id, ['status' => 'DIKIRIM'],
                ['status' => 'DITERIMA', 'kekurangan_kg' => $kekurangan]);

            return $s->fresh();
        });
    }
}

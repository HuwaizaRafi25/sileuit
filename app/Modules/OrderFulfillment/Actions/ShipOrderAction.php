<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipOrderAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    /** BR-15 / Q-D1: stok keluar dari ledger gudang asal saat SHIPPED. */
    public function execute(Shipment $shipment, User $oleh): Shipment
    {
        return DB::transaction(function () use ($shipment, $oleh) {
            $s = Shipment::withoutGlobalScopes()->whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            if ($s->status !== 'MENUNGGU') {
                throw ValidationException::withMessages(['status' => 'Hanya pengiriman MENUNGGU yang bisa dikirim.']);
            }

            $o = SalesOrder::withoutGlobalScopes()->whereKey($s->sales_order_id)->lockForUpdate()->firstOrFail();
            if ($o->status !== 'PICKED') {
                throw ValidationException::withMessages(['status' => 'Pesanan belum di-picking.']);
            }

            $reservations = Reservation::whereIn('sales_order_line_id', $o->lines()->pluck('id'))->where('status', 'AKTIF')->get();
            if ($reservations->isEmpty()) {
                throw ValidationException::withMessages(['reservasi' => 'Tidak ada reservasi aktif untuk dikirim.']);
            }

            foreach ($reservations as $r) {
                $qty = (float) $r->qty_kg;
                $this->ledger->post($r->lot, $r->location_id, 'KIRIM', -$qty, 'SHIPMENT', $s->id, $oleh, -$qty);
                $r->update(['status' => 'TERPENUHI']);
            }

            $s->update(['status' => 'DIKIRIM', 'kurir_id' => $oleh->id, 'waktu_kirim' => now()]);
            $o->update(['status' => 'SHIPPED']);
            AuditLogger::log('PENGIRIMAN_DIKIRIM', 'shipments', $s->id, ['status' => 'MENUNGGU'], ['status' => 'DIKIRIM']);

            return $s->fresh();
        });
    }
}

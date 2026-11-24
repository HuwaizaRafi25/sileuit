<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\PointOfSale\Models\PosSale;
use App\Modules\PointOfSale\Models\PosSaleLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveReturnAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    public function execute(ReturnDoc $retur, User $oleh, string $hasil): ReturnDoc
    {
        return DB::transaction(function () use ($retur, $oleh, $hasil) {
            $r = ReturnDoc::withoutGlobalScopes()->whereKey($retur->id)->lockForUpdate()->firstOrFail();
            if ($r->status !== 'APPROVED') {
                throw ValidationException::withMessages(['status' => 'Retur belum disetujui.']);
            }
            if (! in_array($hasil, ['STOK_KEMBALI', 'KARANTINA', 'DIHAPUS'], true)) {
                throw ValidationException::withMessages(['hasil' => 'Hasil retur tidak valid.']);
            }

            if ($hasil === 'STOK_KEMBALI') {
                $this->kembalikanStok($r, $oleh);
            }

            $r->update(['status' => 'RECEIVED', 'hasil' => $hasil]);
            AuditLogger::log('RETUR_DITERIMA', 'return_docs', $r->id, ['status' => 'APPROVED'],
                ['status' => 'RECEIVED', 'hasil' => $hasil]);

            return $r->fresh();
        });
    }

    /** DEV-024: kredit balik terbaik-effort ke lot asal. */
    private function kembalikanStok(ReturnDoc $r, User $oleh): void
    {
        $lot = null;
        $lokasi = $r->location_id;

        if ($r->ref_tipe === 'SHIPMENT') {
            $s = Shipment::withoutGlobalScopes()->find($r->ref_id);
            $o = $s ? SalesOrder::withoutGlobalScopes()->find($s->sales_order_id) : null;
            $res = $o ? Reservation::whereIn('sales_order_line_id', $o->lines()->pluck('id'))->where('status', 'TERPENUHI')->first() : null;
            if ($res) {
                $lot = $res->lot;
                $lokasi = $res->location_id;
            }
        } else {
            $line = PosSaleLine::where('pos_sale_id', $r->ref_id)->first();
            if ($line) {
                $lot = $line->lot;
                $lokasi = PosSale::withoutGlobalScopes()->find($r->ref_id)?->location_id ?? $lokasi;
            }
        }

        if ($lot) {
            $this->ledger->post($lot, $lokasi, 'RETUR_MASUK', (float) $r->kg, 'RETURN', $r->id, $oleh);
        }
    }
}

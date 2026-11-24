<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Services\FefoAllocationEngine;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocateOrderStockAction
{
    public function __construct(private FefoAllocationEngine $fefo) {}

    public function execute(SalesOrder $order, User $oleh, bool $izinkanKedaluwarsa = false): SalesOrder
    {
        if ($izinkanKedaluwarsa && ! $oleh->hasRole('KEPALA_GUDANG')) {
            throw ValidationException::withMessages(['lot' => 'Pemakaian lot kedaluwarsa hanya boleh disetujui Kepala Gudang (BR-04).']);
        }

        return DB::transaction(function () use ($order, $oleh, $izinkanKedaluwarsa) {
            $o = SalesOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($o->status, ['NEW', 'PARTIAL', 'BACKORDER'], true)) {
                throw ValidationException::withMessages(['status' => 'Pesanan ini tidak bisa dialokasikan lagi.']);
            }

            $totalSisa = 0.0;
            $totalTerpenuhi = 0.0;

            foreach ($o->lines as $line) {
                $sisa = $line->sisaKg();
                $totalSisa += $sisa;
                if ($sisa <= 0) {
                    continue;
                }

                $picks = $this->fefo->pick($line->commodity_id, $line->grade_id, $o->location_id, $sisa, $izinkanKedaluwarsa);

                foreach ($picks as $pick) {
                    $b = StockBalance::where('lot_id', $pick['lot_id'])->where('location_id', $o->location_id)->lockForUpdate()->first();
                    $tersedia = round((float) $b->on_hand_kg - (float) $b->reserved_kg, 3);
                    $ambil = round(min($pick['qty'], $tersedia), 3);
                    if ($ambil <= 0) {
                        continue;
                    }

                    StockBalance::where('lot_id', $pick['lot_id'])->where('location_id', $o->location_id)
                        ->update(['reserved_kg' => round((float) $b->reserved_kg + $ambil, 3), 'updated_at' => now()]);

                    Reservation::create([
                        'sales_order_line_id' => $line->id,
                        'lot_id' => $pick['lot_id'],
                        'location_id' => $o->location_id,
                        'qty_kg' => $ambil,
                        'status' => 'AKTIF',
                        'override_kedaluwarsa' => $izinkanKedaluwarsa,
                        'disetujui_oleh' => $izinkanKedaluwarsa ? $oleh->id : null,
                    ]);

                    $line->update(['qty_terpenuhi' => round((float) $line->qty_terpenuhi + $ambil, 3)]);
                    $totalTerpenuhi += $ambil;
                }
            }

            $status = $totalTerpenuhi <= 0 ? 'BACKORDER' : ($totalTerpenuhi < $totalSisa ? 'PARTIAL' : 'ALLOCATED');
            $o->update(['status' => $status]);

            AuditLogger::log('ALOKASI_FEFO', 'sales_orders', $o->id, null, [
                'status' => $status, 'terpenuhi_kg' => $totalTerpenuhi, 'override_kedaluwarsa' => $izinkanKedaluwarsa,
            ]);

            return $o->fresh();
        });
    }
}

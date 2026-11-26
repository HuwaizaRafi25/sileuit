<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PickOrderAction
{
    public function execute(SalesOrder $order, User $oleh): Shipment
    {
        return DB::transaction(function () use ($order) {
            $o = SalesOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($o->status, ['ALLOCATED', 'PARTIAL'], true)) {
                throw ValidationException::withMessages(['status' => 'Hanya pesanan ALLOCATED atau PARTIAL yang bisa di-picking.']);
            }
            $total = (float) $o->lines()->sum('qty_terpenuhi');
            if ($total <= 0) {
                throw ValidationException::withMessages(['alokasi' => 'Belum ada alokasi stok untuk pesanan ini.']);
            }

            $shipment = Shipment::create([
                'no_dok' => $this->noDok(),
                'sales_order_id' => $o->id,
                'status' => 'MENUNGGU',
                'jumlah_dikirim_kg' => $total,
            ]);

            $statusLama = $o->status;
            $o->update(['status' => 'PICKED']);
            AuditLogger::log('PESANAN_PICKING', 'sales_orders', $o->id, ['status' => $statusLama], ['status' => 'PICKED', 'surat_jalan' => $shipment->no_dok]);

            return $shipment;
        });
    }

    private function noDok(): string
    {
        $prefix = sprintf('SJ-%s-', now()->format('ymd'));
        $urut = Shipment::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1;
        while (Shipment::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        return sprintf('%s%03d', $prefix, $urut);
    }
}

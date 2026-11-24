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

            if ($o->status !== 'ALLOCATED') {
                throw ValidationException::withMessages(['status' => 'Hanya pesanan ALLOCATED yang bisa di-picking.']);
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

            $o->update(['status' => 'PICKED']);
            AuditLogger::log('PESANAN_PICKING', 'sales_orders', $o->id, ['status' => 'ALLOCATED'], ['status' => 'PICKED', 'surat_jalan' => $shipment->no_dok]);

            return $shipment;
        });
    }

    private function noDok(): string
    {
        return sprintf('SJ-%s-%03d', now()->format('ymd'), Shipment::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1);
    }
}

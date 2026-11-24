<?php

namespace App\Modules\InventoryLedger\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipTransferAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    public function execute(StockTransfer $transfer, User $oleh): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $oleh) {
            $t = StockTransfer::withoutGlobalScopes()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($t->status !== 'DRAFT') {
                throw ValidationException::withMessages(['status' => 'Hanya transfer DRAFT yang bisa dikirim.']);
            }
            if ($t->lines()->count() === 0) {
                throw ValidationException::withMessages(['lines' => 'Transfer belum punya baris barang.']);
            }

            foreach ($t->lines as $line) {
                // BR-15: keluar dari asal saat SHIPPED. StockLedgerWriter menolak saldo negatif (TC-28).
                $this->ledger->post($line->lot, $t->dari_lokasi, 'TRANSFER_KELUAR', -(float) $line->kg_kirim, 'TRANSFER', $t->id, $oleh);
            }

            $t->update(['status' => 'SHIPPED', 'dikirim_oleh' => $oleh->id, 'waktu_kirim' => now()]);
            AuditLogger::log('TRANSFER_DIKIRIM', 'stock_transfers', $t->id, ['status' => 'DRAFT'], ['status' => 'SHIPPED']);

            return $t->fresh();
        });
    }
}

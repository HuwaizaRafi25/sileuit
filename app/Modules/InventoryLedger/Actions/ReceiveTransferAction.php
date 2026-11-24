<?php

namespace App\Modules\InventoryLedger\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveTransferAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    /** @param array<int, float> $terimaPerLine keyed by transfer_line id */
    public function execute(StockTransfer $transfer, User $oleh, array $terimaPerLine): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $oleh, $terimaPerLine) {
            $t = StockTransfer::withoutGlobalScopes()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($t->status !== 'SHIPPED') {
                throw ValidationException::withMessages(['status' => 'Hanya transfer SHIPPED yang bisa diterima.']);
            }

            $adaSelisih = false;

            foreach ($t->lines as $line) {
                if (! array_key_exists($line->id, $terimaPerLine)) {
                    throw ValidationException::withMessages(['terima' => 'Semua baris harus diisi jumlah diterimanya.']);
                }
                $kg = round((float) $terimaPerLine[$line->id], 3);
                if ($kg < 0) {
                    throw ValidationException::withMessages(['terima' => 'Jumlah diterima tidak boleh negatif.']);
                }

                $selisih = round((float) $line->kg_kirim - $kg, 3);
                $line->update(['kg_terima' => $kg, 'selisih_kg' => $selisih]);
                if ($selisih !== 0.0) {
                    $adaSelisih = true;
                }

                if ($kg > 0) {
                    $this->ledger->post($line->lot, $t->ke_lokasi, 'TRANSFER_MASUK', $kg, 'TRANSFER', $t->id, $oleh);
                }
            }

            $t->update([
                'status' => $adaSelisih ? 'DISCREPANCY' : 'RECEIVED',
                'diterima_oleh' => $oleh->id,
                'waktu_terima' => now(),
            ]);

            AuditLogger::log('TRANSFER_DITERIMA', 'stock_transfers', $t->id, ['status' => 'SHIPPED'],
                ['status' => $t->status, 'selisih' => $adaSelisih]);

            return $t->fresh();
        });
    }
}

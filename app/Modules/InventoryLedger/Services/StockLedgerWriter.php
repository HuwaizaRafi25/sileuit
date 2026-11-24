<?php

namespace App\Modules\InventoryLedger\Services;

use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use Illuminate\Validation\ValidationException;

class StockLedgerWriter
{
    /**
     * BR-03 / IR-05 / IR-09: satu-satunya jalan mengubah stok.
     * Wajib dipanggil dalam transaksi. $reservedDelta dipakai saat kirim (melepas reservasi).
     */
    public function post(Lot $lot, int $locationId, string $jenis, float $qty, string $refTipe, int $refId, User $oleh, float $reservedDelta = 0.0): StockLedger
    {
        if (round($qty, 3) === 0.0) {
            throw ValidationException::withMessages(['qty' => 'Kuantitas mutasi tidak boleh nol (IR-04).']);
        }

        StockBalance::firstOrCreate(
            ['lot_id' => $lot->id, 'location_id' => $locationId],
            ['on_hand_kg' => 0, 'reserved_kg' => 0]
        );

        $balance = StockBalance::where('lot_id', $lot->id)->where('location_id', $locationId)->lockForUpdate()->first();
        $baruOnHand = round((float) $balance->on_hand_kg + $qty, 3);
        $baruReserved = round((float) $balance->reserved_kg + $reservedDelta, 3);

        if ($baruOnHand < 0) {
            throw ValidationException::withMessages(['qty' => 'Stok tidak cukup. Tersedia '.number_format((float) $balance->on_hand_kg, 3, ',', '.').' kg.']);
        }
        if ($baruReserved < 0) {
            throw ValidationException::withMessages(['qty' => 'Reservasi tidak valid.']);
        }
        if ($baruReserved > $baruOnHand) {
            throw ValidationException::withMessages(['qty' => 'Reservasi melebihi stok on-hand.']);
        }

        StockBalance::where('lot_id', $lot->id)->where('location_id', $locationId)
            ->update(['on_hand_kg' => $baruOnHand, 'reserved_kg' => $baruReserved, 'updated_at' => now()]);

        return StockLedger::create([
            'lot_id' => $lot->id,
            'location_id' => $locationId,
            'jenis_mutasi' => $jenis,
            'qty_kg' => $qty,
            'ref_tipe' => $refTipe,
            'ref_id' => $refId,
            'waktu' => now(),
            'oleh' => $oleh->id,
        ]);
    }
}

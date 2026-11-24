<?php

namespace App\Modules\InventoryLedger\Services;

use App\Modules\InventoryLedger\Models\StockBalance;
use Illuminate\Support\Collection;

class FefoAllocationEngine
{
    /**
     * BR-04 / IR-16: urutan FEFO = batas_simpan paling awal, lalu tgl_terima paling lama.
     * Lot KARANTINA/HABIS tidak pernah ikut; lot kedaluwarsa hanya bila diizinkan Kepala Gudang.
     *
     * @return array<int, array{lot_id:int, qty:float}>
     */
    public function pick(int $commodityId, int $gradeId, int $locationId, float $qty, bool $izinkanKedaluwarsa = false): array
    {
        $status = $izinkanKedaluwarsa ? ['TERSEDIA', 'KEDALUWARSA'] : ['TERSEDIA'];

        $rows = StockBalance::query()
            ->join('lots', 'lots.id', '=', 'stock_balances.lot_id')
            ->where('stock_balances.location_id', $locationId)
            ->where('lots.commodity_id', $commodityId)
            ->where('lots.grade_id', $gradeId)
            ->whereIn('lots.status', $status)
            ->when(! $izinkanKedaluwarsa, fn ($q) => $q->whereDate('lots.batas_simpan', '>=', today()))
            ->whereRaw('stock_balances.on_hand_kg - stock_balances.reserved_kg > 0')
            ->orderBy('lots.batas_simpan')
            ->orderBy('lots.tgl_terima')
            ->orderBy('lots.id')
            ->select('stock_balances.lot_id', 'stock_balances.on_hand_kg', 'stock_balances.reserved_kg')
            ->get();

        return $this->bagi($rows, $qty);
    }

    private function bagi(Collection $rows, float $qty): array
    {
        $sisa = round($qty, 3);
        $hasil = [];

        foreach ($rows as $r) {
            if ($sisa <= 0) {
                break;
            }
            $tersedia = round((float) $r->on_hand_kg - (float) $r->reserved_kg, 3);
            $ambil = round(min($sisa, $tersedia), 3);
            if ($ambil > 0) {
                $hasil[] = ['lot_id' => (int) $r->lot_id, 'qty' => $ambil];
                $sisa = round($sisa - $ambil, 3);
            }
        }

        return $hasil;
    }
}

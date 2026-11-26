<?php

namespace App\Modules\InventoryLedger\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveAdjustmentAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    public function execute(StockAdjustment $adj, User $oleh, bool $setuju): StockAdjustment
    {
        $boleh = $adj->tipe === 'SALDO_AWAL'
            ? $oleh->hasRole('PEMILIK')
            : $oleh->hasRole('KEPALA_GUDANG') && $oleh->locationScopes->contains('id', $adj->location_id);

        if (! $boleh) {
            abort(403, 'Anda tidak berwenang menyetujui penyesuaian ini.');
        }

        return DB::transaction(function () use ($adj, $oleh, $setuju) {
            $a = StockAdjustment::withoutGlobalScopes()->whereKey($adj->id)->lockForUpdate()->firstOrFail();

            if ($a->status !== 'MENUNGGU') {
                throw ValidationException::withMessages(['status' => 'Penyesuaian ini sudah diproses.']);
            }
            if (trim($a->alasan) === '') {
                throw ValidationException::withMessages(['alasan' => 'Alasan penyesuaian wajib diisi (FR-19).']);
            }

            if (! $setuju) {
                $a->update(['status' => 'DITOLAK', 'disetujui_oleh' => $oleh->id]);
                AuditLogger::log('PENYESUAIAN_DITOLAK', 'stock_adjustments', $a->id, null, ['alasan' => $a->alasan]);

                return $a->fresh();
            }

            if ($a->tipe === 'SALDO_AWAL') {
                $lot = Lot::create([
                    'kode_lot' => $this->kodeSaldoAwal($a),
                    'commodity_id' => $a->commodity_id,
                    'grade_id' => $a->grade_id,
                    'receipt_id' => null,
                    'tgl_terima' => $a->tanggal->toDateString(),
                    'batas_simpan' => $a->tanggal->copy()->addDays((int) $a->commodity->umur_simpan_hari)->toDateString(),
                    'status' => 'TERSEDIA',
                ]);
                $a->update(['lot_id' => $lot->id]);
                $jenis = 'SALDO_AWAL';
            } else {
                $lot = $a->lot;
                $jenis = 'PENYESUAIAN';
            }

            $this->ledger->post($lot, $a->location_id, $jenis, (float) $a->qty_kg, 'ADJUSTMENT', $a->id, $oleh);

            $a->update(['status' => 'DISETUJUI', 'disetujui_oleh' => $oleh->id]);
            AuditLogger::log('PENYESUAIAN_DISETUJUI', 'stock_adjustments', $a->id, ['status' => 'MENUNGGU'],
                ['status' => 'DISETUJUI', 'qty_kg' => $a->qty_kg, 'kode_lot' => $lot->kode_lot]);

            return $a->fresh();
        });
    }

    private function kodeSaldoAwal(StockAdjustment $a): string
    {
        $tgl = $a->tanggal->format('ymd');
        $kodeLok = Location::whereKey($a->location_id)->value('kode');
        $prefix = sprintf('LOT-%s-%s-SA', $kodeLok, $tgl);
        $urut = Lot::where('kode_lot', 'like', $prefix.'%')->count() + 1;
        while (Lot::where('kode_lot', sprintf('%s%02d', $prefix, $urut))->exists()) {
            $urut++;
        }

        return sprintf('%s%02d', $prefix, $urut);
    }
}

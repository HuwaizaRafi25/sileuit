<?php

namespace App\Modules\ProcurementReceiving\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Receipt;
use App\Modules\ProcurementReceiving\Services\PriceResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmReceiptAction
{
    public function __construct(
        private PriceResolver $harga,
        private StockLedgerWriter $ledger,
    ) {}

    public function execute(Receipt $receipt, User $oleh): Receipt
    {
        return DB::transaction(function () use ($receipt, $oleh) {
            $r = Receipt::withoutGlobalScopes()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($r->status !== 'INSPECTED') {
                throw ValidationException::withMessages(['status' => 'Hanya penerimaan berstatus INSPECTED yang bisa dikonfirmasi.']);
            }
            if ($r->butuhPersetujuanSelisih() && ! $r->selisih_disetujui_oleh) {
                throw ValidationException::withMessages(['selisih' => 'Selisih di luar toleransi. Butuh persetujuan Kepala Gudang (BR-05).']);
            }

            $insp = $r->inspectionTerakhir();
            if (! $insp) {
                throw ValidationException::withMessages(['inspeksi' => 'Inspeksi mutu belum dilakukan.']);
            }

            if ($insp->keputusan === 'TOLAK') {
                $r->update(['status' => 'REJECTED']);
                AuditLogger::log('PENERIMAAN_DITOLAK', 'receipts', $r->id, null, ['alasan' => $insp->alasan]);

                return $r->fresh();
            }

            $karantina = $insp->keputusan === 'KARANTINA';
            $commodityId = $insp->grade?->commodity_id ?? Commodity::where('nama', 'GABAH')->value('id');
            $hargaPerKg = null;
            $nilai = 0;

            if (! $karantina) {
                $item = $this->harga->resolve($commodityId, $insp->grade_id, $r->waktu_timbang);
                $hargaPerKg = $item->harga_per_kg;
                $nilai = (int) round((float) $r->netto_kg * $hargaPerKg);
            }

            $r->update([
                'harga_per_kg' => $hargaPerKg,
                'nilai_bayar' => $nilai,
                'status' => 'CONFIRMED',
            ]);

            $umur = (int) Commodity::whereKey($commodityId)->value('umur_simpan_hari');

            $lot = Lot::create([
                'kode_lot' => $this->kodeLot($r),
                'commodity_id' => $commodityId,
                'grade_id' => $insp->grade_id,
                'receipt_id' => $r->id,
                'tgl_panen' => $r->harvestPlan?->tgl_perkiraan,
                'tgl_terima' => $r->waktu_timbang->toDateString(),
                'batas_simpan' => $r->waktu_timbang->copy()->addDays($umur)->toDateString(),
                'status' => $karantina ? 'KARANTINA' : 'TERSEDIA',
            ]);

            $this->ledger->post($lot, $r->location_id, 'TERIMA', (float) $r->netto_kg, 'RECEIPT', $r->id, $oleh);

            AuditLogger::log('PENERIMAAN_KONFIRMASI', 'receipts', $r->id, ['status' => 'INSPECTED'], [
                'status' => 'CONFIRMED', 'kode_lot' => $lot->kode_lot, 'nilai_bayar' => $nilai,
            ]);

            return $r->fresh();
        });
    }

    private function kodeLot(Receipt $r): string
    {
        $tgl = $r->waktu_timbang->format('ymd');
        $urut = Lot::where('kode_lot', 'like', "LOT-{$r->location->kode}-{$tgl}-%")->count() + 1;

        return sprintf('LOT-%s-%s-%02d', $r->location->kode, $tgl, $urut);
    }
}

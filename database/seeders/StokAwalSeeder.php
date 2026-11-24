<?php

namespace Database\Seeders;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StokAwalSeeder extends Seeder
{
    public function run(): void
    {
        $g1 = Location::where('kode', 'G1')->first();
        $gabah = Commodity::where('nama', 'GABAH')->first();
        $gradeA = Grade::where('commodity_id', $gabah->id)->where('kode', 'A')->first();
        $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
        $writer = app(StockLedgerWriter::class);

        foreach ([['LOT-G1-261001-01', '2026-10-01'], ['LOT-G1-261101-01', '2026-11-01']] as [$kode, $tgl]) {
            if (Lot::where('kode_lot', $kode)->exists()) {
                continue;
            }
            $lot = Lot::create([
                'kode_lot' => $kode, 'commodity_id' => $gabah->id, 'grade_id' => $gradeA->id, 'receipt_id' => null,
                'tgl_terima' => $tgl, 'batas_simpan' => Carbon::parse($tgl)->addDays($gabah->umur_simpan_hari)->toDateString(),
                'status' => 'TERSEDIA',
            ]);
            DB::transaction(fn () => $writer->post($lot, $g1->id, 'SALDO_AWAL', 500, 'ADJUSTMENT', 0, $pemilik));
        }
    }
}

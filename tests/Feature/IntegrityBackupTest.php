<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->seed());

it('TC-17: invariant integritas lulus setelah transaksi stok', function () {
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
    $lot = Lot::create([
        'kode_lot' => 'LOT-INT-01', 'commodity_id' => $c->id, 'grade_id' => $g->id, 'receipt_id' => null,
        'tgl_terima' => '2026-11-01', 'batas_simpan' => '2027-05-01', 'status' => 'TERSEDIA',
    ]);
    $u = User::where('email', 'pemilik-1@sileuit.id')->first();
    DB::transaction(fn () => app(StockLedgerWriter::class)->post($lot, Location::where('kode', 'G1')->value('id'), 'SALDO_AWAL', 250, 'ADJUSTMENT', 0, $u));

    $this->artisan('sileuit:verifikasi-integritas')->assertSuccessful();
});

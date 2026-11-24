<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Actions\PickOrderAction;
use App\Modules\OrderFulfillment\Actions\ReceiveOrderAction;
use App\Modules\OrderFulfillment\Actions\ShipOrderAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function stokLok(string $kode, string $tgl, float $kg, string $lok = 'G1', string $kom = 'GABAH', string $grade = 'A'): Lot
{
    $c = Commodity::where('nama', $kom)->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', $grade)->first();
    $lot = Lot::create([
        'kode_lot' => $kode, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'receipt_id' => null,
        'tgl_terima' => $tgl, 'batas_simpan' => Carbon::parse($tgl)->addDays($c->umur_simpan_hari)->toDateString(),
        'status' => 'TERSEDIA',
    ]);
    $u = User::where('email', 'pemilik-1@sileuit.id')->first();
    $l = Location::where('kode', $lok)->first();
    DB::transaction(fn () => app(StockLedgerWriter::class)->post($lot, $l->id, 'SALDO_AWAL', $kg, 'ADJUSTMENT', 0, $u));

    return $lot;
}

function pesananSiap(float $kg, string $lok = 'G1', ?string $tujuan = null): SalesOrder
{
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $o = SalesOrder::withoutGlobalScopes()->create([
        'no_dok' => 'SO-F4-'.uniqid(), 'location_id' => Location::where('kode', $lok)->value('id'),
        'lokasi_tujuan_id' => $tujuan ? Location::where('kode', $tujuan)->value('id') : null,
        'status' => 'NEW', 'dibuat_oleh' => $adm->id,
    ]);
    SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'qty_kg' => $kg]);

    return app(AllocateOrderStockAction::class)->execute($o, $adm);
}

it('alur penuh: alokasi → pick → kirim → terima tanpa kekurangan', function () {
    stokLok('LOT-F4-01', '2026-11-01', 1000);
    $o = pesananSiap(100);
    $kurir = User::where('email', 'kur-1@sileuit.id')->first();

    $sj = app(PickOrderAction::class)->execute($o, $kurir);
    expect($o->fresh()->status)->toBe('PICKED')->and($sj->status)->toBe('MENUNGGU');

    app(ShipOrderAction::class)->execute($sj, $kurir);
    $g1 = Location::where('kode', 'G1')->value('id');
    expect($o->fresh()->status)->toBe('SHIPPED')
        ->and($sj->fresh()->status)->toBe('DIKIRIM')
        ->and((float) StockBalance::where('location_id', $g1)->value('on_hand_kg'))->toBe(900.0)
        ->and((float) StockBalance::where('location_id', $g1)->value('reserved_kg'))->toBe(0.0)
        ->and(StockLedger::where('jenis_mutasi', 'KIRIM')->exists())->toBeTrue();

    $hasil = app(ReceiveOrderAction::class)->execute($sj->fresh(), $kurir, 100);
    expect($hasil->status)->toBe('DITERIMA')->and((float) $hasil->kekurangan_kg)->toBe(0.0)
        ->and($o->fresh()->status)->toBe('DELIVERED');
});

it('kekurangan saat terima tercatat dan membuka alur retur', function () {
    stokLok('LOT-F4-02', '2026-11-01', 500);
    $o = pesananSiap(100);
    $kurir = User::where('email', 'kur-1@sileuit.id')->first();
    $sj = app(PickOrderAction::class)->execute($o, $kurir);
    app(ShipOrderAction::class)->execute($sj, $kurir);

    $hasil = app(ReceiveOrderAction::class)->execute($sj->fresh(), $kurir, 90);
    expect($hasil->status)->toBe('DITERIMA')->and((float) $hasil->kekurangan_kg)->toBe(10.0);
});

it('pesanan ke cabang sendiri menambah stok tujuan saat DELIVERED (Q-D1)', function () {
    stokLok('LOT-F4-03', '2026-11-01', 500, 'G1');
    $o = pesananSiap(100, 'G1', 'C1');
    $kurir = User::where('email', 'kur-1@sileuit.id')->first();
    $sj = app(PickOrderAction::class)->execute($o, $kurir);
    app(ShipOrderAction::class)->execute($sj, $kurir);
    app(ReceiveOrderAction::class)->execute($sj->fresh(), $kurir, 100);

    $c1 = Location::where('kode', 'C1')->value('id');
    expect((float) StockBalance::where('location_id', $c1)->value('on_hand_kg'))->toBe(100.0)
        ->and(StockLedger::where('jenis_mutasi', 'TERIMA_CABANG')->exists())->toBeTrue();
});

it('diterima melebihi dikirim ditolak', function () {
    stokLok('LOT-F4-04', '2026-11-01', 200);
    $o = pesananSiap(100);
    $kurir = User::where('email', 'kur-1@sileuit.id')->first();
    $sj = app(PickOrderAction::class)->execute($o, $kurir);
    app(ShipOrderAction::class)->execute($sj, $kurir);
    expect(fn () => app(ReceiveOrderAction::class)->execute($sj->fresh(), $kurir, 150))
        ->toThrow(ValidationException::class);
});

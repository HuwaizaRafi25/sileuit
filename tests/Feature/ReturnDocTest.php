<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Actions\ApproveReturnAction;
use App\Modules\OrderFulfillment\Actions\PickOrderAction;
use App\Modules\OrderFulfillment\Actions\ReceiveReturnAction;
use App\Modules\OrderFulfillment\Actions\RequestReturnAction;
use App\Modules\OrderFulfillment\Actions\ShipOrderAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function kirimPaket(float $kg): Shipment
{
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
    $lot = Lot::create([
        'kode_lot' => 'LOT-RET-'.uniqid(), 'commodity_id' => $c->id, 'grade_id' => $g->id, 'receipt_id' => null,
        'tgl_terima' => '2026-11-01', 'batas_simpan' => '2027-05-01', 'status' => 'TERSEDIA',
    ]);
    $u = User::where('email', 'pemilik-1@sileuit.id')->first();
    DB::transaction(fn () => app(StockLedgerWriter::class)->post($lot, Location::where('kode', 'G1')->value('id'), 'SALDO_AWAL', $kg, 'ADJUSTMENT', 0, $u));

    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $o = SalesOrder::withoutGlobalScopes()->create([
        'no_dok' => 'SO-RET-'.uniqid(), 'location_id' => Location::where('kode', 'G1')->value('id'),
        'status' => 'NEW', 'dibuat_oleh' => $adm->id,
    ]);
    SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'qty_kg' => $kg]);
    $o = app(AllocateOrderStockAction::class)->execute($o, $adm);
    $sj = app(PickOrderAction::class)->execute($o, $u);
    app(ShipOrderAction::class)->execute($sj, $u);

    return $sj->fresh();
}

it('TC-12: retur melebihi kuantitas terkirim ditolak', function () {
    $sj = kirimPaket(50);
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    expect(fn () => app(RequestReturnAction::class)->execute('SHIPMENT', $sj->id, 60, $kasir, 'Barang rusak', Location::where('kode', 'G1')->value('id')))
        ->toThrow(ValidationException::class);
});

it('TC-23: retur tanpa referensi ditolak', function () {
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    expect(fn () => app(RequestReturnAction::class)->execute(null, null, 10, $kasir, 'Tanpa ref', Location::where('kode', 'G1')->value('id')))
        ->toThrow(ValidationException::class);
});

it('TC-24: retur pertama 20 kg (karantina) berhasil, retur kedua 40 kg ditolak', function () {
    $sj = kirimPaket(50);
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $kg = User::where('email', 'kg-g1@sileuit.id')->first();
    $lok = Location::where('kode', 'G1')->value('id');

    $r1 = app(RequestReturnAction::class)->execute('SHIPMENT', $sj->id, 20, $kasir, 'Beras apek', $lok);
    $r1 = app(ApproveReturnAction::class)->execute($r1, $kg, true);
    expect($r1->status)->toBe('APPROVED');
    $r1 = app(ReceiveReturnAction::class)->execute($r1, $kg, 'KARANTINA');
    expect($r1->status)->toBe('RECEIVED')->and($r1->hasil)->toBe('KARANTINA');

    expect(fn () => app(RequestReturnAction::class)->execute('SHIPMENT', $sj->id, 40, $kasir, 'Ajukan lagi', $lok))
        ->toThrow(ValidationException::class);
});

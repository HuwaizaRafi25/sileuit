<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    require_once base_path('tests/Feature/StockTransferTest.php');
});

function pesananBaru(float $kg, string $lok = 'G1'): SalesOrder
{
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
    $o = SalesOrder::withoutGlobalScopes()->create([
        'no_dok' => 'SO-'.uniqid(), 'location_id' => Location::where('kode', $lok)->value('id'),
        'status' => 'NEW', 'dibuat_oleh' => User::where('email', 'adm-1@sileuit.id')->value('id'),
    ]);
    SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'qty_kg' => $kg]);

    return $o;
}

it('TC-10: alokasi 100 kg memakai lot 1 Okt lebih dulu dan menahan reservasi', function () {
    $lama = lotDenganStok('LOT-G1-261001-01', '2026-10-01', 500);
    $baru = lotDenganStok('LOT-G1-261101-01', '2026-11-01', 500);
    $adm = User::where('email', 'adm-1@sileuit.id')->first();

    $o = app(AllocateOrderStockAction::class)->execute(pesananBaru(100), $adm);

    expect($o->status)->toBe('ALLOCATED');
    $r = Reservation::first();
    expect($r->lot_id)->toBe($lama->id)->and((float) $r->qty_kg)->toBe(100.0);
    expect((float) StockBalance::where('lot_id', $lama->id)->value('reserved_kg'))->toBe(100.0)
        ->and((float) StockBalance::where('lot_id', $baru->id)->value('reserved_kg'))->toBe(0.0);
});

it('stok kurang dialokasikan sebagian dan sisanya BACKORDER/PARTIAL (FR-09)', function () {
    lotDenganStok('LOT-FEFO-P1', '2026-10-01', 300);
    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $o = app(AllocateOrderStockAction::class)->execute(pesananBaru(500), $adm);
    expect($o->status)->toBe('PARTIAL')
        ->and((float) $o->lines()->first()->qty_terpenuhi)->toBe(300.0);
});

it('TC-27: lot kedaluwarsa dilewati alokasi normal, dipakai hanya dengan persetujuan Kepala Gudang', function () {
    $tua = lotDenganStok('LOT-FEFO-EXP', '2026-05-01', 500);
    $segar = lotDenganStok('LOT-FEFO-NEW', '2026-11-20', 500);
    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $kg = User::where('email', 'kg-g1@sileuit.id')->first();

    $o1 = app(AllocateOrderStockAction::class)->execute(pesananBaru(100), $adm);
    expect($o1->lines()->first()->reservations()->first()->lot_id)->toBe($segar->id);

    $o2 = app(AllocateOrderStockAction::class)->execute(pesananBaru(100), $kg, true);
    $r2 = $o2->lines()->first()->reservations()->first();
    expect($r2->lot_id)->toBe($tua->id)->and($r2->override_kedaluwarsa)->toBeTrue();

    expect(fn () => app(AllocateOrderStockAction::class)->execute(pesananBaru(10), $adm, true))
        ->toThrow(ValidationException::class);
});

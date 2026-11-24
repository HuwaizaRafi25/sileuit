<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Actions\ApproveAdjustmentAction;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed();
    require_once base_path('tests/Feature/StockTransferTest.php');
});

function ajukan(string $tipe, float $qty, ?Lot $lot = null): StockAdjustment
{
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();

    return StockAdjustment::withoutGlobalScopes()->create([
        'no_dok' => 'ADJ-'.uniqid(), 'tipe' => $tipe,
        'location_id' => Location::where('kode', 'G1')->value('id'),
        'lot_id' => $lot?->id, 'commodity_id' => $c->id, 'grade_id' => $g->id,
        'qty_kg' => $qty, 'tanggal' => today(), 'alasan' => 'Susut pengeringan berkala',
        'status' => 'MENUNGGU', 'dibuat_oleh' => User::where('email', 'pg-g1@sileuit.id')->value('id'),
    ]);
}

it('penyesuaian menunggu persetujuan dan belum mengubah stok', function () {
    $lot = lotDenganStok('LOT-ADJ-01', '2026-11-12', 1000);
    $a = ajukan('PENYESUAIAN', -35, $lot);
    expect($a->status)->toBe('MENUNGGU')
        ->and((float) StockBalance::where('lot_id', $lot->id)->value('on_hand_kg'))->toBe(1000.0);

    $kg = User::where('email', 'kg-g1@sileuit.id')->first();
    $hasil = app(ApproveAdjustmentAction::class)->execute($a, $kg, true);
    expect($hasil->status)->toBe('DISETUJUI')
        ->and((float) StockBalance::where('lot_id', $lot->id)->value('on_hand_kg'))->toBe(965.0)
        ->and(StockLedger::where('jenis_mutasi', 'PENYESUAIAN')->exists())->toBeTrue();
});

it('penyesuaian tidak boleh membuat saldo negatif', function () {
    $lot = lotDenganStok('LOT-ADJ-02', '2026-11-12', 100);
    $a = ajukan('PENYESUAIAN', -200, $lot);
    $kg = User::where('email', 'kg-g1@sileuit.id')->first();
    expect(fn () => app(ApproveAdjustmentAction::class)->execute($a, $kg, true))->toThrow(ValidationException::class);
    expect((float) StockBalance::where('lot_id', $lot->id)->value('on_hand_kg'))->toBe(100.0);
});

it('saldo awal cutover membuat lot dan ledger SALDO_AWAL setelah disetujui Pemilik', function () {
    $a = ajukan('SALDO_AWAL', 750);
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
    $hasil = app(ApproveAdjustmentAction::class)->execute($a, $pemilik, true);
    expect($hasil->status)->toBe('DISETUJUI')
        ->and(StockLedger::where('jenis_mutasi', 'SALDO_AWAL')->exists())->toBeTrue()
        ->and(Lot::whereKey($hasil->lot_id)->exists())->toBeTrue();
});

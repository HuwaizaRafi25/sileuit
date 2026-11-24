<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\PointOfSale\Actions\ProcessPosCheckoutAction;
use App\Modules\PointOfSale\Models\HetConfig;
use App\Modules\PointOfSale\Models\PosSale;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function gradeBerasA(): int
{
    return Grade::where('commodity_id', Commodity::where('nama', 'BERAS')->value('id'))->where('kode', 'A')->value('id');
}

function stokPos(string $kode, string $kom, string $grade, float $kg, string $lok = 'C1'): Lot
{
    $c = Commodity::where('nama', $kom)->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', $grade)->first();
    $lot = Lot::create([
        'kode_lot' => $kode, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'receipt_id' => null,
        'tgl_terima' => '2026-11-20', 'batas_simpan' => '2027-05-19', 'status' => 'TERSEDIA',
    ]);
    $u = User::where('email', 'pemilik-1@sileuit.id')->first();
    DB::transaction(fn () => app(StockLedgerWriter::class)->post($lot, Location::where('kode', $lok)->value('id'), 'SALDO_AWAL', $kg, 'ADJUSTMENT', 0, $u));

    return $lot;
}

function hetBeras(int $harga, string $mode): void
{
    HetConfig::create(['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'harga_per_kg' => $harga, 'mode' => $mode, 'aktif' => true]);
}

it('TC-07: jual melebihi stok ditolak tanpa entri ledger', function () {
    stokPos('LOT-POS-01', 'GABAH', 'A', 100);
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $c1 = Location::where('kode', 'C1')->value('id');
    $sebelum = StockLedger::count();

    expect(fn () => app(ProcessPosCheckoutAction::class)->execute($kasir, $c1, [
        ['commodity_id' => Commodity::where('nama', 'GABAH')->value('id'), 'grade_id' => gradeBerasA(), 'qty_kg' => 120, 'harga_per_kg' => 6000],
    ], 'TUNAI', (string) Str::uuid()))->toThrow(ValidationException::class);

    expect(StockLedger::count())->toBe($sebelum)
        ->and((float) StockBalance::where('location_id', $c1)->value('on_hand_kg'))->toBe(100.0);
});

it('TC-16: harga melampaui HET diblokir saat mode BLOKIR', function () {
    stokPos('LOT-POS-02', 'BERAS', 'A', 100);
    hetBeras(13500, 'BLOKIR');
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $c1 = Location::where('kode', 'C1')->value('id');

    expect(fn () => app(ProcessPosCheckoutAction::class)->execute($kasir, $c1, [
        ['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'grade_id' => gradeBerasA(), 'qty_kg' => 10, 'harga_per_kg' => 14500],
    ], 'TUNAI', (string) Str::uuid()))->toThrow(ValidationException::class);
    expect(PosSale::count())->toBe(0);
});

it('TC-16b: mode PERINGATAN membolehkan jual di atas HET dengan tanda', function () {
    stokPos('LOT-POS-03', 'BERAS', 'A', 100);
    hetBeras(13500, 'PERINGATAN');
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $c1 = Location::where('kode', 'C1')->value('id');

    $sale = app(ProcessPosCheckoutAction::class)->execute($kasir, $c1, [
        ['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'grade_id' => gradeBerasA(), 'qty_kg' => 10, 'harga_per_kg' => 14500],
    ], 'TUNAI', (string) Str::uuid());
    expect($sale->peringatan_het)->toBeTrue();
});

it('TC-20: kasir kedua kehabisan stok lot terakhir, saldo tidak pernah negatif', function () {
    stokPos('LOT-POS-04', 'GABAH', 'A', 50);
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $c1 = Location::where('kode', 'C1')->value('id');
    $line = [['commodity_id' => Commodity::where('nama', 'GABAH')->value('id'), 'grade_id' => gradeBerasA(), 'qty_kg' => 50, 'harga_per_kg' => 6000]];

    app(ProcessPosCheckoutAction::class)->execute($kasir, $c1, $line, 'TUNAI', (string) Str::uuid());

    expect(fn () => app(ProcessPosCheckoutAction::class)->execute($kasir, $c1, $line, 'TUNAI', (string) Str::uuid()))
        ->toThrow(ValidationException::class);
    expect((float) StockBalance::where('location_id', $c1)->value('on_hand_kg'))->toBe(0.0)
        ->and(PosSale::count())->toBe(1);
});

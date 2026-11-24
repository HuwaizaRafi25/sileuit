<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Actions\ReceiveTransferAction;
use App\Modules\InventoryLedger\Actions\ShipTransferAction;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\InventoryLedger\Models\TransferLine;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function lotDenganStok(string $kode, string $tgl, float $kg, string $lok = 'G1', string $kom = 'GABAH', string $grade = 'A'): Lot
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

function buatTransfer(Lot $lot, float $kg, string $dari = 'G1', string $ke = 'G2'): StockTransfer
{
    $t = StockTransfer::create([
        'no_dok' => 'TRF-'.uniqid(), 'dari_lokasi' => Location::where('kode', $dari)->value('id'),
        'ke_lokasi' => Location::where('kode', $ke)->value('id'), 'status' => 'DRAFT',
    ]);
    TransferLine::create(['stock_transfer_id' => $t->id, 'lot_id' => $lot->id, 'kg_kirim' => $kg]);

    return $t;
}

it('TC-09: transfer 400 diterima 390 menjadi DISCREPANCY dan selisih tercatat', function () {
    $lot = lotDenganStok('LOT-TRF-01', '2026-11-12', 1000);
    $t = buatTransfer($lot, 400);
    $kg1 = User::where('email', 'kg-g1@sileuit.id')->first();
    $kg2 = User::where('email', 'kg-g2@sileuit.id')->first();

    app(ShipTransferAction::class)->execute($t, $kg1);
    $g1 = Location::where('kode', 'G1')->value('id');
    expect((float) StockBalance::where('lot_id', $lot->id)->where('location_id', $g1)->value('on_hand_kg'))->toBe(600.0);

    $line = $t->lines()->first();
    $hasil = app(ReceiveTransferAction::class)->execute($t->fresh(), $kg2, [$line->id => 390]);

    $g2 = Location::where('kode', 'G2')->value('id');
    expect($hasil->status)->toBe('DISCREPANCY')
        ->and((float) StockBalance::where('lot_id', $lot->id)->where('location_id', $g2)->value('on_hand_kg'))->toBe(390.0)
        ->and((float) $line->fresh()->selisih_kg)->toBe(10.0);
});

it('TC-28: transfer melebihi on-hand ditolak tanpa mutasi', function () {
    $lot = lotDenganStok('LOT-TRF-02', '2026-11-12', 400);
    $t = buatTransfer($lot, 500);
    $kg1 = User::where('email', 'kg-g1@sileuit.id')->first();
    $sebelum = StockLedger::count();

    expect(fn () => app(ShipTransferAction::class)->execute($t, $kg1))->toThrow(ValidationException::class);
    expect(StockLedger::count())->toBe($sebelum)
        ->and($t->fresh()->status)->toBe('DRAFT')
        ->and((float) StockBalance::where('lot_id', $lot->id)->value('on_hand_kg'))->toBe(400.0);
});

it('transfer terima utuh berstatus RECEIVED', function () {
    $lot = lotDenganStok('LOT-TRF-03', '2026-11-12', 1000);
    $t = buatTransfer($lot, 400);
    app(ShipTransferAction::class)->execute($t, User::where('email', 'kg-g1@sileuit.id')->first());
    $line = $t->lines()->first();
    $hasil = app(ReceiveTransferAction::class)->execute($t->fresh(), User::where('email', 'kg-g2@sileuit.id')->first(), [$line->id => 400]);
    expect($hasil->status)->toBe('RECEIVED');
});

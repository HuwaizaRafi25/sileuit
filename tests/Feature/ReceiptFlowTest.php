<?php

use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\ProcurementReceiving\Actions\ApproveDiscrepancyAction;
use App\Modules\ProcurementReceiving\Actions\ConfirmReceiptAction;
use App\Modules\ProcurementReceiving\Models\Attachment;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Inspection;
use App\Modules\ProcurementReceiving\Models\PriceListItem;
use App\Modules\ProcurementReceiving\Models\Receipt;
use App\Modules\ProcurementReceiving\Services\HargaTidakDitemukanException;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed());

function siapkanReceipt(float $netto, float $kadarAir, string $petugas = 'pg-g1@sileuit.id', string $keputusan = 'TERIMA', ?string $alasan = null): Receipt
{
    $u = User::where('email', $petugas)->first();
    auth()->login($u);
    $kontrak = Contract::whereHas('partner', fn ($q) => $q->where('nama', 'Petani Contoh A'))->first();
    $rencana = HarvestPlan::where('contract_id', $kontrak->id)->first();
    $gabah = Commodity::where('nama', 'GABAH')->first();
    $grade = $gabah->grades->filter(fn ($g) => $kadarAir <= (float) $g->ambang_mutu['kadar_air_max'])->sortBy(fn ($g) => (float) $g->ambang_mutu['kadar_air_max'])->first();

    $r = Receipt::withoutGlobalScopes()->create([
        'no_dok' => 'GRN-TEST-'.Str::random(6),
        'contract_id' => $kontrak->id,
        'location_id' => $u->active_location_id,
        'harvest_plan_id' => $rencana->id,
        'bruto_kg' => $netto + 50,
        'tara_kg' => 50,
        'netto_kg' => $netto,
        'status' => 'INSPECTED',
        'selisih_persen' => round(($netto - (float) $rencana->kg_perkiraan) / (float) $rencana->kg_perkiraan * 100, 2),
        'diterima_oleh' => $u->id,
        'waktu_timbang' => '2026-11-12 10:15:00',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $att = Attachment::create(['path' => 'inspections/test.webp', 'diunggah_oleh' => $u->id]);
    Inspection::create([
        'receipt_id' => $r->id, 'kadar_air' => $kadarAir, 'kadar_hampa' => 2, 'butir_patah' => 3,
        'varietas' => 'Ciherang', 'grade_id' => $grade?->id, 'keputusan' => $keputusan,
        'alasan' => $alasan, 'foto_attachment_id' => $att->id,
    ]);

    return $r;
}

it('TC-01: penerimaan normal membuat lot, ledger, saldo, dan nilai bayar', function () {
    $r = siapkanReceipt(1000, 13.5);
    $hasil = app(ConfirmReceiptAction::class)->execute($r, auth()->user());

    expect($hasil->status)->toBe('CONFIRMED')
        ->and((int) $hasil->harga_per_kg)->toBe(6800)
        ->and((int) $hasil->nilai_bayar)->toBe(6800000);

    $lot = Lot::where('receipt_id', $r->id)->first();
    expect($lot)->not->toBeNull()->and($lot->status)->toBe('TERSEDIA');
    expect((float) StockLedger::where('ref_id', $r->id)->value('qty_kg'))->toBe(1000.0);
    expect((float) StockBalance::where('lot_id', $lot->id)->value('on_hand_kg'))->toBe(1000.0);
});

it('TC-02: selisih 0,5% diterima otomatis tanpa persetujuan', function () {
    $r = siapkanReceipt(995, 13.5);
    expect($r->butuhPersetujuanSelisih())->toBeFalse();
    expect(app(ConfirmReceiptAction::class)->execute($r, auth()->user())->status)->toBe('CONFIRMED');
});

it('TC-03: selisih 10% menunggu persetujuan dan stok belum bertambah', function () {
    $r = siapkanReceipt(900, 13.5);
    $r->update(['status' => 'MENUNGGU_PERSETUJUAN']);
    expect(StockLedger::count())->toBe(0);

    $kg = User::where('email', 'kg-g1@sileuit.id')->first();
    $r = app(ApproveDiscrepancyAction::class)->execute($r, $kg, true, 'Susut transportasi, disetujui.');
    expect($r->status)->toBe('INSPECTED')->and($r->selisih_disetujui_oleh)->toBe($kg->id);

    $hasil = app(ConfirmReceiptAction::class)->execute($r, auth()->user());
    expect($hasil->status)->toBe('CONFIRMED');
    expect((float) StockLedger::where('ref_id', $r->id)->value('qty_kg'))->toBe(900.0);
});

it('TC-04: kadar air 22% ditolak, tidak ada lot maupun ledger, alasan tercatat', function () {
    $r = siapkanReceipt(1000, 22, keputusan: 'TOLAK', alasan: 'Kadar air 22% di atas batas minimum 18%.');
    $hasil = app(ConfirmReceiptAction::class)->execute($r, auth()->user());

    expect($hasil->status)->toBe('REJECTED');
    expect(Lot::count())->toBe(0)->and(StockLedger::count())->toBe(0);
    expect($r->inspectionTerakhir()->alasan)->toContain('22%');
});

it('TC-04b: karantina membuat lot tidak layak jual', function () {
    $r = siapkanReceipt(1000, 22, keputusan: 'KARANTINA', alasan: 'Perlu pengeringan ulang.');
    app(ConfirmReceiptAction::class)->execute($r, auth()->user());
    expect(Lot::where('receipt_id', $r->id)->value('status'))->toBe('KARANTINA');
});

it('TC-19: kadar air 15% turun ke grade B dengan harga B', function () {
    $r = siapkanReceipt(1000, 15);
    $hasil = app(ConfirmReceiptAction::class)->execute($r, auth()->user());
    expect($hasil->inspectionTerakhir()->grade->kode)->toBe('B')
        ->and((int) $hasil->harga_per_kg)->toBe(6500)
        ->and((int) $hasil->nilai_bayar)->toBe(6500000);
});

it('TC-05: harga grade belum ada memblokir konfirmasi, tidak ada lot/ledger', function () {
    $r = siapkanReceipt(1000, 13.5);
    $g = Grade::whereHas('commodity', fn ($q) => $q->where('nama', 'GABAH'))->where('kode', 'A')->first();
    PriceListItem::where('grade_id', $g->id)->delete();

    expect(fn () => app(ConfirmReceiptAction::class)->execute($r, auth()->user()))
        ->toThrow(HargaTidakDitemukanException::class);
    expect(Lot::count())->toBe(0)->and(StockLedger::count())->toBe(0);
});

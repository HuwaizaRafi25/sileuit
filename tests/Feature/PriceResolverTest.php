<?php

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\PriceListItem;
use App\Modules\ProcurementReceiving\Services\HargaTidakDitemukanException;
use App\Modules\ProcurementReceiving\Services\PriceResolver;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function gabahGrade(string $kode): Grade
{
    $gabah = Commodity::where('nama', 'GABAH')->first();

    return Grade::where('commodity_id', $gabah->id)->where('kode', $kode)->first();
}

it('mengambil harga disetujui yang berlaku pada tanggal timbang (dasar TC-01)', function () {
    $item = app(PriceResolver::class)->resolve(
        gabahGrade('A')->commodity_id, gabahGrade('A')->id, Carbon::parse('2026-11-12')
    );
    expect($item->harga_per_kg)->toBe(6800);
});

it('menolak bila harga grade belum ada (TC-05)', function () {
    $g = gabahGrade('C');
    PriceListItem::where('grade_id', $g->id)->delete();
    expect(fn () => app(PriceResolver::class)->resolve($g->commodity_id, $g->id, Carbon::parse('2026-11-12')))
        ->toThrow(HargaTidakDitemukanException::class);
});

it('mengabaikan harga yang belum disetujui Pemilik', function () {
    $g = gabahGrade('A');
    $item = PriceListItem::where('grade_id', $g->id)->first();
    $item->update(['disetujui_oleh' => null, 'disetujui_pada' => null]);
    expect(fn () => app(PriceResolver::class)->resolve($g->commodity_id, $g->id, Carbon::parse('2026-11-12')))
        ->toThrow(HargaTidakDitemukanException::class);
});

it('harga baru berlaku besok tidak mengubah harga hari ini (FR-02)', function () {
    $g = gabahGrade('A');
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();

    PriceListItem::where('grade_id', $g->id)->first()->update(['berlaku_sampai' => '2026-11-25']);
    PriceListItem::create([
        'commodity_id' => $g->commodity_id, 'grade_id' => $g->id, 'harga_per_kg' => 7000,
        'berlaku_dari' => '2026-11-26', 'disetujui_oleh' => $pemilik->id, 'disetujui_pada' => now(),
    ]);

    $item = app(PriceResolver::class)->resolve($g->commodity_id, $g->id, Carbon::parse('2026-11-12'));
    expect($item->harga_per_kg)->toBe(6800);
});

it('menolak periode harga tumpang tindih (IR-06)', function () {
    $g = gabahGrade('A');
    expect(fn () => PriceListItem::create([
        'commodity_id' => $g->commodity_id, 'grade_id' => $g->id, 'harga_per_kg' => 6900, 'berlaku_dari' => '2026-11-10',
    ]))->toThrow(ValidationException::class);
});

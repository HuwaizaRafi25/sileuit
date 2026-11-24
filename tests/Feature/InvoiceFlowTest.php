<?php

use App\Modules\BillingAccountsReceivable\Actions\IssueInvoiceAction;
use App\Modules\BillingAccountsReceivable\Actions\RecordInvoicePaymentAction;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Actions\PickOrderAction;
use App\Modules\OrderFulfillment\Actions\ReceiveOrderAction;
use App\Modules\OrderFulfillment\Actions\ShipOrderAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\PointOfSale\Models\HetConfig;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Partner;
use Database\Seeders\InvoiceBaselineSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

function mitraRetail(string $nama, int $batas = 15000000, int $termin = 14): Partner
{
    return Partner::create([
        'tipe' => 'RETAIL', 'nama' => $nama, 'hp' => '+628'.rand(100000000, 999999999),
        'batas_kredit' => $batas, 'termin_hari' => $termin, 'status' => 'AKTIF',
    ]);
}

function pesananDelivered(float $kg, Partner $partner, string $kom = 'BERAS', string $grade = 'A'): SalesOrder
{
    require_once base_path('tests/Feature/OrderFulfillmentTest.php');
    $c = Commodity::where('nama', $kom)->first() ?? Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', $grade)->first();

    // stok lot
    $lot = Lot::create([
        'kode_lot' => 'LOT-F5-'.Str::random(6), 'commodity_id' => $c->id, 'grade_id' => $g->id, 'receipt_id' => null,
        'tgl_terima' => '2026-11-01', 'batas_simpan' => '2027-05-01', 'status' => 'TERSEDIA',
    ]);
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
    DB::transaction(fn () => app(StockLedgerWriter::class)
        ->post($lot, Location::where('kode', 'G1')->value('id'), 'SALDO_AWAL', $kg + 200, 'ADJUSTMENT', 0, $pemilik));

    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $o = SalesOrder::withoutGlobalScopes()->create([
        'no_dok' => 'SO-F5-'.uniqid(), 'partner_id' => $partner->id,
        'location_id' => Location::where('kode', 'G1')->value('id'), 'status' => 'NEW', 'dibuat_oleh' => $adm->id,
    ]);
    SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'qty_kg' => $kg]);
    $o = app(AllocateOrderStockAction::class)->execute($o, $adm);
    $sj = app(PickOrderAction::class)->execute($o, $adm);
    app(ShipOrderAction::class)->execute($sj, $adm);
    app(ReceiveOrderAction::class)->execute($sj->fresh(), $adm, $kg);

    return $o->fresh();
}

it('TC-11: bayar sebagian mengubah status menjadi PARTIAL dan menyisakan saldo', function () {
    HetConfig::create(['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'harga_per_kg' => 10000, 'mode' => 'PERINGATAN', 'aktif' => true]);
    $r01 = mitraRetail('R-01 TC-11', 100000000);
    $o = pesananDelivered(1000, $r01);
    $keu = User::where('email', 'keu-1@sileuit.id')->first();

    $inv = app(IssueInvoiceAction::class)->execute($o, $keu);
    expect($inv->total)->toBe(10000000)->and($inv->status())->toBe('ISSUED');

    app(RecordInvoicePaymentAction::class)->execute($keu, $r01->id, 4000000, 'TRANSFER', 'TRF-001', (string) Str::uuid());
    $inv = $inv->fresh();
    expect($inv->status())->toBe('PARTIAL')->and($inv->saldo())->toBe(6000000);
});

it('TC-21: pembayaran melebihi saldo invoice ditolak (A-17)', function () {
    HetConfig::create(['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'harga_per_kg' => 10000, 'mode' => 'PERINGATAN', 'aktif' => true]);
    $r01 = mitraRetail('R-01 TC-21', 100000000);
    $o = pesananDelivered(1000, $r01);
    $keu = User::where('email', 'keu-1@sileuit.id')->first();

    $inv = app(IssueInvoiceAction::class)->execute($o, $keu);
    app(RecordInvoicePaymentAction::class)->execute($keu, $r01->id, 4000000, 'TRANSFER', 'TRF-A', (string) Str::uuid());

    expect(fn () => app(RecordInvoicePaymentAction::class)->execute($keu, $r01->id, 7000000, 'TRANSFER', 'TRF-B', (string) Str::uuid()))
        ->toThrow(ValidationException::class);
    expect($inv->fresh()->saldo())->toBe(6000000);
});

it('TC-22: alokasi pembayaran ke invoice tertua otomatis', function () {
    $this->seed(InvoiceBaselineSeeder::class);
    $r01 = Partner::where('nama', 'Retail Contoh Satu')->first();
    $keu = User::where('email', 'keu-1@sileuit.id')->first();

    $inv1 = Invoice::where('no', 'INV-BASE-001')->first();
    $inv2 = Invoice::where('no', 'INV-BASE-002')->first();

    app(RecordInvoicePaymentAction::class)->execute($keu, $r01->id, 6000000, 'TRANSFER', 'TRF-C', (string) Str::uuid());

    expect($inv1->fresh()->status())->toBe('PAID')->and($inv1->fresh()->saldo())->toBe(0);
    expect($inv2->fresh()->status())->toBe('PARTIAL')
        ->and($inv2->fresh()->saldo())->toBe(7000000)
        ->and($inv2->fresh()->dibayar())->toBe(1000000);
});

it('TC-15: invoice yang melebihi batas kredit ditolak tanpa paksaan', function () {
    HetConfig::create(['commodity_id' => Commodity::where('nama', 'BERAS')->value('id'), 'harga_per_kg' => 10000, 'mode' => 'PERINGATAN', 'aktif' => true]);
    $r01 = mitraRetail('R-01 TC-15', 15000000);
    $keu = User::where('email', 'keu-1@sileuit.id')->first();

    // Bangun saldo berjalan 12 jt lewat 2 invoice kecil (paksa lolos)
    $o1 = pesananDelivered(600, $r01);
    $inv1 = app(IssueInvoiceAction::class)->execute($o1, $keu, true);
    $o2 = pesananDelivered(600, $r01);
    app(IssueInvoiceAction::class)->execute($o2, $keu, true);

    $o3 = pesananDelivered(500, $r01);
    expect(fn () => app(IssueInvoiceAction::class)->execute($o3, $keu))->toThrow(ValidationException::class);
    // Dengan paksaan, lolos
    $inv3 = app(IssueInvoiceAction::class)->execute($o3, $keu, true);
    expect($inv3->exists)->toBeTrue();
});

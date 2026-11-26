<?php

use App\Modules\BillingAccountsReceivable\Actions\IssueInvoiceAction;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Partner;

beforeEach(fn () => $this->seed());

it('FR-16: cetak surat jalan dan invoice menghasilkan PDF', function () {
    $r01 = Partner::create(['tipe' => 'RETAIL', 'nama' => 'R-01 Export', 'hp' => '+628911111', 'batas_kredit' => 0, 'termin_hari' => 14, 'status' => 'AKTIF']);
    $c = Commodity::where('nama', 'GABAH')->first();
    $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $keu = User::where('email', 'keu-1@sileuit.id')->first();

    $o = SalesOrder::withoutGlobalScopes()->create([
        'no_dok' => 'SO-EXP-1', 'partner_id' => $r01->id, 'location_id' => Location::where('kode', 'G1')->value('id'),
        'status' => 'NEW', 'dibuat_oleh' => $adm->id,
    ]);
    SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $c->id, 'grade_id' => $g->id, 'qty_kg' => 10, 'qty_terpenuhi' => 10]);
    $o->update(['status' => 'DELIVERED']);

    $sj = Shipment::create(['no_dok' => 'SJ-EXP-1', 'sales_order_id' => $o->id, 'status' => 'DITERIMA', 'jumlah_dikirim_kg' => 10]);

    $this->actingAs($adm)->get('/app/cetak/surat-jalan/'.$sj->id)
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    $inv = app(IssueInvoiceAction::class)->execute($o->fresh(), $keu);
    $this->actingAs($keu)->get('/app/cetak/invoice/'.$inv->id)
        ->assertOk()->assertHeader('content-type', 'application/pdf');
});

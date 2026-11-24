<?php

namespace Database\Seeders;

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Models\InvoiceLine;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Database\Seeder;

class InvoiceBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $r01 = Partner::where('nama', 'Retail Contoh Satu')->first();
        if (! $r01) {
            $r01 = Partner::create([
                'tipe' => 'RETAIL', 'nama' => 'Retail Contoh Satu', 'hp' => '+628900000001',
                'batas_kredit' => 15000000, 'termin_hari' => 14, 'status' => 'AKTIF',
            ]);
        }
        $c = Commodity::where('nama', 'BERAS')->first() ?? Commodity::where('nama', 'GABAH')->first();
        $g = Grade::where('commodity_id', $c->id)->where('kode', 'A')->first();
        $adm = User::where('email', 'adm-1@sileuit.id')->first();
        $lok = Location::where('kode', 'C1')->first() ?? Location::where('kode', 'G1')->first();

        foreach ([['INV-BASE-001', 5000000, '-20 day'], ['INV-BASE-002', 8000000, '-5 day']] as [$no, $total, $when]) {
            if (Invoice::where('no', $no)->exists()) {
                continue;
            }
            $so = SalesOrder::withoutGlobalScopes()->create([
                'no_dok' => 'SO-'.$no, 'partner_id' => $r01->id, 'location_id' => $lok->id,
                'status' => 'DELIVERED', 'dibuat_oleh' => $adm->id,
            ]);
            $inv = Invoice::create([
                'no' => $no, 'partner_id' => $r01->id, 'sales_order_id' => $so->id, 'location_id' => $lok->id,
                'tgl_invoice' => now()->modify($when)->toDateString(), 'jatuh_tempo' => now()->modify($when)->addDays(14)->toDateString(),
                'total' => $total, 'dibuat_oleh' => $adm->id,
            ]);
            InvoiceLine::create([
                'invoice_id' => $inv->id, 'commodity_id' => $c->id, 'grade_id' => $g->id,
                'qty_kg' => $total / 10000, 'harga_per_kg' => 10000, 'subtotal' => $total,
            ]);
        }
    }
}

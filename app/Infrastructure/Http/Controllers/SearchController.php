<?php

namespace App\Infrastructure\Http\Controllers;

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Partner;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController
{
    /** FR-17: pencarian global lot, mitra, dokumen — tetap tunduk pada cakupan server (BR-12, TC-08). */
    public function index(Request $request): View
    {
        $u = $request->user();
        $q = trim((string) $request->query('q', ''));
        $hasil = ['lots' => collect(), 'receipts' => collect(), 'shipments' => collect(), 'invoices' => collect(), 'partners' => collect()];

        if ($q !== '') {
            $lq = Lot::with('commodity', 'grade')->where('kode_lot', 'like', "%{$q}%");
            if ($u->hasRole('PETANI', 'MITRA_B2B')) {
                $lq->whereRaw('1 = 0');
            } elseif (! $u->isGlobal()) {
                $lq->whereHas('receipt', fn ($r) => $r->withoutGlobalScopes()->whereIn('location_id', $u->locationScopes->pluck('id')));
            }
            $hasil['lots'] = $lq->limit(10)->get();
            $hasil['receipts'] = Receipt::with('contract.partner', 'location')->where('no_dok', 'like', "%{$q}%")->limit(10)->get();
            $hasil['shipments'] = Shipment::with('order')->where('no_dok', 'like', "%{$q}%")->limit(10)->get();
            $hasil['invoices'] = Invoice::with('partner')->where('no', 'like', "%{$q}%")->limit(10)->get();
            $hasil['partners'] = $u->hasRole('PETANI', 'MITRA_B2B') ? collect() : Partner::where('nama', 'like', "%{$q}%")->limit(10)->get();
        }

        return view('app.cari', ['q' => $q, 'hasil' => $hasil]);
    }
}

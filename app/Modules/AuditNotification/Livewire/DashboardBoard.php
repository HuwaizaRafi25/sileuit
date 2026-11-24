<?php

namespace App\Modules\AuditNotification\Livewire;

use App\Modules\BillingAccountsReceivable\Services\AgingCalculator;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DashboardBoard extends Component
{
    public function render()
    {
        $u = auth()->user();
        $scopeIds = $u->isGlobal() ? null : $u->locationScopes->pluck('id');

        $stokKg = StockBalance::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->sum('on_hand_kg');
        $terimaHariIni = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->whereDate('waktu_timbang', today())->count();
        $pesananAktif = SalesOrder::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->whereIn('status', ['NEW', 'ALLOCATED', 'PICKED', 'SHIPPED'])->count();
        $lotMendekati = Lot::when($scopeIds, fn ($q) => $q->whereIn('receipt_id', Receipt::when($scopeIds, fn ($qq) => $qq->whereIn('location_id', $scopeIds))->select('id')))
            ->where('status', 'TERSEDIA')->get()->filter(fn ($l) => $l->persenUmur() >= 80)->count();

        $piutang = 0;
        $aging = ['buckets' => [], 'total' => 0];
        if ($u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN')) {
            $aging = app(AgingCalculator::class)->hitung();
            $piutang = $aging['total'];
        }

        $pendingApprovals = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->where('status', 'MENUNGGU_PERSETUJUAN')->count();

        return view('livewire.dashboard-board', [
            'stokKg' => (float) $stokKg,
            'terimaHariIni' => $terimaHariIni,
            'pesananAktif' => $pesananAktif,
            'lotMendekati' => $lotMendekati,
            'piutang' => $piutang,
            'aging' => $aging,
            'pendingApprovals' => $pendingApprovals,
        ]);
    }
}

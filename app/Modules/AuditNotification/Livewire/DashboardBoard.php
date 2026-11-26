<?php

namespace App\Modules\AuditNotification\Livewire;

use App\Modules\AuditNotification\Models\AuditLog;
use App\Modules\BillingAccountsReceivable\Services\AgingCalculator;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\PointOfSale\Models\PosSale;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DashboardBoard extends Component
{
    public function gantiLokasi(int $locId): void
    {
        $u = auth()->user();
        if (! $u) {
            return;
        }

        if ($u->isGlobal() || $u->locationScopes->contains('id', $locId)) {
            $u->update(['active_location_id' => $locId]);
        }
    }

    public function render()
    {
        $u = auth()->user();
        $scopeIds = $u->isGlobal() ? null : $u->locationScopes->pluck('id');

        // Stock stats
        $stockBalances = StockBalance::with(['lot.commodity', 'lot.grade', 'location'])
            ->when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->where('on_hand_kg', '>', 0)
            ->get();

        $stokKg = (float) $stockBalances->sum('on_hand_kg');
        $stokReserved = (float) $stockBalances->sum('reserved_kg');
        $stokTersedia = max(0, $stokKg - $stokReserved);

        // Receipts
        $receiptsQuery = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds));
        $terimaHariIni = (clone $receiptsQuery)->whereDate('waktu_timbang', today())->count();
        $terimaHariIniKg = (float) (clone $receiptsQuery)->whereDate('waktu_timbang', today())->sum('netto_kg');
        $terimaTotal = (clone $receiptsQuery)->count();
        $terimaTerbaru = Receipt::with(['contract.partner', 'location'])
            ->when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->latest('id')
            ->limit(5)
            ->get();

        // Orders & Shipments
        $pesananQuery = SalesOrder::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds));
        $pesananAktif = (clone $pesananQuery)->whereIn('status', ['NEW', 'ALLOCATED', 'PICKED', 'SHIPPED'])->count();
        $pesananTerbaru = SalesOrder::with(['partner', 'lines.commodity', 'lines.grade'])
            ->when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->latest('id')
            ->limit(5)
            ->get();

        $pengirimanAktif = Shipment::whereIn('status', ['MENUNGGU', 'DIKIRIM'])->count()
            + ($u->hasRole('KURIR') ? StockTransfer::where('kurir_id', $u->id)->where('status', 'SHIPPED')->count() : 0);

        // Expiring / Aged Lots
        $lotMendekati = Lot::when($scopeIds, fn ($q) => $q->whereIn('receipt_id', Receipt::when($scopeIds, fn ($qq) => $qq->whereIn('location_id', $scopeIds))->select('id')))
            ->where('status', 'TERSEDIA')->get()->filter(fn ($l) => $l->persenUmur() >= 80)->count();

        // Approvals Needed
        $pendingReceipts = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->where('status', 'MENUNGGU_PERSETUJUAN')->count();
        $pendingAdjustments = StockAdjustment::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))
            ->where('status', 'MENUNGGU')->count();
        $pendingReturns = ReturnDoc::where('status', 'REQUESTED')->count();
        $totalPendingApprovals = $pendingReceipts + $pendingAdjustments + $pendingReturns;

        // Finance / Receivables
        $piutang = 0;
        $aging = ['buckets' => [], 'total' => 0];
        if ($u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN')) {
            $aging = app(AgingCalculator::class)->hitung();
            $piutang = $aging['total'];
        }

        // Today's POS Sales
        $posHariIni = 0;
        if ($u->hasRole('KASIR', 'KEUANGAN', 'PEMILIK', 'ADMIN')) {
            $posHariIni = (int) PosSale::whereDate('created_at', today())->sum('total');
        }

        // Recent Audit Activity
        $recentAudit = [];
        if ($u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN', 'KEPALA_GUDANG')) {
            $recentAudit = AuditLog::with('user')->latest('id')->limit(6)->get();
        }

        // Available Locations for Location Switcher
        $availableLocations = $u->isGlobal()
            ? Location::all()
            : $u->locationScopes;

        return view('livewire.dashboard-board', [
            'stokKg' => $stokKg,
            'stokReserved' => $stokReserved,
            'stokTersedia' => $stokTersedia,
            'terimaHariIni' => $terimaHariIni,
            'terimaHariIniKg' => $terimaHariIniKg,
            'terimaTotal' => $terimaTotal,
            'terimaTerbaru' => $terimaTerbaru,
            'pesananAktif' => $pesananAktif,
            'pesananTerbaru' => $pesananTerbaru,
            'pengirimanAktif' => $pengirimanAktif,
            'lotMendekati' => $lotMendekati,
            'pendingReceipts' => $pendingReceipts,
            'pendingAdjustments' => $pendingAdjustments,
            'pendingReturns' => $pendingReturns,
            'totalPendingApprovals' => $totalPendingApprovals,
            'piutang' => $piutang,
            'aging' => $aging,
            'posHariIni' => $posHariIni,
            'recentAudit' => $recentAudit,
            'availableLocations' => $availableLocations,
            'stockBalances' => $stockBalances,
        ]);
    }
}

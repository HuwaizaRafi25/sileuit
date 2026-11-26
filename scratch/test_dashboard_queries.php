<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Modules\AuditNotification\Models\AuditLog;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Contracts\Console\Kernel;

$u = User::where('email', 'pg-g1@sileuit.id')->first();
auth()->login($u);

$scopeIds = $u->isGlobal() ? null : $u->locationScopes->pluck('id');

$stokOnHand = StockBalance::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->sum('on_hand_kg');
$stokReserved = StockBalance::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->sum('reserved_kg');
$stokTersedia = max(0, (float) $stokOnHand - (float) $stokReserved);

$terimaHariIni = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->whereDate('waktu_timbang', today())->count();
$terimaTotal = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->count();
$terimaTerbaru = Receipt::with(['contract.partner', 'location'])->when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->latest('id')->limit(5)->get();

$pesananAktif = SalesOrder::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->whereIn('status', ['NEW', 'ALLOCATED', 'PICKED', 'SHIPPED'])->count();
$pesananTerbaru = SalesOrder::with(['partner', 'lines.commodity', 'lines.grade'])->when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->latest('id')->limit(5)->get();

$pengirimanAktif = Shipment::whereIn('status', ['MENUNGGU', 'DIKIRIM'])->count();

$pendingPenerimaan = Receipt::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->where('status', 'MENUNGGU_PERSETUJUAN')->count();
$pendingAdjustment = StockAdjustment::when($scopeIds, fn ($q) => $q->whereIn('location_id', $scopeIds))->where('status', 'MENUNGGU')->count();
$pendingReturn = ReturnDoc::where('status', 'REQUESTED')->count();

$recentAudit = AuditLog::with('user')->latest('id')->limit(5)->get();

echo "Stok On Hand: {$stokOnHand}\n";
echo "Stok Tersedia: {$stokTersedia}\n";
echo "Terima Hari Ini: {$terimaHariIni}, Total Terima: {$terimaTotal}\n";
echo "Pesanan Aktif: {$pesananAktif}\n";
echo "Pengiriman Aktif: {$pengirimanAktif}\n";
echo "Pending Approvals: Penerimaan={$pendingPenerimaan}, Adj={$pendingAdjustment}, Return={$pendingReturn}\n";
echo 'Recent Audit Count: '.$recentAudit->count()."\n";
echo "SUCCESS!\n";

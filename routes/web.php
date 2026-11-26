<?php

use App\Infrastructure\Http\Controllers\ExportController;
use App\Infrastructure\Http\Controllers\SearchController;
use App\Modules\AuditNotification\Http\Controllers\NotificationController;
use App\Modules\AuditNotification\Livewire\AuditLogBoard;
use App\Modules\AuditNotification\Livewire\DashboardBoard;
use App\Modules\BillingAccountsReceivable\Livewire\InvoiceBoard;
use App\Modules\IdentityAccess\Http\Controllers\LoginController;
use App\Modules\InventoryLedger\Livewire\AdjustmentBoard;
use App\Modules\InventoryLedger\Livewire\TransferBoard;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\OrderFulfillment\Livewire\OrderBoard;
use App\Modules\OrderFulfillment\Livewire\OrderFulfillmentBoard;
use App\Modules\OrderFulfillment\Livewire\ReturnBoard;
use App\Modules\PointOfSale\Livewire\PosBoard;
use App\Modules\ProcurementReceiving\Livewire\ReceiptDetail;
use App\Modules\ProcurementReceiving\Livewire\ReceiptWizard;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');
Route::redirect('/admin/login', '/login');

Route::get('/login', [LoginController::class, 'showStaff'])->name('login');
Route::post('/login', [LoginController::class, 'loginStaff']);
Route::post('/login/demo/{role}', [LoginController::class, 'loginDemo'])->name('login.demo');
Route::get('/login/petani', [LoginController::class, 'showPetani']);
Route::post('/login/petani', [LoginController::class, 'loginPetani']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/app/beranda', DashboardBoard::class)->name('beranda');

    Route::get('/app/penerimaan', function () {
        abort_unless(auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'), 403);

        $q = Receipt::with(['contract.partner', 'location', 'inspections.grade'])->latest('id');
        $search = request('q');
        $status = request('status');
        if ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('no_dok', 'like', "%{$search}%")
                    ->orWhereHas('contract.partner', fn ($p) => $p->where('nama', 'like', "%{$search}%"));
            });
        }
        if ($status) {
            $q->where('status', $status);
        }

        return view('app.penerimaan', [
            'daftar' => $q->paginate(15)->withQueryString(),
            'search' => $search,
            'statusFilter' => $status,
        ]);
    })->name('penerimaan');

    Route::get('/app/terima', ReceiptWizard::class)->name('terima');
    Route::get('/app/penerimaan/{receiptId}', ReceiptDetail::class)->name('penerimaan.detail');

    Route::get('/app/stok', function () {
        abort_unless(auth()->user()->can('lihat-stok'), 403);
        $u = auth()->user();
        $q = StockBalance::with(['lot.commodity', 'lot.grade', 'location'])->where('on_hand_kg', '>', 0);
        if (! $u->isGlobal()) {
            $q->whereIn('location_id', $u->locationScopes->pluck('id'));
        }
        $search = request('q');
        if ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->whereHas('lot', fn ($l) => $l->where('kode_lot', 'like', "%{$search}%"))
                    ->orWhereHas('lot.commodity', fn ($c) => $c->where('nama', 'like', "%{$search}%"));
            });
        }

        return view('app.stok', [
            'saldo' => $q->get(),
            'search' => $search,
        ]);
    })->name('stok');

    Route::get('/app/stok/{lotId}', function (int $lotId) {
        abort_unless(auth()->user()->can('lihat-stok'), 403);
        $lot = Lot::with(['commodity', 'grade'])->findOrFail($lotId);
        $u = auth()->user();
        $q = StockLedger::with('location')->where('lot_id', $lot->id)->orderBy('waktu')->orderBy('id');
        if (! $u->isGlobal()) {
            $q->whereIn('location_id', $u->locationScopes->pluck('id'));
        }
        $mutasi = $q->get();
        $jalan = 0.0;
        foreach ($mutasi as $m) {
            $jalan = round($jalan + (float) $m->qty_kg, 3);
            $m->saldo_jalan = $jalan;
        }

        return view('app.kartu-stok', ['lot' => $lot, 'mutasi' => $mutasi]);
    })->name('stok.kartu');

    Route::get('/app/transfer', TransferBoard::class)->name('transfer');
    Route::get('/app/penyesuaian', AdjustmentBoard::class)->name('penyesuaian');
    Route::get('/app/pesanan', OrderBoard::class)->name('pesanan');
    Route::get('/app/pengiriman', OrderFulfillmentBoard::class)->name('pengiriman');
    Route::get('/app/retur', ReturnBoard::class)->name('retur');
    Route::get('/app/pos', PosBoard::class)->name('pos');
    Route::get('/app/invoice', InvoiceBoard::class)->name('invoice');
    Route::get('/app/audit', AuditLogBoard::class)->name('audit');
    Route::get('/app/notifikasi', [NotificationController::class, 'index'])->name('notifikasi');
    Route::post('/app/notifikasi/baca-semua', [NotificationController::class, 'markAllRead']);
    Route::get('/app/cari', [SearchController::class, 'index'])->name('cari');

    Route::get('/app/cetak/slip/{paymentId}', [ExportController::class, 'slip'])->name('cetak.slip');
    Route::get('/app/cetak/surat-jalan/{shipmentId}', [ExportController::class, 'suratJalan'])->name('cetak.suratjalan');
    Route::get('/app/cetak/invoice/{invoiceId}', [ExportController::class, 'invoice'])->name('cetak.invoice');

    Route::get('/portal', function () {
        abort_unless(auth()->user()->hasRole('PETANI'), 403);
        $riwayat = Receipt::latest('id')->limit(10)->get();
        $terakhir = $riwayat->first();

        return view('portal.index', ['terakhir' => $terakhir, 'insp' => $terakhir?->inspectionTerakhir(), 'riwayat' => $riwayat]);
    })->name('portal');

    Route::get('/portal/penerimaan/{receiptId}', ReceiptDetail::class)->name('portal.penerimaan.detail');
});

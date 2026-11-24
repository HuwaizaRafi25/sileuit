<?php

use App\Modules\IdentityAccess\Http\Controllers\LoginController;
use App\Modules\InventoryLedger\Livewire\AdjustmentBoard;
use App\Modules\InventoryLedger\Livewire\TransferBoard;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\OrderFulfillment\Livewire\OrderBoard;
use App\Modules\ProcurementReceiving\Livewire\ReceiptDetail;
use App\Modules\ProcurementReceiving\Livewire\ReceiptWizard;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [LoginController::class, 'showStaff'])->name('login');
Route::post('/login', [LoginController::class, 'loginStaff']);
Route::get('/login/petani', [LoginController::class, 'showPetani']);
Route::post('/login/petani', [LoginController::class, 'loginPetani']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/app/beranda', 'app.beranda')->name('beranda');

    Route::get('/app/penerimaan', function () {
        abort_unless(auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'), 403);

        return view('app.penerimaan', ['daftar' => Receipt::with(['contract.partner', 'location'])->latest('id')->paginate(15)]);
    })->name('penerimaan');

    Route::get('/app/terima', ReceiptWizard::class)->name('terima');
    Route::get('/app/penerimaan/{receiptId}', ReceiptDetail::class)->name('penerimaan.detail');

    Route::get('/app/stok', function () {
        abort_unless(auth()->user()->can('lihat-stok'), 403);
        $u = auth()->user();
        $q = StockBalance::with(['lot.commodity', 'lot.grade', 'location'])
            ->where('on_hand_kg', '>', 0);
        if (! $u->isGlobal()) {
            $q->whereIn('location_id', $u->locationScopes->pluck('id'));
        }

        return view('app.stok', ['saldo' => $q->get()]);
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

    Route::get('/portal', function () {
        abort_unless(auth()->user()->hasRole('PETANI'), 403);
        $riwayat = Receipt::latest('id')->limit(10)->get();
        $terakhir = $riwayat->first();

        return view('portal.index', ['terakhir' => $terakhir, 'insp' => $terakhir?->inspectionTerakhir(), 'riwayat' => $riwayat]);
    })->name('portal');
});

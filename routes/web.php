<?php

use App\Modules\IdentityAccess\Http\Controllers\LoginController;
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

    Route::get('/portal', function () {
        abort_unless(auth()->user()->hasRole('PETANI'), 403);
        $riwayat = Receipt::latest('id')->limit(10)->get();
        $terakhir = $riwayat->first();

        return view('portal.index', [
            'terakhir' => $terakhir,
            'insp' => $terakhir?->inspectionTerakhir(),
            'riwayat' => $riwayat,
        ]);
    })->name('portal');
});

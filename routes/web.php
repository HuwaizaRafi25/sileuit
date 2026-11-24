<?php

use App\Modules\IdentityAccess\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [LoginController::class, 'showStaff'])->name('login');
Route::post('/login', [LoginController::class, 'loginStaff']);
Route::get('/login/petani', [LoginController::class, 'showPetani']);
Route::post('/login/petani', [LoginController::class, 'loginPetani']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/app/beranda', 'app.beranda')->name('beranda');
    Route::get('/portal', function () {
        abort_unless(auth()->user()->hasRole('PETANI'), 403);

        return view('portal.index');
    })->name('portal');
});

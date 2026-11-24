<?php

namespace App\Providers;

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Receipt;
use App\Modules\ProcurementReceiving\Policies\ReceiptPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Receipt::class, ReceiptPolicy::class);

        Gate::define('approve-price', fn (User $u) => $u->hasRole('PEMILIK'));
        Gate::define('create-price', fn (User $u) => $u->hasRole('PENGADAAN'));
        Gate::define('manage-users', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN'));
        Gate::define('approve-contract', fn (User $u) => $u->hasRole('PEMILIK'));

        Gate::define('lihat-stok', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN', 'PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KASIR'));
        Gate::define('buat-transfer', fn (User $u) => $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG'));
        Gate::define('terima-transfer', fn (User $u) => $u->hasRole('KEPALA_GUDANG'));
        Gate::define('buat-penyesuaian', fn (User $u) => $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG'));
        Gate::define('buat-pesanan', fn (User $u) => $u->hasRole('KASIR', 'ADMIN', 'KEPALA_GUDANG'));
        Gate::define('alokasi-pesanan', fn (User $u) => $u->hasRole('ADMIN', 'KEPALA_GUDANG'));
    }
}

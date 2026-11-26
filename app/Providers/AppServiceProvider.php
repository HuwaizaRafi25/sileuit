<?php

namespace App\Providers;

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Receipt;
use App\Modules\ProcurementReceiving\Policies\ReceiptPolicy;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Receipt::class, ReceiptPolicy::class);

        Gate::define('approve-price', fn (User $u) => $u->hasRole('PEMILIK'));
        Gate::define('create-price', fn (User $u) => $u->hasRole('PENGADAAN', 'PEMILIK', 'ADMIN'));
        Gate::define('manage-users', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN'));
        Gate::define('approve-contract', fn (User $u) => $u->hasRole('PEMILIK'));

        Gate::define('lihat-stok', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN', 'PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KASIR'));
        Gate::define('lihat-transfer', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KURIR'));
        Gate::define('buat-transfer', fn (User $u) => $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG'));
        Gate::define('terima-transfer', fn (User $u) => $u->hasRole('KEPALA_GUDANG'));
        Gate::define('lihat-penyesuaian', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN', 'PETUGAS_GUDANG', 'KEPALA_GUDANG'));
        Gate::define('buat-penyesuaian', fn (User $u) => $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG'));
        Gate::define('buat-pesanan', fn (User $u) => $u->hasRole('KASIR', 'ADMIN', 'KEPALA_GUDANG'));
        Gate::define('alokasi-pesanan', fn (User $u) => $u->hasRole('ADMIN', 'KEPALA_GUDANG'));

        Gate::define('pos-jual', fn (User $u) => $u->hasRole('KASIR'));
        Gate::define('lihat-fulfillment', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KURIR'));
        Gate::define('lihat-retur', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'KEPALA_GUDANG', 'PETUGAS_GUDANG', 'KASIR', 'MITRA_B2B', 'KURIR'));
        Gate::define('buat-retur', fn (User $u) => $u->hasRole('KASIR', 'PETUGAS_GUDANG', 'MITRA_B2B', 'KURIR', 'ADMIN', 'KEPALA_GUDANG'));
        Gate::define('retur-approve', fn (User $u) => $u->hasRole('KEPALA_GUDANG'));

        Gate::define('terbit-invoice', fn (User $u) => $u->hasRole('KEUANGAN', 'ADMIN', 'PEMILIK'));
        Gate::define('lihat-invoice', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN', 'KASIR', 'MITRA_B2B'));
        Gate::define('lihat-audit', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN'));
        Gate::define('lihat-laporan', fn (User $u) => $u->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN', 'KEPALA_GUDANG', 'PENGADAAN'));

        $this->app->singleton(
            LogoutResponse::class,
            function () {
                return new class implements LogoutResponse
                {
                    public function toResponse($request): RedirectResponse
                    {
                        return redirect('/login');
                    }
                };
            }
        );

        Livewire::resolveMissingComponent(function (string $name): ?string {
            if (! str_starts_with($name, 'app.modules.')) {
                return null;
            }

            $class = collect(explode('.', $name))
                ->map(fn (string $segment): string => (string) str($segment)->studly())
                ->implode('\\');

            return class_exists($class) ? $class : null;
        });
    }
}

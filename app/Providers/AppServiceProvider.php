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
    }
}

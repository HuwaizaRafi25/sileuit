<?php

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\IdentityAccess\Models\Role;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Partner;
use Database\Seeders\InvoiceBaselineSeeder;

beforeEach(fn () => $this->seed());

it('mitra B2B hanya melihat invoice miliknya (BR-16)', function () {
    $this->seed(InvoiceBaselineSeeder::class);
    $r01 = Partner::where('nama', 'Retail Contoh Satu')->first();

    // buat user mitra terikat ke r01
    $mitra = User::create([
        'name' => 'User Mitra R01', 'email' => 'mitra-r01@sileuit.id', 'status' => 'AKTIF',
        'password' => bcrypt('x'), 'partner_id' => $r01->id, 'active_location_id' => null,
    ]);
    $role = Role::where('kode', 'MITRA_B2B')->first();
    $mitra->roles()->attach($role->id);

    $this->actingAs($mitra);
    $nos = Invoice::pluck('no')->all();
    expect($nos)->toContain('INV-BASE-001')
        ->and(Invoice::whereHas('partner', fn ($q) => $q->where('nama', '<>', 'Retail Contoh Satu'))->count())->toBe(0);
});

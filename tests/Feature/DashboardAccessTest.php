<?php

use App\Modules\IdentityAccess\Models\User;

beforeEach(fn () => $this->seed());

it('dashboard dapat diakses semua peran staf dan menampilkan metrik sesuai peran', function () {
    $kg = User::where('email', 'kg-g1@sileuit.id')->first();
    $this->actingAs($kg)->get('/app/beranda')->assertOk()->assertSee('Stok on-hand');

    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $this->actingAs($pg)->get('/app/beranda')->assertOk()->assertSee('+ Terima gabah');
});

it('audit trail hanya bisa dibuka Pemilik/Admin', function () {
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $this->actingAs($pg)->get('/app/audit')->assertForbidden();

    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
    $this->actingAs($pemilik)->get('/app/audit')->assertOk()->assertSee('Audit trail');
});

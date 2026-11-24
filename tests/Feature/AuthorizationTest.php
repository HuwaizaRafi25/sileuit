<?php

use App\Modules\AuditNotification\Models\AuditLog;
use App\Modules\IdentityAccess\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => $this->seed());

it('kasir ditolak membuka daftar harga di server dan tercatat di audit (TC-26)', function () {
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    $this->actingAs($kasir)->get('/admin/price-list-items')->assertForbidden();
    expect(AuditLog::where('aksi', 'AKSES_DITOLAK')->where('user_id', $kasir->id)->exists())->toBeTrue();
});

it('petugas gudang ditolak membuka manajemen pengguna (TC-26)', function () {
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $this->actingAs($pg)->get('/admin/users')->assertForbidden();
    expect(AuditLog::where('aksi', 'AKSES_DITOLAK')->where('user_id', $pg->id)->exists())->toBeTrue();
});

it('hanya Pemilik yang boleh menyetujui harga (BR-01)', function () {
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();
    expect(Gate::forUser($pemilik)->allows('approve-price'))->toBeTrue();
    expect(Gate::forUser($kasir)->allows('approve-price'))->toBeFalse();
});

<?php

use App\Modules\AuditNotification\Models\AuditLog;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed();
    Route::middleware(['web', 'auth', 'location.scope'])->get('/_uji/lokasi/{location}', fn (Location $location) => response()->json(['ok' => true]));
});

it('menolak akses lintas lokasi untuk pengguna cakupan G dan mencatat audit (mekanisme TC-08)', function () {
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $g2 = Location::where('kode', 'G2')->first();
    $this->actingAs($pg)->getJson('/_uji/lokasi/'.$g2->id)->assertForbidden();
    expect(AuditLog::where('aksi', 'AKSES_DITOLAK')->where('user_id', $pg->id)->exists())->toBeTrue();
});

it('mengizinkan akses ke lokasi sendiri', function () {
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $g1 = Location::where('kode', 'G1')->first();
    $this->actingAs($pg)->getJson('/_uji/lokasi/'.$g1->id)->assertOk();
});

it('pengguna global boleh mengakses lokasi mana pun', function () {
    $adm = User::where('email', 'adm-1@sileuit.id')->first();
    $g2 = Location::where('kode', 'G2')->first();
    $this->actingAs($adm)->getJson('/_uji/lokasi/'.$g2->id)->assertOk();
});

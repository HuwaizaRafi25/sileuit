<?php

use App\Filament\Resources\Contracts\Pages\ManageContracts;
use App\Filament\Resources\HetConfigs\Pages\ManageHetConfigs;
use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Filament\Resources\PriceListItems\Pages\ManagePriceListItems;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Partner;
use App\Modules\ProcurementReceiving\Models\StandardPackage;
use Livewire\Livewire;

beforeEach(fn () => $this->seed());

it('seluruh halaman CRUD master di /admin dapat dibuka oleh Pemilik', function () {
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();

    $slugs = [
        '/admin/users',
        '/admin/partners',
        '/admin/locations',
        '/admin/commodities',
        '/admin/grades',
        '/admin/standard-packages',
        '/admin/uom-conversions',
        '/admin/contracts',
        '/admin/harvest-plans',
        '/admin/price-list-items',
        '/admin/het-configs',
    ];

    foreach ($slugs as $slug) {
        $this->actingAs($pemilik)->get($slug)->assertOk();
    }
});

it('membuat Kasir mewajibkan 1 lokasi Cabang dan menyinkronkan cakupan lokasi tunggal', function () {
    $admin = User::where('email', 'adm-1@sileuit.id')->first();
    $gudang = Location::where('kode', 'G1')->first();
    $cabang = Location::where('kode', 'C2')->first();

    Livewire::actingAs($admin)
        ->test(ManageUsers::class)
        ->callAction('create', data: [
            'role_kode' => 'KASIR',
            'assigned_location_id' => $gudang->id,
            'name' => 'Kasir Salah Lokasi',
            'email' => 'kasir-salah@sileuit.id',
            'password' => 'Sileuit#2026',
            'status' => 'AKTIF',
        ])
        ->assertHasActionErrors(['assigned_location_id']);

    Livewire::actingAs($admin)
        ->test(ManageUsers::class)
        ->callAction('create', data: [
            'role_kode' => 'KASIR',
            'assigned_location_id' => $cabang->id,
            'name' => 'Kasir Baru C2',
            'email' => 'kasir-baru@sileuit.id',
            'password' => 'Sileuit#2026',
            'status' => 'AKTIF',
        ])
        ->assertHasNoActionErrors();

    $created = User::where('email', 'kasir-baru@sileuit.id')->first();
    expect($created)->not->toBeNull();
    expect($created->roles()->pluck('kode')->all())->toBe(['KASIR']);
    expect($created->active_location_id)->toBe($cabang->id);
    expect($created->locationScopes()->pluck('locations.id')->all())->toBe([$cabang->id]);
    expect($created->partner_id)->toBeNull();
});

it('membuat Petani Mitra menormalisasi nomor HP ke +62, menyetel PIN, tanpa email/lokasi, dan bisa login portal', function () {
    $admin = User::where('email', 'adm-1@sileuit.id')->first();
    $petaniBaru = Partner::create([
        'tipe' => 'FARMER',
        'nama' => 'Petani Baru Cianjur',
        'hp' => '08123450009',
        'kelompok' => 'Tani Lestari',
        'status' => 'AKTIF',
    ]);

    expect($petaniBaru->hp)->toBe('+628123450009');

    Livewire::actingAs($admin)
        ->test(ManageUsers::class)
        ->callAction('create', data: [
            'role_kode' => 'PETANI',
            'partner_id' => $petaniBaru->id,
            'name' => 'Petani Baru Cianjur',
            'hp' => '08123450009',
            'pin_hash' => '654321',
            'status' => 'AKTIF',
        ])
        ->assertHasNoActionErrors();

    $user = User::where('partner_id', $petaniBaru->id)->first();
    expect($user)->not->toBeNull();
    expect($user->roles()->pluck('kode')->all())->toBe(['PETANI']);
    expect($user->email)->toBeNull();
    expect($user->active_location_id)->toBeNull();
    expect($user->locationScopes()->count())->toBe(0);
    expect($user->hp)->toBe('+628123450009');

    $this->post('/login/petani', ['hp' => '08123450009', 'pin' => '654321'])
        ->assertRedirect('/portal');
});

it('form mitra menolak nomor HP duplikat pada jenis mitra yang sama', function () {
    $admin = User::where('email', 'adm-1@sileuit.id')->first();

    Livewire::actingAs($admin)
        ->test(ManagePartners::class)
        ->callAction('create', data: [
            'tipe' => 'FARMER',
            'nama' => 'Petani Duplikat',
            'hp' => '08111111001',
            'status' => 'AKTIF',
        ])
        ->assertHasActionErrors(['hp']);
});

it('Admin/Pengadaan tidak dapat mengaktifkan kontrak secara sepihak tanpa persetujuan Pemilik', function () {
    $pengadaan = User::where('email', 'pengadaan-1@sileuit.id')->first();
    $petani = Partner::where('tipe', 'FARMER')->first();
    $paket = StandardPackage::first();

    Livewire::actingAs($pengadaan)
        ->test(ManageContracts::class)
        ->callAction('create', data: [
            'partner_id' => $petani->id,
            'standard_package_id' => $paket->id,
            'musim' => '2027/2028',
            'mulai' => '2027-01-01',
            'selesai' => '2027-06-30',
            'estimasi_kg' => 2500,
            'status' => 'AKTIF',
        ])
        ->assertHasActionErrors(['status']);

    expect(Contract::where('musim', '2027/2028')->exists())->toBeFalse();

    Livewire::actingAs($pengadaan)
        ->test(ManageContracts::class)
        ->callAction('create', data: [
            'partner_id' => $petani->id,
            'standard_package_id' => $paket->id,
            'musim' => '2027/2028',
            'mulai' => '2027-01-01',
            'selesai' => '2027-06-30',
            'estimasi_kg' => 2500,
        ])
        ->assertHasNoActionErrors();

    $kontrak = Contract::where('musim', '2027/2028')->first();
    expect($kontrak)->not->toBeNull();
    expect($kontrak->status)->toBe('DRAFT');
});

it('Harga Beli menolak pasangan komoditas dan grade yang tidak cocok', function () {
    $pengadaan = User::where('email', 'pengadaan-1@sileuit.id')->first();
    $gabah = Commodity::where('nama', 'GABAH')->first();
    $beras = Commodity::where('nama', 'BERAS')->first();
    $gradeBeras = Grade::where('commodity_id', $beras->id)->first();

    Livewire::actingAs($pengadaan)
        ->test(ManagePriceListItems::class)
        ->callAction('create', data: [
            'commodity_id' => $gabah->id,
            'grade_id' => $gradeBeras->id,
            'harga_per_kg' => 7200,
            'berlaku_dari' => '2027-01-01',
        ])
        ->assertHasActionErrors(['grade_id']);
});

it('Konfigurasi HET menolak lebih dari satu aturan aktif untuk komoditas yang sama', function () {
    $pemilik = User::where('email', 'pemilik-1@sileuit.id')->first();
    $beras = Commodity::where('nama', 'BERAS')->first();

    Livewire::actingAs($pemilik)
        ->test(ManageHetConfigs::class)
        ->callAction('create', data: [
            'commodity_id' => $beras->id,
            'harga_per_kg' => 14900,
            'mode' => 'BLOKIR',
            'aktif' => true,
        ])
        ->assertHasNoActionErrors();

    Livewire::actingAs($pemilik)
        ->test(ManageHetConfigs::class)
        ->callAction('create', data: [
            'commodity_id' => $beras->id,
            'harga_per_kg' => 15500,
            'mode' => 'PERINGATAN',
            'aktif' => true,
        ])
        ->assertHasActionErrors(['aktif']);
});

<?php

use App\Modules\IdentityAccess\Models\User;

it('staf lapangan login benar diarahkan ke beranda', function () {
    $this->seed();
    $this->post('/login', ['email' => 'pg-g1@sileuit.id', 'password' => 'Sileuit#2026'])->assertRedirect('/app/beranda');
});

it('staf backoffice login benar diarahkan ke panel admin', function () {
    $this->seed();
    $this->post('/login', ['email' => 'adm-1@sileuit.id', 'password' => 'Sileuit#2026'])->assertRedirect('/admin');
});

it('lima kali salah mengunci akun 15 menit (DEV-005)', function () {
    $this->seed();
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'pg-g1@sileuit.id', 'password' => 'salah']);
    }
    $response = $this->post('/login', ['email' => 'pg-g1@sileuit.id', 'password' => 'salah']);
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('dikunci');
});

it('petani login HP dan PIN masuk portal', function () {
    $this->seed();
    $this->post('/login/petani', ['hp' => '8111111001', 'pin' => '123456'])->assertRedirect('/portal');
});

it('PIN petani salah ditolak', function () {
    $this->seed();
    $this->post('/login/petani', ['hp' => '8111111001', 'pin' => '999999'])->assertSessionHasErrors('hp');
});

it('menampilkan sepuluh tombol login demo saat aplikasi lokal', function () {
    config(['app.env' => 'local']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('Akses Demo (Lokal Saja)')
        ->assertSee('Pemilik')
        ->assertSee('Admin Operasional')
        ->assertSee('Staf Pengadaan')
        ->assertSee('Petugas Gudang (G1)')
        ->assertSee('Kepala Gudang (G1)')
        ->assertSee('Kurir')
        ->assertSee('Kasir (Cabang 1)')
        ->assertSee('Keuangan')
        ->assertSee('Petani Mitra')
        ->assertSee('Mitra Retail B2B');
});

it('login demo masuk memakai akun tetap untuk peran yang dipilih', function () {
    $this->seed();
    config(['app.env' => 'local']);

    $response = $this->post('/login/demo/PETUGAS_GUDANG');

    $response->assertRedirect('/app/beranda');
    $this->assertAuthenticatedAs(User::where('email', 'pg-g1@sileuit.id')->first());
});

it('login demo petani diarahkan ke portal petani', function () {
    $this->seed();
    config(['app.env' => 'local']);

    $response = $this->post('/login/demo/PETANI');

    $response->assertRedirect('/portal');
    $this->assertAuthenticatedAs(User::where('hp_hash', hash('sha256', '+628111111001'))->first());
});

it('login demo mitra retail diarahkan ke aplikasi operasional', function () {
    $this->seed();
    config(['app.env' => 'local']);

    $response = $this->post('/login/demo/MITRA_B2B');

    $response->assertRedirect('/app/beranda');
    $this->assertAuthenticatedAs(User::where('email', 'retail-contoh-satu@mitra.sileuit.id')->first());
});

it('menolak peran demo yang tidak terdaftar', function () {
    $this->seed();
    config(['app.env' => 'local']);

    $this->post('/login/demo/ROLE_TIDAK_ADA')->assertNotFound();

    $this->assertGuest();
});

it('menolak akun demo yang sudah tidak aktif', function () {
    $this->seed();
    config(['app.env' => 'local']);
    User::where('email', 'pg-g1@sileuit.id')->update(['status' => 'NONAKTIF']);

    $this->post('/login/demo/PETUGAS_GUDANG')->assertNotFound();

    $this->assertGuest();
});

it('menyembunyikan dan menolak login demo di luar lingkungan lokal', function () {
    $this->seed();
    config(['app.env' => 'production']);

    $this->get('/login')->assertOk()->assertDontSee('Akses Demo (Lokal Saja)');
    $this->post('/login/demo/ADMIN')->assertNotFound();

    $this->assertGuest();
});

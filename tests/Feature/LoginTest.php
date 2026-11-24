<?php

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

<?php

use App\Modules\AuditNotification\Models\AuditLog;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Receipt;

beforeEach(fn () => $this->seed());

function receiptMilik(string $emailPetugas, string $noDok, ?int $contractId = null): Receipt
{
    $u = User::where('email', $emailPetugas)->first();

    return Receipt::withoutGlobalScopes()->create([
        'no_dok' => $noDok, 'contract_id' => $contractId ?? Contract::first()->id,
        'location_id' => $u->active_location_id, 'bruto_kg' => 1050, 'tara_kg' => 50, 'netto_kg' => 1000,
        'status' => 'CONFIRMED', 'harga_per_kg' => 6800, 'nilai_bayar' => 6800000,
        'diterima_oleh' => $u->id, 'waktu_timbang' => now(), 'idempotency_key' => 'key-'.$noDok,
    ]);
}

it('TC-08: pg-g1 membuka dokumen G2 lewat URL dan API ditolak 403 + audit', function () {
    $g2 = receiptMilik('pg-g2@sileuit.id', 'GRN-G2-001');
    $pg1 = User::where('email', 'pg-g1@sileuit.id')->first();

    $this->actingAs($pg1)->get('/app/penerimaan/'.$g2->id)->assertForbidden();
    $this->actingAs($pg1)->postJson("/api/v1/receipts/{$g2->id}/pay", ['metode' => 'TUNAI', 'referensi' => 'X', 'idempotency_key' => 'k'])->assertForbidden();

    expect(AuditLog::where('aksi', 'AKSES_DITOLAK')->where('user_id', $pg1->id)->count())->toBeGreaterThan(0);
});

it('TC-08: daftar penerimaan pg-g1 tidak memuat dokumen G2', function () {
    receiptMilik('pg-g2@sileuit.id', 'GRN-G2-002');
    $pg1 = User::where('email', 'pg-g1@sileuit.id')->first();

    $this->actingAs($pg1);
    expect(Receipt::pluck('no_dok')->all())->not->toContain('GRN-G2-002');
    $this->get('/app/penerimaan')->assertOk()->assertDontSee('GRN-G2-002');
});

it('TC-25: petani P-001 tidak bisa membuka penerimaan P-002', function () {
    $kontrak2 = Contract::whereHas('partner', fn ($q) => $q->where('nama', 'Petani Contoh B'))->first();
    $r2 = receiptMilik('pg-g1@sileuit.id', 'GRN-P002-001', $kontrak2->id);
    $p001 = User::where('hp_hash', hash('sha256', '+628111111001'))->first();

    $this->actingAs($p001)->get('/app/penerimaan/'.$r2->id)->assertForbidden();
    expect(AuditLog::where('aksi', 'AKSES_DITOLAK')->where('user_id', $p001->id)->exists())->toBeTrue();
});

it('TC-26: petugas memanggil API bayar petani dan kasir menyetujui selisih ditolak 403', function () {
    $r = receiptMilik('pg-g1@sileuit.id', 'GRN-TC26-001');
    $r->update(['status' => 'MENUNGGU_PERSETUJUAN']);
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $kasir = User::where('email', 'ksr-c1@sileuit.id')->first();

    $this->actingAs($pg)->postJson("/api/v1/receipts/{$r->id}/pay", ['metode' => 'TUNAI', 'referensi' => 'X', 'idempotency_key' => 'k1'])->assertForbidden();
    $this->actingAs($kasir)->postJson("/api/v1/receipts/{$r->id}/approve", ['setuju' => true, 'alasan' => 'coba'])->assertForbidden();
    expect($r->fresh()->status)->toBe('MENUNGGU_PERSETUJUAN');
});

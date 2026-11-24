<?php

use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Actions\PayFarmerAction;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed());

it('TC-06: duplikasi pembayaran petani ditolak (kunci sama & berbeda)', function () {
    $keu = User::where('email', 'keu-1@sileuit.id')->first();
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $kontrak = Contract::first();

    $r = Receipt::withoutGlobalScopes()->create([
        'no_dok' => 'GRN-PAY-001', 'contract_id' => $kontrak->id, 'location_id' => $pg->active_location_id,
        'bruto_kg' => 1050, 'tara_kg' => 50, 'netto_kg' => 1000, 'harga_per_kg' => 6800, 'nilai_bayar' => 6800000,
        'status' => 'CONFIRMED', 'diterima_oleh' => $pg->id, 'waktu_timbang' => now(), 'idempotency_key' => 'r-pay-1',
    ]);

    $aksi = app(PayFarmerAction::class);
    $p1 = $aksi->execute($r, $keu, 'TRANSFER', 'TRF-001', 'key-1');
    $p2 = $aksi->execute($r->fresh(), $keu, 'TRANSFER', 'TRF-001', 'key-1');

    expect($p2->id)->toBe($p1->id)->and(Payment::count())->toBe(1);
    expect($r->fresh()->status)->toBe('PAID');

    expect(fn () => $aksi->execute($r->fresh(), $keu, 'TRANSFER', 'TRF-002', 'key-2'))
        ->toThrow(ValidationException::class);
    expect(Payment::count())->toBe(1)->and(PaymentAllocation::where('receipt_id', $r->id)->count())->toBe(1);
});

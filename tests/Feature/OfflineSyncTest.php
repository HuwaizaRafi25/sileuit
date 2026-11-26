<?php

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed());

function pngBase64(): string
{
    return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
}

it('TC-14: kirim ulang entri offline yang sama hanya membuat satu penerimaan', function () {
    Storage::fake('s3');
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $kontrak = Contract::first();

    $payload = [
        'idempotency_key' => 'offline-key-1',
        'contract_id' => $kontrak->id,
        'location_id' => $pg->active_location_id,
        'harvest_plan_id' => null,
        'bruto_kg' => 1050, 'tara_kg' => 50,
        'kadar_air' => 13.5, 'kadar_hampa' => 2, 'butir_patah' => 3,
        'varietas' => 'Ciherang',
        'foto_base64' => pngBase64(),
    ];

    $this->actingAs($pg)->postJson('/api/v1/procurement/sync', $payload)->assertOk()->assertJsonPath('duplikat', false);
    $this->actingAs($pg)->postJson('/api/v1/procurement/sync', $payload)->assertOk()->assertJsonPath('duplikat', true);

    expect(Receipt::withoutGlobalScopes()->where('idempotency_key', 'offline-key-1')->count())->toBe(1);
    $r = Receipt::withoutGlobalScopes()->where('idempotency_key', 'offline-key-1')->first();
    expect($r->status)->toBe('CONFIRMED')->and((int) $r->nilai_bayar)->toBe(6800000);
});

it('dua kunci idempotency berbeda membuat dua penerimaan', function () {
    Storage::fake('s3');
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $kontrak = Contract::first();

    $base = [
        'contract_id' => $kontrak->id, 'location_id' => $pg->active_location_id, 'harvest_plan_id' => null,
        'bruto_kg' => 1050, 'tara_kg' => 50, 'kadar_air' => 13.5, 'kadar_hampa' => 2,
        'butir_patah' => 3, 'varietas' => 'Ciherang', 'foto_base64' => pngBase64(),
    ];

    $this->actingAs($pg)->postJson('/api/v1/procurement/sync', array_merge($base, ['idempotency_key' => 'key-A']))->assertOk();
    $this->actingAs($pg)->postJson('/api/v1/procurement/sync', array_merge($base, ['idempotency_key' => 'key-B']))->assertOk();

    expect(Receipt::withoutGlobalScopes()->whereIn('idempotency_key', ['key-A', 'key-B'])->count())->toBe(2);
});

it('sync offline dengan selisih melebihi toleransi masuk MENUNGGU_PERSETUJUAN', function () {
    Storage::fake('s3');
    $pg = User::where('email', 'pg-g1@sileuit.id')->first();
    $plan = HarvestPlan::first();

    $payload = [
        'idempotency_key' => 'offline-selisih-1',
        'contract_id' => $plan->contract_id,
        'location_id' => $pg->active_location_id,
        'harvest_plan_id' => $plan->id,
        'bruto_kg' => 950,
        'tara_kg' => 50,
        'kadar_air' => 13.5,
        'kadar_hampa' => 2,
        'varietas' => 'Ciherang',
        'selisih_alasan' => 'Sebagian gabah masih tertinggal di sawah.',
        'foto_base64' => pngBase64(),
    ];

    $this->actingAs($pg)
        ->postJson('/api/v1/procurement/sync', $payload)
        ->assertOk()
        ->assertJsonPath('status', 'MENUNGGU_PERSETUJUAN')
        ->assertJsonPath('duplikat', false);
});

<?php

use App\Modules\AuditNotification\Models\AlertNotification;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Database\Seeders\InvoiceBaselineSeeder;

beforeEach(fn () => $this->seed());

it('FR-17: notifikasi umur simpan dan invoice lewat jatuh tempo dibuat idempoten', function () {
    Lot::create([
        'kode_lot' => 'LOT-NOTIF-01',
        'commodity_id' => Commodity::where('nama', 'GABAH')->value('id'),
        'grade_id' => Grade::where('kode', 'A')->value('id'),
        'receipt_id' => null,
        'tgl_terima' => now()->subDays(170)->toDateString(),
        'batas_simpan' => now()->addDays(10)->toDateString(),
        'status' => 'TERSEDIA',
    ]);
    $this->seed(InvoiceBaselineSeeder::class);

    $this->artisan('sileuit:buat-notifikasi')->assertSuccessful();
    expect(AlertNotification::where('tipe', 'UMUR_SIMPAN')->count())->toBe(1);
    expect(AlertNotification::where('tipe', 'INVOICE_OVERDUE')->count())->toBeGreaterThan(0);

    $this->artisan('sileuit:buat-notifikasi')->assertSuccessful();
    expect(AlertNotification::where('tipe', 'UMUR_SIMPAN')->count())->toBe(1);
});

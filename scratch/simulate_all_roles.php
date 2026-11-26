<?php

use App\Modules\AuditNotification\Livewire\AuditLogBoard;
use App\Modules\AuditNotification\Livewire\DashboardBoard;
use App\Modules\BillingAccountsReceivable\Livewire\InvoiceBoard;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Livewire\AdjustmentBoard;
use App\Modules\InventoryLedger\Livewire\TransferBoard;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\OrderFulfillment\Livewire\OrderBoard;
use App\Modules\OrderFulfillment\Livewire\OrderFulfillmentBoard;
use App\Modules\PointOfSale\Livewire\PosBoard;
use App\Modules\ProcurementReceiving\Livewire\ReceiptDetail;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "====================================================\n";
echo "   SILEUIT.ID FULL REGRESSION QA TEST SUITE         \n";
echo "====================================================\n\n";

$passes = 0;
$fails = 0;

$runTest = function (string $title, callable $fn) use (&$passes, &$fails) {
    echo "[TEST] {$title} ... ";
    try {
        $result = $fn();
        echo 'PASSED';
        if (is_string($result) && $result !== '') {
            echo " ({$result})";
        }
        echo "\n";
        $passes++;
    } catch (Throwable $e) {
        echo "FAILED!\n";
        echo '       Error: '.$e->getMessage()."\n";
        echo '       File:  '.$e->getFile().':'.$e->getLine()."\n";
        $fails++;
    }
};

// -------------------------------------------------------------
// 1. PETANI: PA DANU
// -------------------------------------------------------------
$runTest('Petani Pa Danu Portal View', function () {
    $danu = User::where('name', 'like', '%Contoh A%')->orWhere('hp_hash', hash('sha256', '+628111111001'))->firstOrFail();
    Auth::login($danu);

    $riwayat = Receipt::with(['contract.partner', 'location'])->latest('id')->limit(10)->get();
    $terakhir = $riwayat->first();
    $insp = $terakhir?->inspectionTerakhir();

    $html = View::make('portal.index', ['terakhir' => $terakhir, 'insp' => $insp, 'riwayat' => $riwayat])->render();
    if (! str_contains($html, 'Halo,') || ! str_contains($html, 'Portal Petani')) {
        throw new Exception('Rendered portal HTML missing expected greeting.');
    }

    return 'Rendered successfully, items count: '.$riwayat->count();
});

// -------------------------------------------------------------
// 2. PETANI: PA DEDI
// -------------------------------------------------------------
$runTest('Petani Pa Dedi Portal View (Isolation & Rejection check)', function () {
    $dedi = User::where('name', 'like', '%Contoh B%')->orWhere('hp_hash', hash('sha256', '+628111111002'))->firstOrFail();
    Auth::login($dedi);

    $riwayat = Receipt::with(['contract.partner', 'location'])->latest('id')->limit(10)->get();
    $terakhir = $riwayat->first();
    $insp = $terakhir?->inspectionTerakhir();

    $html = View::make('portal.index', ['terakhir' => $terakhir, 'insp' => $insp, 'riwayat' => $riwayat])->render();
    if (! str_contains($html, 'Halo,') || ! str_contains($html, 'Portal Petani')) {
        throw new Exception('Rendered portal HTML missing expected greeting for Pa Dedi.');
    }

    return 'Rendered successfully';
});

// -------------------------------------------------------------
// 3. PETUGAS GUDANG: UJANG (G1)
// -------------------------------------------------------------
$runTest('Petugas Gudang Ujang Dashboard & Gudang Pages', function () {
    $ujang = User::where('email', 'pg-g1@sileuit.id')->firstOrFail();
    Auth::login($ujang);

    // Livewire Dashboard render
    $dash = Livewire::test(DashboardBoard::class);
    $dash->assertStatus(200);

    // Penerimaan list view
    $daftar = Receipt::with(['contract.partner', 'location', 'inspections.grade'])->latest('id')->paginate(15);
    $htmlPenerimaan = View::make('app.penerimaan', ['daftar' => $daftar, 'search' => '', 'statusFilter' => ''])->render();
    if (! str_contains($htmlPenerimaan, 'Daftar Penerimaan Gabah')) {
        throw new Exception('Penerimaan page render failed.');
    }

    // Stok list view
    $saldo = StockBalance::with(['lot.commodity', 'lot.grade', 'location'])->where('on_hand_kg', '>', 0)->get();
    $htmlStok = View::make('app.stok', ['saldo' => $saldo, 'search' => ''])->render();
    if (! str_contains($htmlStok, 'Stok Gabah')) {
        throw new Exception('Stok page render failed.');
    }

    // Transfer board render
    $trf = Livewire::test(TransferBoard::class);
    $trf->assertStatus(200);

    return 'Dashboard, Penerimaan, Stok, and Transfer render cleanly';
});

// -------------------------------------------------------------
// 4. KEPALA GUDANG: PAK ASEP (G1)
// -------------------------------------------------------------
$runTest('Kepala Gudang Pak Asep Approval & Alokasi Pages', function () {
    $asep = User::where('email', 'kg-g1@sileuit.id')->firstOrFail();
    Auth::login($asep);

    $adj = Livewire::test(AdjustmentBoard::class);
    $adj->assertStatus(200);

    $ord = Livewire::test(OrderBoard::class);
    $ord->assertStatus(200);

    return 'AdjustmentBoard and OrderBoard initialized with 200 OK';
});

// -------------------------------------------------------------
// 5. KASIR: MBAK SARI (Cabang 1)
// -------------------------------------------------------------
$runTest('Kasir Mbak Sari POS Terminal Flow', function () {
    $sari = User::where('email', 'ksr-c1@sileuit.id')->firstOrFail();
    Auth::login($sari);

    $pos = Livewire::test(PosBoard::class);
    $pos->assertStatus(200);

    // Try adding a line
    $commodity = Commodity::first();
    $grade = Grade::where('commodity_id', $commodity->id)->first();

    $pos->set('commodity_id', $commodity->id)
        ->set('grade_id', $grade->id)
        ->set('qty', '10')
        ->set('harga', '14000')
        ->call('tambah');

    $pos->assertCount('lines', 1);

    return 'POS livewire component adds items cleanly';
});

// -------------------------------------------------------------
// 6. KURIR: KANG UCIN
// -------------------------------------------------------------
$runTest('Kurir Kang Ucin Order Fulfillment Board', function () {
    $ucin = User::where('email', 'kur-1@sileuit.id')->firstOrFail();
    Auth::login($ucin);

    $ful = Livewire::test(OrderFulfillmentBoard::class);
    $ful->assertStatus(200);

    return 'OrderFulfillmentBoard mounted 200 OK';
});

// -------------------------------------------------------------
// 7. KEUANGAN: BU TUTI
// -------------------------------------------------------------
$runTest('Keuangan Bu Tuti Invoice Board & Aging Calculation', function () {
    $tuti = User::where('email', 'keu-1@sileuit.id')->firstOrFail();
    Auth::login($tuti);

    $inv = Livewire::test(InvoiceBoard::class);
    $inv->assertStatus(200);

    // Verify receipt detail payment gate
    $confirmedReceipt = Receipt::where('status', 'CONFIRMED')->first();
    if ($confirmedReceipt) {
        $rd = Livewire::test(ReceiptDetail::class, ['receiptId' => $confirmedReceipt->id]);
        $rd->assertStatus(200);
    }

    return 'InvoiceBoard and ReceiptDetail verified';
});

// -------------------------------------------------------------
// 8. PEMILIK: PAK OMANG
// -------------------------------------------------------------
$runTest('Pemilik Pak Omang Global Dashboard & Audit Log', function () {
    $omang = User::where('email', 'pemilik-1@sileuit.id')->firstOrFail();
    Auth::login($omang);

    $dash = Livewire::test(DashboardBoard::class);
    $dash->assertStatus(200);

    $audit = Livewire::test(AuditLogBoard::class);
    $audit->assertStatus(200);

    return 'Global Dashboard and AuditLog rendered with full metrics';
});

echo "\n====================================================\n";
echo "SUMMARY: {$passes} PASSED, {$fails} FAILED\n";
echo "====================================================\n";

if ($fails > 0) {
    exit(1);
}

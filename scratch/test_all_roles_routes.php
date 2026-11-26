<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Modules\AuditNotification\Livewire\DashboardBoard;
use App\Modules\BillingAccountsReceivable\Livewire\InvoiceBoard;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Livewire\AdjustmentBoard;
use App\Modules\InventoryLedger\Livewire\TransferBoard;
use App\Modules\OrderFulfillment\Livewire\OrderFulfillmentBoard;
use App\Modules\OrderFulfillment\Livewire\ReturnBoard;
use App\Modules\PointOfSale\Livewire\PosBoard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

$users = [
    'KURIR' => 'kur-1@sileuit.id',
    'KASIR' => 'ksr-c1@sileuit.id',
    'PETUGAS_GUDANG' => 'pg-g1@sileuit.id',
    'KEPALA_GUDANG' => 'kg-g1@sileuit.id',
    'KEUANGAN' => 'keu-1@sileuit.id',
    'PEMILIK' => 'pemilik-1@sileuit.id',
    'ADMIN' => 'adm-1@sileuit.id',
];

echo "====================================================\n";
echo "    AUDIT AKSES & PERMISSIONS SEMUA ROLE STAF       \n";
echo "====================================================\n\n";

$errors = 0;

foreach ($users as $roleName => $email) {
    echo "--- Checking Role: {$roleName} ({$email}) ---\n";
    $user = User::where('email', $email)->firstOrFail();
    Auth::login($user);

    // 1. Dashboard
    try {
        $dash = Livewire::test(DashboardBoard::class);
        $dash->assertStatus(200);
        echo "  [OK] Dashboard Board (200)\n";
    } catch (Throwable $e) {
        echo '  [FAIL] Dashboard Board: '.$e->getMessage()."\n";
        $errors++;
    }

    // 2. Fulfillment
    if ($user->can('lihat-fulfillment')) {
        try {
            $ful = Livewire::test(OrderFulfillmentBoard::class);
            $ful->assertStatus(200);
            echo "  [OK] Order Fulfillment Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] Order Fulfillment Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }

    // 3. POS
    if ($user->can('pos-jual')) {
        try {
            $pos = Livewire::test(PosBoard::class);
            $pos->assertStatus(200);
            echo "  [OK] POS Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] POS Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }

    // 4. Invoices
    if ($user->can('lihat-invoice')) {
        try {
            $inv = Livewire::test(InvoiceBoard::class);
            $inv->assertStatus(200);
            echo "  [OK] Invoice Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] Invoice Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }

    // 5. Transfer
    if ($user->can('buat-transfer')) {
        try {
            $trf = Livewire::test(TransferBoard::class);
            $trf->assertStatus(200);
            echo "  [OK] Transfer Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] Transfer Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }

    // 6. Penyesuaian
    if ($user->can('buat-penyesuaian')) {
        try {
            $adj = Livewire::test(AdjustmentBoard::class);
            $adj->assertStatus(200);
            echo "  [OK] Adjustment Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] Adjustment Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }

    // 7. Retur
    if ($user->can('buat-retur')) {
        try {
            $ret = Livewire::test(ReturnBoard::class);
            $ret->assertStatus(200);
            echo "  [OK] Return Board (200)\n";
        } catch (Throwable $e) {
            echo '  [FAIL] Return Board: '.$e->getMessage()."\n";
            $errors++;
        }
    }
}

echo "\n====================================================\n";
echo "TOTAL ERRORS: {$errors}\n";
echo "====================================================\n";

if ($errors > 0) {
    exit(1);
}

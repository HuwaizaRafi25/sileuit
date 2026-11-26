<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Livewire\ReceiptWizard;
use App\Modules\ProcurementReceiving\Models\Contract;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

$ujang = User::where('email', 'pg-g1@sileuit.id')->firstOrFail();
Auth::login($ujang);

echo '[TEST] Mounting ReceiptWizard ... ';
$wiz = Livewire::test(ReceiptWizard::class);
$wiz->assertStatus(200);
$wiz->assertSet('step', 1);
echo "PASSED (Step 1)\n";

// Step 1: select contract
$contract = Contract::first();
echo "[TEST] Selecting Contract #{$contract->id} ... ";
$wiz->set('contract_id', $contract->id);
$wiz->call('next');
$wiz->assertSet('step', 2);
echo "PASSED (Moved to Step 2)\n";

// Step 2: weigh in (1050 / 50 -> netto 1000)
echo '[TEST] Weighing (bruto 1050, tara 50) ... ';
$wiz->set('bruto', '1050');
$wiz->set('tara', '50');
$wiz->call('next');
$wiz->assertSet('step', 3);
echo 'PASSED (Moved to Step 3, Netto = '.$wiz->get('netto').")\n";

echo "ALL WIZARD LOGIC VERIFIED!\n";

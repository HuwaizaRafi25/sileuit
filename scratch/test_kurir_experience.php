<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Modules\AuditNotification\Livewire\DashboardBoard;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Livewire\OrderFulfillmentBoard;
use App\Modules\OrderFulfillment\Livewire\ReturnBoard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

$kurir = User::where('email', 'kur-1@sileuit.id')->firstOrFail();
Auth::login($kurir);

echo '[TEST] Kurir DashboardBoard Render ... ';
$dash = Livewire::test(DashboardBoard::class);
$dash->assertStatus(200);
$dashHtml = $dash->html();
if (str_contains($dashHtml, '/app/penerimaan"')) {
    echo "WARNING: contains link to /app/penerimaan\n";
} else {
    echo "CLEAN (No forbidden /app/penerimaan link)\n";
}
if (str_contains($dashHtml, '/app/pesanan"')) {
    echo "WARNING: contains link to /app/pesanan\n";
} else {
    echo "CLEAN (No forbidden /app/pesanan link)\n";
}

echo '[TEST] Kurir OrderFulfillmentBoard Render ... ';
$ful = Livewire::test(OrderFulfillmentBoard::class);
$ful->assertStatus(200);
$fulHtml = $ful->html();
if (str_contains($fulHtml, '/app/pesanan"')) {
    echo "WARNING: contains link to /app/pesanan\n";
} else {
    echo "CLEAN (No forbidden /app/pesanan link)\n";
}

echo '[TEST] Kurir ReturnBoard Access ... ';
$ret = Livewire::test(ReturnBoard::class);
$ret->assertStatus(200);
echo "OK (Kurir can access ReturnBoard)\n";

echo "KURIR AUDIT COMPLETED!\n";

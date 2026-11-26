<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

$u = User::where('email', 'pemilik-1@sileuit.id')->firstOrFail();
Auth::login($u);

echo 'Filament Auth check: '.(Auth::user()?->canAccessPanel(Filament\Facades\Filament::getPanel('admin')) ? 'YES' : 'NO')."\n";
echo "SUCCESS!\n";

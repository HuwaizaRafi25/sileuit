<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Filament\Widgets\AdminAlertsWidget;
use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\LatestOrdersTableWidget;
use App\Filament\Widgets\LatestReceiptsTableWidget;
use App\Modules\IdentityAccess\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

$omang = User::where('email', 'pemilik-1@sileuit.id')->firstOrFail();
Auth::login($omang);

echo '[TEST] Filament Admin Panel Set Current ... ';
Filament::setCurrentPanel(Filament::getPanel('admin'));
echo 'OK ('.Filament::getCurrentPanel()->getId().")\n";

echo '[TEST] AdminStatsOverviewWidget ... ';
$w1 = Livewire::test(AdminStatsOverviewWidget::class);
$w1->assertStatus(200);
echo "OK\n";

echo '[TEST] AdminAlertsWidget ... ';
$w2 = Livewire::test(AdminAlertsWidget::class);
$w2->assertStatus(200);
echo "OK\n";

echo '[TEST] LatestReceiptsTableWidget ... ';
$w3 = Livewire::test(LatestReceiptsTableWidget::class);
$w3->assertStatus(200);
echo "OK\n";

echo '[TEST] LatestOrdersTableWidget ... ';
$w4 = Livewire::test(LatestOrdersTableWidget::class);
$w4->assertStatus(200);
echo "OK\n";

echo "ALL FILAMENT ADMIN DASHBOARD WIDGETS TESTED SUCCESSFULLY!\n";

<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Console\Kernel;

$stat = Stat::make('Stok On-Hand', '1.000 kg')
    ->description('Tersedia: 1.000 kg')
    ->descriptionIcon('heroicon-m-archive-box')
    ->color('success');

echo 'Stat class: '.get_class($stat)."\n";
echo "SUCCESS!\n";

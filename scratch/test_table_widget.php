<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Modules\ProcurementReceiving\Models\Receipt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Console\Kernel;

class TestTableWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(Receipt::query()->latest('id'))
            ->columns([
                TextColumn::make('no_dok')->label('No Dokumen'),
                TextColumn::make('status')->badge(),
            ]);
    }
}

$widget = new TestTableWidget;
echo 'TableWidget instantiated: '.get_class($widget)."\n";
echo "SUCCESS!\n";

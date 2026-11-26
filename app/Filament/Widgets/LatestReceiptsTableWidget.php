<?php

namespace App\Filament\Widgets;

use App\Modules\ProcurementReceiving\Models\Receipt;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestReceiptsTableWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Penerimaan Gabah Terkini')
            ->description('5 transaksi penimbangan dan uji mutu SNI terbaru')
            ->query(Receipt::query()->with(['contract.partner', 'location'])->latest('id')->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('no_dok')
                    ->label('No. Dokumen')
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('contract.partner.nama')
                    ->label('Petani Mitra')
                    ->default('—'),

                TextColumn::make('location.kode')
                    ->label('Gudang')
                    ->badge(),

                TextColumn::make('netto_kg')
                    ->label('Netto (kg)')
                    ->numeric(decimalPlaces: 1)
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CONFIRMED' => 'success',
                        'PAID' => 'primary',
                        'MENUNGGU_PERSETUJUAN' => 'warning',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('waktu_timbang')
                    ->label('Waktu Timbang')
                    ->dateTime('d M Y H:i')
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Detail')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Receipt $record): string => "/app/penerimaan/{$record->id}")
                    ->openUrlInNewTab(),
            ]);
    }
}

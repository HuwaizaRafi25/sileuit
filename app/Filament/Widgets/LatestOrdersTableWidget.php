<?php

namespace App\Filament\Widgets;

use App\Modules\OrderFulfillment\Models\SalesOrder;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersTableWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pesanan & Pengiriman Terkini')
            ->description('Aktivitas pemenuhan pesanan penjualan retail dan B2B')
            ->query(SalesOrder::query()->with('partner')->latest('id')->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('no_dok')
                    ->label('No. Pesanan')
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('partner.nama')
                    ->label('Mitra / Pelanggan')
                    ->default('Penjualan Internal'),

                TextColumn::make('status')
                    ->label('Status Pesanan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DELIVERED' => 'success',
                        'ALLOCATED', 'PICKED', 'SHIPPED' => 'info',
                        'NEW' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('created_at')
                    ->label('Tanggal Buat')
                    ->dateTime('d M Y, H:i')
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Fulfillment')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (): string => '/app/pengiriman')
                    ->visible(fn (): bool => auth()->user()?->can('lihat-fulfillment') ?? false)
                    ->openUrlInNewTab(),
            ]);
    }
}

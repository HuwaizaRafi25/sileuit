<?php

namespace App\Filament\Widgets;

use App\Modules\BillingAccountsReceivable\Services\AgingCalculator;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $onHand = (float) StockBalance::sum('on_hand_kg');
        $reserved = (float) StockBalance::sum('reserved_kg');
        $tersedia = max(0, $onHand - $reserved);

        $terimaHariIniCount = Receipt::whereDate('waktu_timbang', today())->count();
        $terimaHariIniKg = (float) Receipt::whereDate('waktu_timbang', today())->sum('netto_kg');

        $piutangTotal = 0;
        try {
            $aging = app(AgingCalculator::class)->hitung();
            $piutangTotal = (int) ($aging['total'] ?? 0);
        } catch (\Throwable) {
            $piutangTotal = 0;
        }

        $pesananAktif = SalesOrder::whereIn('status', ['NEW', 'ALLOCATED', 'PICKED', 'SHIPPED'])->count();
        $pengirimanBerjalan = Shipment::whereIn('status', ['MENUNGGU', 'DIKIRIM'])->count();

        $u = auth()->user();

        return [
            Stat::make('Total Stok On-Hand', number_format($onHand, 1, ',', '.').' kg')
                ->description('Tersedia: '.number_format($tersedia, 1, ',', '.').' kg · Reserved: '.number_format($reserved, 1, ',', '.').' kg')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success')
                ->url($u?->can('lihat-stok') ? '/app/stok' : null),

            Stat::make('Penerimaan Hari Ini', $terimaHariIniCount.' Truk / Lot')
                ->description('Netto masuk: '.number_format($terimaHariIniKg, 1, ',', '.').' kg')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->url($u?->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN') ? '/app/penerimaan' : null),

            Stat::make('Piutang Berjalan (AR)', 'Rp '.number_format($piutangTotal, 0, ',', '.'))
                ->description('Faktur aktif & mitigasi risiko limit kredit')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->url($u?->can('lihat-invoice') ? '/app/invoice' : null),

            Stat::make('Pesanan & Pengiriman', $pesananAktif.' Pesanan Aktif')
                ->description('Dalam armada kurir: '.$pengirimanBerjalan.' pengiriman')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info')
                ->url($u?->can('lihat-fulfillment') ? '/app/pengiriman' : ($u?->can('buat-pesanan') ? '/app/pesanan' : null)),
        ];
    }
}

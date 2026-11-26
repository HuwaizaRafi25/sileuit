<?php

namespace App\Filament\Widgets;

use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Filament\Widgets\Widget;

class AdminAlertsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.admin-alerts-widget';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $pendingReceipts = Receipt::where('status', 'MENUNGGU_PERSETUJUAN')->count();
        $pendingAdjustments = StockAdjustment::where('status', 'MENUNGGU')->count();
        $pendingReturns = ReturnDoc::where('status', 'REQUESTED')->count();
        $lotMendekati = Lot::where('status', 'TERSEDIA')->get()->filter(fn ($l) => $l->persenUmur() >= 80)->count();

        return [
            'pendingReceipts' => $pendingReceipts,
            'pendingAdjustments' => $pendingAdjustments,
            'pendingReturns' => $pendingReturns,
            'lotMendekati' => $lotMendekati,
            'totalAlerts' => $pendingReceipts + $pendingAdjustments + $pendingReturns + $lotMendekati,
        ];
    }
}

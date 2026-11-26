<?php

namespace App\Modules\OrderFulfillment\Livewire;

use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\OrderFulfillment\Actions\PickOrderAction;
use App\Modules\OrderFulfillment\Actions\ReceiveOrderAction;
use App\Modules\OrderFulfillment\Actions\ShipOrderAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\Shipment;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderFulfillmentBoard extends Component
{
    public array $diterima = [];

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-fulfillment'), 403);
    }

    public function pick(int $orderId, PickOrderAction $aksi): void
    {
        try {
            $sj = $aksi->execute(SalesOrder::withoutGlobalScopes()->findOrFail($orderId), auth()->user());
            $this->pesan = 'Surat jalan '.$sj->no_dok.' dibuat (status PICKED).';
        } catch (ValidationException $e) {
            $this->addError('aksi', collect($e->errors())->flatten()->first());
        }
    }

    public function kirim(int $shipmentId, ShipOrderAction $aksi): void
    {
        try {
            $aksi->execute(Shipment::withoutGlobalScopes()->findOrFail($shipmentId), auth()->user());
            $this->pesan = 'Dikirim. Stok keluar dari ledger asal.';
        } catch (ValidationException $e) {
            $this->addError('aksi', collect($e->errors())->flatten()->first());
        }
    }

    public function terima(int $shipmentId, ReceiveOrderAction $aksi): void
    {
        $raw = $this->diterima[$shipmentId] ?? 0;
        $kg = (float) str_replace(',', '.', trim((string) $raw));
        try {
            $aksi->execute(Shipment::withoutGlobalScopes()->findOrFail($shipmentId), auth()->user(), $kg);
            $this->pesan = 'Penerimaan tercatat.';
        } catch (ValidationException $e) {
            $this->addError('aksi', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.order-fulfillment-board', [
            'pesanan' => SalesOrder::with('lines.commodity', 'lines.grade')->whereIn('status', ['ALLOCATED', 'PARTIAL', 'PICKED', 'SHIPPED'])->latest()->get(),
            'pengiriman' => Shipment::with('order')->whereIn('status', ['MENUNGGU', 'DIKIRIM', 'DITERIMA'])->latest()->limit(20)->get(),
            'transferKurir' => StockTransfer::with(['asal', 'tujuan', 'kurir', 'lines.lot.commodity'])
                ->whereNotNull('kurir_id')
                ->whereIn('status', ['SHIPPED', 'RECEIVED', 'DISCREPANCY'])
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }
}

<?php

namespace App\Modules\OrderFulfillment\Livewire;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderBoard extends Component
{
    public ?int $commodity_id = null;

    public ?int $grade_id = null;

    public string $qty = '';

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('buat-pesanan'), 403);
    }

    public function buat(): void
    {
        abort_unless(auth()->user()->can('alokasi-pesanan') || auth()->user()->can('buat-pesanan'), 403);
        $this->validate(['commodity_id' => 'required|exists:commodities,id', 'grade_id' => 'required|exists:grades,id', 'qty' => 'required|numeric|min:0.001']);

        $o = SalesOrder::withoutGlobalScopes()->create([
            'no_dok' => sprintf('SO-%s-%03d', now()->format('ymd'), SalesOrder::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1),
            'location_id' => auth()->user()->active_location_id ?? Location::where('kode', 'G1')->value('id'),
            'status' => 'NEW',
            'dibuat_oleh' => auth()->id(),
        ]);
        SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $this->commodity_id, 'grade_id' => $this->grade_id, 'qty_kg' => (float) $this->qty]);

        $this->reset(['commodity_id', 'grade_id', 'qty']);
        $this->pesan = 'Pesanan dibuat (NEW). Tekan Alokasi untuk memotong stok FEFO.';
    }

    public function alokasi(int $id, bool $override, AllocateOrderStockAction $aksi): void
    {
        abort_unless(auth()->user()->can('alokasi-pesanan'), 403);
        $o = SalesOrder::withoutGlobalScopes()->findOrFail($id);
        try {
            $hasil = $aksi->execute($o, auth()->user(), $override);
            $this->pesan = 'Status pesanan: '.$hasil->status;
        } catch (ValidationException $e) {
            $this->addError('alokasi', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.order-board', [
            'daftar' => SalesOrder::with(['lines.commodity', 'lines.grade', 'lines.reservations.lot'])->latest('id')->limit(30)->get(),
            'komoditas' => Commodity::all(),
            'grades' => Grade::with('commodity')->get(),
        ]);
    }
}

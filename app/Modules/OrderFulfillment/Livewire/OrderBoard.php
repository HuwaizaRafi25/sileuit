<?php

namespace App\Modules\OrderFulfillment\Livewire;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\OrderFulfillment\Actions\AllocateOrderStockAction;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderBoard extends Component
{
    public ?int $partner_id = null;

    public ?int $location_id = null;

    public ?int $lokasi_tujuan_id = null;

    public ?int $commodity_id = null;

    public ?int $grade_id = null;

    public string $qty = '';

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('buat-pesanan'), 403);
        $u = auth()->user();
        $activeLoc = $u->activeLocation;
        if ($activeLoc && $activeLoc->tipe === 'GUDANG') {
            $this->location_id = $activeLoc->id;
        } else {
            $this->location_id = Location::where('kode', 'G1')->value('id');
            if ($activeLoc && $activeLoc->tipe === 'CABANG') {
                $this->lokasi_tujuan_id = $activeLoc->id;
            }
        }
    }

    public function updatedPartnerId(): void
    {
        if ($this->partner_id) {
            $this->lokasi_tujuan_id = null;
        }
    }

    public function updatedCommodityId(): void
    {
        $this->grade_id = null;
    }

    public function buat(): void
    {
        abort_unless(auth()->user()->can('alokasi-pesanan') || auth()->user()->can('buat-pesanan'), 403);
        $this->qty = str_replace(',', '.', trim($this->qty));
        $this->validate([
            'partner_id' => 'nullable|exists:partners,id',
            'location_id' => 'required|exists:locations,id',
            'lokasi_tujuan_id' => 'nullable|exists:locations,id|different:location_id',
            'commodity_id' => 'required|exists:commodities,id',
            'grade_id' => 'required|exists:grades,id',
            'qty' => 'required|numeric|min:0.001',
        ]);

        $prefix = sprintf('SO-%s-', now()->format('ymd'));
        $urut = SalesOrder::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1;
        while (SalesOrder::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        $o = SalesOrder::withoutGlobalScopes()->create([
            'no_dok' => sprintf('%s%03d', $prefix, $urut),
            'partner_id' => $this->partner_id,
            'location_id' => $this->location_id,
            'lokasi_tujuan_id' => $this->partner_id ? null : $this->lokasi_tujuan_id,
            'status' => 'NEW',
            'dibuat_oleh' => auth()->id(),
        ]);
        SalesOrderLine::create(['sales_order_id' => $o->id, 'commodity_id' => $this->commodity_id, 'grade_id' => $this->grade_id, 'qty_kg' => (float) $this->qty]);

        $this->reset(['partner_id', 'commodity_id', 'grade_id', 'qty']);
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
            'daftar' => SalesOrder::with(['partner', 'lines.commodity', 'lines.grade', 'lines.reservations.lot'])->latest('id')->limit(30)->get(),
            'komoditas' => Commodity::all(),
            'grades' => Grade::with('commodity')->get(),
            'mitra' => Partner::whereIn('tipe', ['RETAIL', 'CUSTOMER'])->orderBy('nama')->get(),
            'gudangList' => Location::where('tipe', 'GUDANG')->where('aktif', true)->get(),
            'cabangList' => Location::where('tipe', 'CABANG')->where('aktif', true)->get(),
        ]);
    }
}

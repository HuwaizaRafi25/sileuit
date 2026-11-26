<?php

namespace App\Modules\OrderFulfillment\Livewire;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\InventoryLedger\Models\StockLedger;
use App\Modules\OrderFulfillment\Actions\ApproveReturnAction;
use App\Modules\OrderFulfillment\Actions\ReceiveReturnAction;
use App\Modules\OrderFulfillment\Actions\RequestReturnAction;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\PointOfSale\Models\PosSale;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReturnBoard extends Component
{
    public string $ref_tipe = 'SHIPMENT';

    public string $ref_id = '';

    public string $kg = '';

    public string $alasan = '';

    /** @var array<int, string> */
    public array $hasil = [];

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-retur'), 403);
    }

    public function updatedRefTipe(): void
    {
        $this->ref_id = '';
    }

    public function ajukan(RequestReturnAction $aksi): void
    {
        abort_unless(auth()->user()->can('buat-retur'), 403);
        $this->kg = str_replace(',', '.', trim($this->kg));
        $this->validate([
            'ref_id' => 'required|integer',
            'kg' => 'required|numeric|min:0.001',
            'alasan' => 'required|string|min:5',
        ], [
            'ref_id.required' => 'Retur wajib merujuk dokumen asal (BR-10).',
        ]);
        try {
            $locId = null;
            if ($this->ref_tipe === 'SHIPMENT') {
                $locId = Shipment::withoutGlobalScopes()->with('order')->find((int) $this->ref_id)?->order?->location_id;
            } elseif ($this->ref_tipe === 'SALE') {
                $sale = PosSale::withoutGlobalScopes()->with('lines')->find((int) $this->ref_id);
                $lotId = $sale?->lines->first()?->lot_id;
                if ($lotId) {
                    $locId = StockLedger::where('lot_id', $lotId)
                        ->whereHas('location', fn ($q) => $q->where('tipe', 'GUDANG'))
                        ->value('location_id');
                }
            }
            if (! $locId) {
                $active = auth()->user()->activeLocation;
                $locId = ($active && $active->tipe === 'GUDANG') ? $active->id : Location::where('tipe', 'GUDANG')->where('aktif', true)->value('id');
            }

            $aksi->execute($this->ref_tipe, (int) $this->ref_id, (float) $this->kg, auth()->user(), $this->alasan, (int) $locId);
            $this->pesan = 'Retur diajukan (REQUESTED).';
            $this->reset('ref_id', 'kg', 'alasan');
        } catch (ValidationException $e) {
            $this->addError('retur', collect($e->errors())->flatten()->first());
        }
    }

    public function proses(int $id, bool $setuju, ApproveReturnAction $aksi): void
    {
        try {
            $aksi->execute(ReturnDoc::withoutGlobalScopes()->findOrFail($id), auth()->user(), $setuju);
            $this->pesan = $setuju ? 'Retur disetujui.' : 'Retur ditolak.';
        } catch (ValidationException $e) {
            $this->addError('retur', collect($e->errors())->flatten()->first());
        }
    }

    public function terimaBarang(int $id, ReceiveReturnAction $aksi): void
    {
        abort_unless(auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'ADMIN'), 403);
        $disposisi = filled($this->hasil[$id] ?? null) ? $this->hasil[$id] : 'STOK_KEMBALI';
        try {
            $aksi->execute(ReturnDoc::withoutGlobalScopes()->findOrFail($id), auth()->user(), $disposisi);
            unset($this->hasil[$id]);
            $this->pesan = 'Retur diterima ('.$disposisi.').';
        } catch (ValidationException $e) {
            $this->addError('retur', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.return-board', [
            'daftar' => ReturnDoc::latest()->get(),
            'pengiriman' => Shipment::withoutGlobalScopes()->latest('id')->limit(30)->get(),
            'nota' => PosSale::withoutGlobalScopes()->latest('id')->limit(30)->get(),
        ]);
    }
}

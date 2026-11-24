<?php

namespace App\Modules\OrderFulfillment\Livewire;

use App\Modules\OrderFulfillment\Actions\ApproveReturnAction;
use App\Modules\OrderFulfillment\Actions\ReceiveReturnAction;
use App\Modules\OrderFulfillment\Actions\RequestReturnAction;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\Shipment;
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

    public string $hasil = 'STOK_KEMBALI';

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('buat-retur'), 403);
    }

    public function ajukan(RequestReturnAction $aksi): void
    {
        $this->validate(['ref_id' => 'required|integer', 'kg' => 'required|numeric|min:0.001', 'alasan' => 'required|string|min:5']);
        try {
            $aksi->execute($this->ref_tipe, (int) $this->ref_id, (float) $this->kg, auth()->user(), $this->alasan, auth()->user()->active_location_id ?? 1);
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
        try {
            $aksi->execute(ReturnDoc::withoutGlobalScopes()->findOrFail($id), auth()->user(), $this->hasil);
            $this->pesan = 'Retur diterima ('.$this->hasil.').';
        } catch (ValidationException $e) {
            $this->addError('retur', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.return-board', [
            'daftar' => ReturnDoc::latest()->get(),
            'pengiriman' => Shipment::withoutGlobalScopes()->get(),
        ]);
    }
}

<?php

namespace App\Modules\InventoryLedger\Livewire;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\InventoryLedger\Actions\ReceiveTransferAction;
use App\Modules\InventoryLedger\Actions\ShipTransferAction;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\InventoryLedger\Models\TransferLine;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TransferBoard extends Component
{
    public ?int $ke_lokasi = null;

    public ?int $lot_id = null;

    public string $kg = '';

    public array $terima = [];

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('buat-transfer'), 403);
    }

    public function stokAsal()
    {
        return StockBalance::with('lot.grade', 'lot.commodity')
            ->where('location_id', auth()->user()->active_location_id)
            ->where('on_hand_kg', '>', 0)->get();
    }

    public function buat(): void
    {
        $this->validate(['ke_lokasi' => 'required|exists:locations,id', 'lot_id' => 'required|exists:lots,id', 'kg' => 'required|numeric|min:0.001']);
        $asal = auth()->user()->active_location_id;
        if ((int) $this->ke_lokasi === (int) $asal) {
            $this->addError('ke_lokasi', 'Lokasi tujuan tidak boleh sama dengan asal (IR-17).');

            return;
        }

        $t = StockTransfer::create([
            'no_dok' => sprintf('TRF-%s-%s-%03d', auth()->user()->activeLocation->kode, now()->format('ymd'), StockTransfer::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1),
            'dari_lokasi' => $asal, 'ke_lokasi' => $this->ke_lokasi, 'status' => 'DRAFT',
        ]);
        TransferLine::create(['stock_transfer_id' => $t->id, 'lot_id' => $this->lot_id, 'kg_kirim' => (float) $this->kg]);

        $this->reset(['ke_lokasi', 'lot_id', 'kg']);
        $this->pesan = 'Transfer dibuat (DRAFT). Tekan Kirim untuk memindahkan stok.';
    }

    public function kirim(int $id, ShipTransferAction $aksi): void
    {
        $t = StockTransfer::withoutGlobalScopes()->findOrFail($id);
        abort_unless(auth()->user()->locationScopes->contains('id', $t->dari_lokasi) || auth()->user()->isGlobal(), 403);
        try {
            $aksi->execute($t, auth()->user());
            $this->pesan = 'Terkirim. Stok asal berkurang, barang dalam perjalanan.';
        } catch (ValidationException $e) {
            $this->addError('kirim', collect($e->errors())->flatten()->first());
        }
    }

    public function terimaBarang(int $id, ReceiveTransferAction $aksi): void
    {
        $t = StockTransfer::withoutGlobalScopes()->findOrFail($id);
        abort_unless(auth()->user()->can('terima-transfer') && auth()->user()->locationScopes->contains('id', $t->ke_lokasi), 403);
        try {
            $aksi->execute($t, auth()->user(), $this->terima);
            $this->terima = [];
            $this->pesan = 'Penerimaan tercatat.';
        } catch (ValidationException $e) {
            $this->addError('terima', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.transfer-board', [
            'daftar' => StockTransfer::with(['asal', 'tujuan', 'lines.lot'])->latest('id')->limit(30)->get(),
            'stok' => $this->stokAsal(),
            'lokasi' => Location::where('aktif', true)->whereKeyNot(auth()->user()->active_location_id)->get(),
        ]);
    }
}

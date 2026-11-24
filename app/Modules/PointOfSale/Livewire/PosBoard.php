<?php

namespace App\Modules\PointOfSale\Livewire;

use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\PointOfSale\Actions\ProcessPosCheckoutAction;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PosBoard extends Component
{
    public array $lines = [];

    public ?int $commodity_id = null;

    public ?int $grade_id = null;

    public string $qty = '';

    public string $harga = '';

    public string $metode = 'TUNAI';

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('pos-jual'), 403);
        abort_if(auth()->user()->active_location_id === null, 403, 'Akun ini belum punya lokasi tugas.');
    }

    public function tambah(): void
    {
        $this->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'grade_id' => 'required|exists:grades,id',
            'qty' => 'required|numeric|min:0.001',
            'harga' => 'required|integer|min:1',
        ]);
        $this->lines[] = [
            'commodity_id' => (int) $this->commodity_id, 'grade_id' => (int) $this->grade_id,
            'qty_kg' => (float) $this->qty, 'harga_per_kg' => (int) $this->harga,
        ];
        $this->reset('commodity_id', 'grade_id', 'qty', 'harga');
    }

    public function hapus(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function checkout(ProcessPosCheckoutAction $aksi): void
    {
        if (empty($this->lines)) {
            $this->addError('checkout', 'Tambahkan minimal satu produk.');

            return;
        }
        try {
            $sale = $aksi->execute(auth()->user(), auth()->user()->active_location_id, $this->lines, $this->metode, (string) Str::uuid());
            $this->lines = [];
            $this->pesan = 'Nota '.$sale->no_nota.' berhasil. Total Rp'.number_format($sale->total, 0, ',', '.').($sale->peringatan_het ? ' (peringatan HET)' : '');
        } catch (ValidationException $e) {
            $this->addError('checkout', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.pos-board', [
            'komoditas' => Commodity::all(),
            'grades' => Grade::with('commodity')->get(),
            'stok' => StockBalance::with('lot.grade', 'lot.commodity')
                ->where('location_id', auth()->user()->active_location_id)->where('on_hand_kg', '>', 0)->get(),
        ]);
    }
}

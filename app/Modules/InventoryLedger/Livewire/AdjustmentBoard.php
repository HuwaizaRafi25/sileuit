<?php

namespace App\Modules\InventoryLedger\Livewire;

use App\Modules\InventoryLedger\Actions\ApproveAdjustmentAction;
use App\Modules\InventoryLedger\Models\StockAdjustment;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdjustmentBoard extends Component
{
    public string $tipe = 'PENYESUAIAN';

    public ?int $lot_id = null;

    public ?int $commodity_id = null;

    public ?int $grade_id = null;

    public string $qty = '';

    public string $alasan = '';

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-penyesuaian'), 403);
    }

    public function updatedTipe(): void
    {
        $this->reset(['lot_id', 'commodity_id', 'grade_id', 'qty']);
    }

    public function updatedCommodityId(): void
    {
        $this->grade_id = null;
    }

    public function buat(): void
    {
        abort_unless(auth()->user()->can('buat-penyesuaian'), 403);
        $this->qty = str_replace(',', '.', trim($this->qty));
        $this->validate([
            'tipe' => 'required|in:PENYESUAIAN,SALDO_AWAL',
            'qty' => 'required|numeric|not_in:0',
            'alasan' => 'required|string|min:5',
        ]);

        if ($this->tipe === 'PENYESUAIAN' && ! $this->lot_id) {
            $this->addError('lot_id', 'Pilih lot yang disesuaikan.');

            return;
        }
        if ($this->tipe === 'PENYESUAIAN' && (float) $this->qty < 0) {
            $balance = StockBalance::where('lot_id', $this->lot_id)
                ->where('location_id', auth()->user()->active_location_id)
                ->first();
            $tersedia = $balance ? $balance->tersediaKg() : 0.0;
            if (abs((float) $this->qty) > $tersedia) {
                $this->addError('qty', 'Pengurangan melebihi saldo tersedia lot ('.number_format($tersedia, 1, ',', '.').' kg).');

                return;
            }
        }
        if ($this->tipe === 'SALDO_AWAL' && (! $this->commodity_id || ! $this->grade_id)) {
            $this->addError('commodity_id', 'Saldo awal butuh komoditas dan grade.');

            return;
        }
        if ($this->tipe === 'SALDO_AWAL' && (float) $this->qty <= 0) {
            $this->addError('qty', 'Saldo awal harus positif.');

            return;
        }

        $prefix = sprintf('ADJ-%s-%s-', auth()->user()->activeLocation->kode, now()->format('ymd'));
        $urut = StockAdjustment::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1;
        while (StockAdjustment::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        StockAdjustment::withoutGlobalScopes()->create([
            'no_dok' => sprintf('%s%03d', $prefix, $urut),
            'tipe' => $this->tipe,
            'location_id' => auth()->user()->active_location_id,
            'lot_id' => $this->tipe === 'PENYESUAIAN' ? $this->lot_id : null,
            'commodity_id' => $this->commodity_id,
            'grade_id' => $this->grade_id,
            'qty_kg' => (float) $this->qty,
            'tanggal' => today(),
            'alasan' => $this->alasan,
            'status' => 'MENUNGGU',
            'dibuat_oleh' => auth()->id(),
        ]);

        $this->reset(['lot_id', 'commodity_id', 'grade_id', 'qty', 'alasan']);
        $this->pesan = 'Penyesuaian diajukan. Menunggu persetujuan.';
    }

    public function proses(int $id, bool $setuju, ApproveAdjustmentAction $aksi): void
    {
        $a = StockAdjustment::withoutGlobalScopes()->findOrFail($id);
        try {
            $aksi->execute($a, auth()->user(), $setuju);
            $this->pesan = $setuju ? 'Disetujui. Ledger tercatat.' : 'Ditolak.';
        } catch (ValidationException $e) {
            $this->addError('proses', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.adjustment-board', [
            'daftar' => StockAdjustment::with(['lot', 'commodity', 'grade'])->latest('id')->limit(30)->get(),
            'stok' => StockBalance::with('lot')->where('location_id', auth()->user()->active_location_id)->where('on_hand_kg', '>', 0)->get(),
            'komoditas' => Commodity::all(),
            'grades' => Grade::with('commodity')->get(),
        ]);
    }
}

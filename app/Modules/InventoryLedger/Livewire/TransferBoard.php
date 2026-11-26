<?php

namespace App\Modules\InventoryLedger\Livewire;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Actions\ReceiveTransferAction;
use App\Modules\InventoryLedger\Actions\ShipTransferAction;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockTransfer;
use App\Modules\InventoryLedger\Models\TransferLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TransferBoard extends Component
{
    public ?int $ke_lokasi = null;

    public ?int $lot_id = null;

    public ?int $kurir_id = null;

    public string $kg = '';

    /** @var array<int, array{lot_id: int|string|null, kg: string}> */
    public array $items = [
        ['lot_id' => '', 'kg' => ''],
    ];

    public array $terima = [];

    public array $catatan = [];

    public array $kurirDraft = [];

    public ?string $pesan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-transfer'), 403);
    }

    public function tambahBaris(): void
    {
        $this->items[] = ['lot_id' => '', 'kg' => ''];
    }

    public function hapusBaris(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function stokAsal()
    {
        if (! auth()->user()->can('buat-transfer') || ! auth()->user()->active_location_id) {
            return collect();
        }

        return StockBalance::with('lot.grade', 'lot.commodity')
            ->where('location_id', auth()->user()->active_location_id)
            ->where('on_hand_kg', '>', 0)
            ->get();
    }

    public function buat(): void
    {
        abort_unless(auth()->user()->can('buat-transfer'), 403);

        if ($this->lot_id && $this->kg !== '' && empty($this->items[0]['lot_id'])) {
            $this->items = [['lot_id' => $this->lot_id, 'kg' => $this->kg]];
        }

        foreach ($this->items as $idx => $item) {
            $this->items[$idx]['kg'] = str_replace(',', '.', trim((string) ($item['kg'] ?? '')));
        }

        $this->validate([
            'ke_lokasi' => 'required|exists:locations,id',
            'kurir_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.kg' => 'required|numeric|min:0.001',
        ], [
            'items.*.lot_id.required' => 'Pilih lot sumber.',
            'items.*.kg.required' => 'Isi jumlah transfer.',
            'items.*.kg.min' => 'Jumlah transfer minimal 0,001 kg.',
        ]);

        if ($this->kurir_id) {
            $kurir = User::find($this->kurir_id);
            if (! $kurir || ! $kurir->hasRole('KURIR') || $kurir->status !== 'AKTIF') {
                $this->addError('kurir_id', 'Pengantar harus merupakan akun Kurir yang aktif.');

                return;
            }
        }

        $asal = auth()->user()->active_location_id;
        if ((int) $this->ke_lokasi === (int) $asal) {
            $this->addError('ke_lokasi', 'Lokasi tujuan tidak boleh sama dengan asal (IR-17).');

            return;
        }

        $seenLots = [];
        foreach ($this->items as $idx => $item) {
            $lotId = (int) $item['lot_id'];
            $qty = round((float) $item['kg'], 3);

            if (in_array($lotId, $seenLots, true)) {
                $this->addError("items.{$idx}.lot_id", 'Lot ini sudah ada di baris lain. Gabungkan jumlahnya pada satu baris.');

                return;
            }
            $seenLots[] = $lotId;

            $balance = StockBalance::where('lot_id', $lotId)->where('location_id', $asal)->first();
            $tersedia = $balance ? $balance->tersediaKg() : 0.0;
            if ($qty > $tersedia) {
                $kodeLot = Lot::whereKey($lotId)->value('kode_lot') ?? ('#'.$lotId);
                $msg = 'Stok tersedia lot '.$kodeLot.' hanya '.number_format($tersedia, 1, ',', '.').' kg (kurang dari '.number_format($qty, 1, ',', '.').' kg). Kurangi jumlah atau klik "+ Tambah Lot Lain".';
                $this->addError("items.{$idx}.kg", $msg);
                $this->addError('buat', $msg);

                return;
            }
        }

        $prefix = sprintf('TRF-%s-%s-', auth()->user()->activeLocation->kode, now()->format('ymd'));
        $urut = StockTransfer::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1;
        while (StockTransfer::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        DB::transaction(function () use ($prefix, $urut, $asal): void {
            $t = StockTransfer::create([
                'no_dok' => sprintf('%s%03d', $prefix, $urut),
                'dari_lokasi' => $asal,
                'ke_lokasi' => $this->ke_lokasi,
                'kurir_id' => $this->kurir_id,
                'status' => 'DRAFT',
            ]);

            foreach ($this->items as $item) {
                TransferLine::create([
                    'stock_transfer_id' => $t->id,
                    'lot_id' => (int) $item['lot_id'],
                    'kg_kirim' => round((float) $item['kg'], 3),
                ]);
            }
        });

        $this->reset(['ke_lokasi', 'lot_id', 'kurir_id', 'kg']);
        $this->items = [['lot_id' => '', 'kg' => '']];
        $this->pesan = 'Transfer dibuat (DRAFT). Tekan Kirim untuk memindahkan stok.';
    }

    public function kirim(int $id, ShipTransferAction $aksi): void
    {
        abort_unless(auth()->user()->can('buat-transfer'), 403);
        $t = StockTransfer::withoutGlobalScopes()->findOrFail($id);
        abort_unless(auth()->user()->locationScopes->contains('id', $t->dari_lokasi) || auth()->user()->isGlobal(), 403);

        $chosenCourierId = $this->kurirDraft[$id] ?? $t->kurir_id;
        if ($chosenCourierId && (int) $chosenCourierId !== (int) $t->kurir_id) {
            $kurir = User::find((int) $chosenCourierId);
            if (! $kurir || ! $kurir->hasRole('KURIR') || $kurir->status !== 'AKTIF') {
                $this->addError('kirim', 'Kurir yang dipilih tidak valid atau tidak aktif.');

                return;
            }
            $t->update(['kurir_id' => $kurir->id]);
        }

        try {
            $aksi->execute($t, auth()->user());
            unset($this->kurirDraft[$id]);
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
            $aksi->execute($t, auth()->user(), $this->terima, $this->catatan[$id] ?? null);
            $this->terima = [];
            unset($this->catatan[$id]);
            $this->pesan = 'Penerimaan tercatat.';
        } catch (ValidationException $e) {
            $this->addError('terima', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        $kurirList = User::query()
            ->where('status', 'AKTIF')
            ->whereHas('roles', fn ($q) => $q->where('kode', 'KURIR'))
            ->orderBy('name')
            ->get();

        return view('livewire.transfer-board', [
            'daftar' => StockTransfer::with(['asal', 'tujuan', 'kurir', 'lines.lot.commodity', 'lines.lot.grade'])->latest('id')->limit(30)->get(),
            'stok' => $this->stokAsal(),
            'lokasi' => Location::where('aktif', true)
                ->where('tipe', 'GUDANG')
                ->whereKeyNot(auth()->user()->active_location_id)
                ->orderBy('kode')
                ->get(),
            'kurirList' => $kurirList,
        ]);
    }
}

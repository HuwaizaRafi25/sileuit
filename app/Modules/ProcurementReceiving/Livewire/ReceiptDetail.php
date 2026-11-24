<?php

namespace App\Modules\ProcurementReceiving\Livewire;

use App\Modules\ProcurementReceiving\Actions\ApproveDiscrepancyAction;
use App\Modules\ProcurementReceiving\Actions\PayFarmerAction;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReceiptDetail extends Component
{
    public Receipt $receipt;

    public string $metode = 'TUNAI';

    public string $referensi = '';

    public string $alasan_setuju = '';

    public string $idempotency_key = '';

    public ?string $pesan = null;

    public function mount(int $receiptId): void
    {
        $this->receipt = Receipt::withoutGlobalScopes()->findOrFail($receiptId);
        Gate::authorize('view', $this->receipt);
        $this->idempotency_key = (string) Str::uuid();
    }

    public function setujui(ApproveDiscrepancyAction $aksi): void
    {
        Gate::authorize('approve', $this->receipt);
        try {
            $this->receipt = $aksi->execute($this->receipt, auth()->user(), true, $this->alasan_setuju);
            $this->pesan = 'Selisih disetujui. Penerimaan siap dikonfirmasi petugas.';
        } catch (ValidationException $e) {
            $this->addError('alasan_setuju', collect($e->errors())->flatten()->first());
        }
    }

    public function tolak(ApproveDiscrepancyAction $aksi): void
    {
        Gate::authorize('approve', $this->receipt);
        try {
            $this->receipt = $aksi->execute($this->receipt, auth()->user(), false, $this->alasan_setuju);
            $this->pesan = 'Penerimaan ditolak.';
        } catch (ValidationException $e) {
            $this->addError('alasan_setuju', collect($e->errors())->flatten()->first());
        }
    }

    public function bayar(PayFarmerAction $aksi): void
    {
        Gate::authorize('pay', $this->receipt);
        try {
            $aksi->execute($this->receipt, auth()->user(), $this->metode, $this->referensi, $this->idempotency_key);
            $this->receipt->refresh();
            $this->pesan = 'Pembayaran tercatat. Slip tersedia di portal petani.';
        } catch (ValidationException $e) {
            $this->addError('referensi', collect($e->errors())->flatten()->first());
        }
    }

    public function fotoUrl(): ?string
    {
        $insp = $this->receipt->inspectionTerakhir();
        if (! $insp) {
            return null;
        }
        try {
            return Storage::disk('s3')->temporaryUrl($insp->foto->path, now()->addMinutes(15));
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.receipt-detail', [
            'insp' => $this->receipt->inspectionTerakhir(),
            'foto' => $this->fotoUrl(),
            'pembayaran' => $this->receipt->allocations()->with('payment')->first()?->payment,
        ]);
    }
}

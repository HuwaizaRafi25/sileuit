<?php

namespace App\Modules\ProcurementReceiving\Livewire;

use App\Modules\ProcurementReceiving\Actions\ApproveDiscrepancyAction;
use App\Modules\ProcurementReceiving\Actions\PayFarmerAction;
use App\Modules\ProcurementReceiving\Models\Attachment;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ReceiptDetail extends Component
{
    use WithFileUploads;

    public Receipt $receipt;

    public string $metode = 'TUNAI';

    public string $referensi = '';

    public $bukti_foto = null;

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
        $this->validate([
            'referensi' => 'required|string|max:80',
            'bukti_foto' => $this->metode === 'TRANSFER' ? 'required|image|max:8192' : 'nullable|image|max:8192',
        ], [
            'referensi.required' => $this->metode === 'TRANSFER' ? 'Nomor referensi transfer wajib diisi.' : 'Nomor tanda terima tunai wajib diisi.',
            'bukti_foto.required' => 'Foto struk / bukti transfer wajib diunggah untuk metode Transfer Bank.',
        ]);

        try {
            $buktiId = null;
            if ($this->bukti_foto) {
                $binary = (string) Image::decodeBinary($this->bukti_foto->get())->scaleDown(width: 1600)->encodeUsingMediaType('image/webp', quality: 80);
                $path = 'payments/'.$this->receipt->no_dok.'_'.now()->timestamp.'.webp';
                Storage::disk('s3')->put($path, $binary);
                $att = Attachment::create([
                    'path' => $path,
                    'disk' => 's3',
                    'mime' => 'image/webp',
                    'ukuran_byte' => strlen($binary),
                    'diunggah_oleh' => auth()->id(),
                ]);
                $buktiId = $att->id;
            }

            $aksi->execute($this->receipt, auth()->user(), $this->metode, $this->referensi, $this->idempotency_key, $buktiId);
            $this->receipt->refresh();
            $this->reset('bukti_foto');
            $this->pesan = 'Pembayaran tercatat. Slip tersedia di portal petani.';
        } catch (ValidationException $e) {
            $this->addError('referensi', collect($e->errors())->flatten()->first());
        }
    }

    public function hapusDraft(): mixed
    {
        abort_unless($this->receipt->status === 'DRAFT' && auth()->user()->can('create', Receipt::class), 403);
        $this->receipt->delete();

        return $this->redirect('/app/penerimaan');
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
        $pembayaran = $this->receipt->allocations()->with('payment.bukti')->first()?->payment;
        $buktiUrl = null;
        if ($pembayaran?->bukti) {
            try {
                $buktiUrl = Storage::disk('s3')->temporaryUrl($pembayaran->bukti->path, now()->addMinutes(15));
            } catch (\Throwable) {
                $buktiUrl = null;
            }
        }

        return view('livewire.receipt-detail', [
            'insp' => $this->receipt->inspectionTerakhir(),
            'foto' => $this->fotoUrl(),
            'pembayaran' => $pembayaran,
            'buktiUrl' => $buktiUrl,
        ]);
    }
}

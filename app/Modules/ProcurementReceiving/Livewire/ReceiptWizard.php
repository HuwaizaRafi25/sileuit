<?php

namespace App\Modules\ProcurementReceiving\Livewire;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\ProcurementReceiving\Actions\CalculateNetWeightAction;
use App\Modules\ProcurementReceiving\Actions\ConfirmReceiptAction;
use App\Modules\ProcurementReceiving\Actions\DetermineGradeAction;
use App\Modules\ProcurementReceiving\Models\Attachment;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Inspection;
use App\Modules\ProcurementReceiving\Models\Receipt;
use App\Modules\ProcurementReceiving\Services\PriceResolver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ReceiptWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public ?int $receipt_id = null;

    public string $idempotency_key = '';

    public ?int $contract_id = null;

    public ?int $harvest_plan_id = null;

    public string $bruto = '';

    public string $tara = '';

    public string $kadar_air = '';

    public string $kadar_hampa = '';

    public string $butir_patah = '';

    public string $varietas = '';

    public $foto = null;

    public string $keputusan = 'TERIMA';

    public string $alasan = '';

    public string $selisih_alasan = '';

    public ?int $grade_id = null;

    public ?string $kode_lot = null;

    public ?int $nilai_bayar = null;

    public ?int $harga_per_kg = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create', Receipt::class), 403);
        abort_if(auth()->user()->active_location_id === null, 403, 'Akun ini belum punya lokasi tugas.');
        $this->idempotency_key = (string) Str::uuid();
    }

    public function kontrak()
    {
        return Contract::with('partner')->where('status', 'AKTIF')->get();
    }

    public function rencana()
    {
        return $this->contract_id
            ? HarvestPlan::where('contract_id', $this->contract_id)->where('status', 'RENCANA')->get()
            : collect();
    }

    public function netto(): float
    {
        return round((float) $this->bruto - (float) $this->tara, 3);
    }

    public function rencanaKg(): ?float
    {
        $p = $this->harvest_plan_id ? HarvestPlan::find($this->harvest_plan_id) : null;

        return $p ? (float) $p->kg_perkiraan : null;
    }

    public function selisih(): ?float
    {
        return app(CalculateNetWeightAction::class)->selisihPersen($this->netto(), $this->rencanaKg());
    }

    public function perluSetuju(): bool
    {
        $s = $this->selisih();

        return $s !== null && abs($s) > 1.0;
    }

    public function next(): void
    {
        match ($this->step) {
            1 => $this->simpanLangkah1(),
            2 => $this->simpanLangkah2(),
            3 => $this->simpanLangkah3(),
            default => null,
        };
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    private function simpanLangkah1(): void
    {
        $this->validate(['contract_id' => 'required|exists:contracts,id', 'harvest_plan_id' => 'nullable|exists:harvest_plans,id']);

        $r = Receipt::withoutGlobalScopes()->updateOrCreate(
            ['idempotency_key' => $this->idempotency_key],
            [
                'no_dok' => $this->noDok(),
                'contract_id' => $this->contract_id,
                'location_id' => auth()->user()->active_location_id,
                'harvest_plan_id' => $this->harvest_plan_id,
                'status' => 'DRAFT',
                'diterima_oleh' => auth()->id(),
                'waktu_timbang' => now(),
            ]
        );
        $this->receipt_id = $r->id;
        $this->step = 2;
    }

    private function simpanLangkah2(): void
    {
        $this->validate(['bruto' => 'required|numeric|min:0.001', 'tara' => 'required|numeric|min:0']);
        $netto = app(CalculateNetWeightAction::class)->execute((float) $this->bruto, (float) $this->tara);
        $selisih = app(CalculateNetWeightAction::class)->selisihPersen($netto, $this->rencanaKg());

        if ($selisih !== null && abs($selisih) > 1.0 && trim($this->selisih_alasan) === '') {
            throw ValidationException::withMessages(['selisih_alasan' => 'Selisih di luar ±1%. Alasan wajib diisi untuk persetujuan Kepala Gudang.']);
        }

        Receipt::withoutGlobalScopes()->whereKey($this->receipt_id)->update([
            'bruto_kg' => (float) $this->bruto,
            'tara_kg' => (float) $this->tara,
            'netto_kg' => $netto,
            'selisih_persen' => $selisih,
            'selisih_alasan' => $this->selisih_alasan ?: null,
            'waktu_timbang' => now(),
        ]);
        $this->step = 3;
    }

    private function simpanLangkah3(): void
    {
        $this->validate([
            'kadar_air' => 'required|numeric|min:0|max:100',
            'kadar_hampa' => 'required|numeric|min:0|max:100',
            'butir_patah' => 'nullable|numeric|min:0|max:100',
            'varietas' => 'required|string|max:60',
            'foto' => 'required|image|max:8192',
        ]);

        $r = Receipt::withoutGlobalScopes()->findOrFail($this->receipt_id);
        $commodity = Commodity::where('nama', 'GABAH')->firstOrFail();
        $grade = app(DetermineGradeAction::class)->execute($commodity, (float) $this->kadar_air);

        if (! $grade) {
            $this->validate(['keputusan' => 'required|in:KARANTINA,TOLAK', 'alasan' => 'required|string|min:5']);
        } else {
            $this->keputusan = 'TERIMA';
            $this->alasan = '';
        }

        $binary = (string) Image::read($this->foto->getRealPath())->scaleDown(width: 1600)->toWebp(quality: 80);
        $path = 'inspections/'.$r->no_dok.'_'.now()->timestamp.'.webp';
        Storage::disk('s3')->put($path, $binary);

        $att = Attachment::create(['path' => $path, 'disk' => 's3', 'mime' => 'image/webp', 'ukuran_byte' => strlen($binary), 'diunggah_oleh' => auth()->id()]);

        Inspection::create([
            'receipt_id' => $r->id,
            'kadar_air' => (float) $this->kadar_air,
            'kadar_hampa' => (float) $this->kadar_hampa,
            'butir_patah' => $this->butir_patah !== '' ? (float) $this->butir_patah : null,
            'varietas' => $this->varietas,
            'grade_id' => $grade?->id,
            'keputusan' => $this->keputusan,
            'alasan' => $this->alasan ?: null,
            'foto_attachment_id' => $att->id,
        ]);

        $this->grade_id = $grade?->id;
        $r->update(['status' => 'INSPECTED']);

        if ($grade) {
            try {
                $item = app(PriceResolver::class)->resolve($grade->commodity_id, $grade->id, $r->waktu_timbang);
                $this->harga_per_kg = $item->harga_per_kg;
                $this->nilai_bayar = (int) round($this->netto() * $item->harga_per_kg);
            } catch (\Throwable) {
                $this->harga_per_kg = null;
                $this->nilai_bayar = null;
            }
        }

        $this->step = 4;
    }

    public function confirm(ConfirmReceiptAction $aksi): void
    {
        $r = Receipt::withoutGlobalScopes()->findOrFail($this->receipt_id);
        abort_unless(auth()->user()->can('confirm', $r), 403);

        if ($r->butuhPersetujuanSelisih()) {
            $r->update(['status' => 'MENUNGGU_PERSETUJUAN']);
            AuditLogger::log('PENERIMAAN_DIAJUKAN', 'receipts', $r->id, null, ['selisih_persen' => $r->selisih_persen]);
            $this->step = 5;

            return;
        }

        try {
            $r = $aksi->execute($r, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('konfirmasi', collect($e->errors())->flatten()->first());

            return;
        }

        $this->kode_lot = $r->lots()->first()?->kode_lot;
        $this->nilai_bayar = $r->nilai_bayar;
        $this->step = $r->status === 'REJECTED' ? 6 : 5;
    }

    private function noDok(): string
    {
        $lok = auth()->user()->activeLocation;
        $tgl = now()->format('ymd');
        $urut = Receipt::withoutGlobalScopes()->where('location_id', $lok->id)->whereDate('created_at', today())->count() + 1;

        return sprintf('GRN-%s-%s-%03d', $lok->kode, $tgl, $urut);
    }

    public function render()
    {
        return view('livewire.receipt-wizard', [
            'kontrak' => $this->kontrak(),
            'rencanaList' => $this->rencana(),
            'grade' => $this->grade_id ? Grade::find($this->grade_id) : null,
        ]);
    }
}

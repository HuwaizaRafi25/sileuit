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

        $draftId = request()->integer('draft');
        if ($draftId > 0) {
            $draft = Receipt::withoutGlobalScopes()->where('status', 'DRAFT')->find($draftId);
            if ($draft && auth()->user()->can('view', $draft)) {
                $this->receipt_id = $draft->id;
                $this->idempotency_key = $draft->idempotency_key;
                $this->contract_id = $draft->contract_id;
                $this->harvest_plan_id = $draft->harvest_plan_id;
                $this->bruto = $draft->bruto_kg !== null ? (string) $draft->bruto_kg : '';
                $this->tara = $draft->tara_kg !== null ? (string) $draft->tara_kg : '';
                $this->selisih_alasan = (string) ($draft->selisih_alasan ?? '');

                $insp = $draft->inspectionTerakhir();
                if ($insp) {
                    $this->kadar_air = (string) $insp->kadar_air;
                    $this->kadar_hampa = (string) $insp->kadar_hampa;
                    $this->butir_patah = $insp->butir_patah !== null ? (string) $insp->butir_patah : '';
                    $this->varietas = (string) $insp->varietas;
                    $this->keputusan = (string) $insp->keputusan;
                    $this->alasan = (string) ($insp->alasan ?? '');
                }

                $this->step = $draft->bruto_kg !== null ? 3 : 2;
            }
        }
    }

    public function updatedBruto($value): void
    {
        $this->bruto = str_replace(',', '.', (string) $value);
    }

    public function updatedTara($value): void
    {
        $this->tara = str_replace(',', '.', (string) $value);
    }

    public function updatedKadarAir($value): void
    {
        $this->kadar_air = str_replace(',', '.', (string) $value);
    }

    public function updatedKadarHampa($value): void
    {
        $this->kadar_hampa = str_replace(',', '.', (string) $value);
    }

    public function updatedButirPatah($value): void
    {
        $this->butir_patah = str_replace(',', '.', (string) $value);
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
        $this->bruto = str_replace(',', '.', trim($this->bruto));
        $this->tara = str_replace(',', '.', trim($this->tara));
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
        $this->kadar_air = str_replace(',', '.', trim($this->kadar_air));
        $this->kadar_hampa = str_replace(',', '.', trim($this->kadar_hampa));
        if ($this->butir_patah !== '') {
            $this->butir_patah = str_replace(',', '.', trim($this->butir_patah));
        }
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

        $binary = (string) Image::decodeBinary($this->foto->get())->scaleDown(width: 1600)->encodeUsingMediaType('image/webp', quality: 80);
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
        $prefix = sprintf('GRN-%s-%s-', $lok->kode, $tgl);
        $urut = Receipt::withoutGlobalScopes()->where('location_id', $lok->id)->whereDate('created_at', today())->count() + 1;

        while (Receipt::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        return sprintf('%s%03d', $prefix, $urut);
    }

    public function render()
    {
        $kontrakList = $this->kontrak()->load('partner');
        $plansByContract = HarvestPlan::whereIn('contract_id', $kontrakList->pluck('id'))
            ->where('status', 'RENCANA')
            ->orderBy('tgl_perkiraan')
            ->get()
            ->groupBy('contract_id');

        $gabahCommodity = Commodity::where('nama', 'GABAH')->first();
        $gabahGrades = $gabahCommodity
            ? Grade::where('commodity_id', $gabahCommodity->id)->get()->map(fn (Grade $g): array => [
                'id' => $g->id,
                'kode' => $g->kode,
                'kadar_air_max' => (float) ($g->ambang_mutu['kadar_air_max'] ?? 999),
            ])->values()->all()
            : [];

        $offlineMaster = [
            'location_id' => auth()->user()->active_location_id,
            'location_nama' => auth()->user()->activeLocation?->nama ?? '',
            'grades' => $gabahGrades,
            'contracts' => $kontrakList->map(function (Contract $k) use ($plansByContract): array {
                return [
                    'id' => $k->id,
                    'label' => "{$k->partner->nama} · {$k->musim}",
                    'plans' => ($plansByContract->get($k->id) ?? collect())->map(fn (HarvestPlan $p): array => [
                        'id' => $p->id,
                        'tgl_label' => $p->tgl_perkiraan->format('d M Y'),
                        'kg_perkiraan' => (float) $p->kg_perkiraan,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];

        return view('livewire.receipt-wizard', [
            'kontrak' => $kontrakList,
            'rencanaList' => $this->rencana(),
            'grade' => $this->grade_id ? Grade::find($this->grade_id) : null,
            'offlineMaster' => $offlineMaster,
        ]);
    }
}

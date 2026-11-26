<?php

namespace App\Modules\ProcurementReceiving\Http\Controllers;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\ProcurementReceiving\Actions\CalculateNetWeightAction;
use App\Modules\ProcurementReceiving\Actions\ConfirmReceiptAction;
use App\Modules\ProcurementReceiving\Actions\DetermineGradeAction;
use App\Modules\ProcurementReceiving\Models\Attachment;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Inspection;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfflineSyncController
{
    /**
     * FR-18 / TC-14: otoritas server penuh. Data offline divalidasi ulang;
     * idempotency_key mencegah penerimaan ganda saat kirim ulang.
     */
    public function sync(Request $request, ConfirmReceiptAction $confirm, CalculateNetWeightAction $netCalc): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('create', Receipt::class), 403);

        $data = $request->validate([
            'idempotency_key' => 'required|string|max:64',
            'contract_id' => 'required|exists:contracts,id',
            'location_id' => 'required|exists:locations,id',
            'harvest_plan_id' => 'nullable|exists:harvest_plans,id',
            'bruto_kg' => 'required|numeric|min:0.001',
            'tara_kg' => 'required|numeric|min:0',
            'kadar_air' => 'required|numeric|min:0|max:100',
            'kadar_hampa' => 'required|numeric|min:0|max:100',
            'butir_patah' => 'nullable|numeric|min:0|max:100',
            'varietas' => 'required|string|max:60',
            'selisih_alasan' => 'nullable|string|max:500',
            'keputusan' => 'nullable|in:TERIMA,KARANTINA,TOLAK',
            'alasan' => 'nullable|string|max:500',
            'foto_base64' => 'required|string',
        ]);

        abort_unless($user->isGlobal() || $user->locationScopes->contains('id', (int) $data['location_id']), 403);

        $sudah = Receipt::withoutGlobalScopes()->where('idempotency_key', $data['idempotency_key'])->first();
        if ($sudah) {
            return response()->json([
                'message' => 'Penerimaan sudah tercatat (duplikat dikenali).',
                'receipt_id' => $sudah->id,
                'no_dok' => $sudah->no_dok,
                'status' => $sudah->status,
                'duplikat' => true,
            ], 200);
        }

        $binary = base64_decode($data['foto_base64'], true);
        if ($binary === false) {
            return response()->json(['message' => 'foto_base64 tidak valid.'], 422);
        }

        $path = 'inspections/offline_'.Str::uuid().'.webp';
        Storage::disk('s3')->put($path, $binary);
        $att = Attachment::create([
            'path' => $path,
            'disk' => 's3',
            'mime' => 'image/webp',
            'ukuran_byte' => strlen($binary),
            'diunggah_oleh' => $user->id,
        ]);

        $kontrak = Contract::findOrFail($data['contract_id']);
        $netto = $netCalc->execute((float) $data['bruto_kg'], (float) $data['tara_kg']);
        $rencanaKg = isset($data['harvest_plan_id'])
            ? (float) (HarvestPlan::whereKey($data['harvest_plan_id'])->value('kg_perkiraan') ?? 0)
            : null;
        $selisihPersen = $netCalc->selisihPersen($netto, $rencanaKg ?: null);

        if ($selisihPersen !== null && abs($selisihPersen) > 1.0 && ! filled($data['selisih_alasan'] ?? null)) {
            return response()->json(['message' => 'Selisih di luar toleransi ±1% wajib menyertakan alasan selisih.'], 422);
        }

        $komoditas = Commodity::where('nama', 'GABAH')->firstOrFail();
        $grade = app(DetermineGradeAction::class)->execute($komoditas, (float) $data['kadar_air']);
        $keputusan = $grade ? 'TERIMA' : ($data['keputusan'] ?? null);

        if (! $grade && ! in_array($keputusan, ['KARANTINA', 'TOLAK'], true)) {
            return response()->json(['message' => 'Mutu di bawah standar minimum. Pilih keputusan KARANTINA atau TOLAK beserta alasan.'], 422);
        }

        if (! $grade && ! filled($data['alasan'] ?? null)) {
            return response()->json(['message' => 'Alasan penolakan/karantina wajib diisi.'], 422);
        }

        $receipt = Receipt::create([
            'no_dok' => $this->noDok((int) $data['location_id']),
            'contract_id' => $kontrak->id,
            'location_id' => (int) $data['location_id'],
            'harvest_plan_id' => $data['harvest_plan_id'] ?? null,
            'bruto_kg' => (float) $data['bruto_kg'],
            'tara_kg' => (float) $data['tara_kg'],
            'netto_kg' => $netto,
            'selisih_persen' => $selisihPersen,
            'selisih_alasan' => $data['selisih_alasan'] ?? null,
            'harga_per_kg' => null,
            'nilai_bayar' => null,
            'status' => 'INSPECTED',
            'diterima_oleh' => $user->id,
            'waktu_timbang' => now(),
            'idempotency_key' => $data['idempotency_key'],
        ]);

        Inspection::create([
            'receipt_id' => $receipt->id,
            'kadar_air' => (float) $data['kadar_air'],
            'kadar_hampa' => (float) $data['kadar_hampa'],
            'butir_patah' => isset($data['butir_patah']) && $data['butir_patah'] !== '' ? (float) $data['butir_patah'] : null,
            'varietas' => $data['varietas'],
            'grade_id' => $grade?->id,
            'keputusan' => $keputusan,
            'alasan' => $grade ? null : ($data['alasan'] ?? null),
            'foto_attachment_id' => $att->id,
        ]);

        if ($receipt->butuhPersetujuanSelisih()) {
            $receipt->update(['status' => 'MENUNGGU_PERSETUJUAN']);
            AuditLogger::log('PENERIMAAN_DIAJUKAN', 'receipts', $receipt->id, null, [
                'selisih_persen' => $receipt->selisih_persen,
                'offline_sync' => true,
            ]);

            return response()->json([
                'message' => 'Penerimaan offline tersinkron (menunggu persetujuan selisih Kepala Gudang).',
                'receipt_id' => $receipt->id,
                'no_dok' => $receipt->no_dok,
                'status' => $receipt->status,
                'duplikat' => false,
            ], 200);
        }

        try {
            $receipt = $confirm->execute($receipt, $user);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Penerimaan offline tersinkron.',
            'receipt_id' => $receipt->id,
            'no_dok' => $receipt->no_dok,
            'status' => $receipt->status,
            'duplikat' => false,
        ], 200);
    }

    private function noDok(int $locationId): string
    {
        $kode = Location::whereKey($locationId)->value('kode');
        $prefix = sprintf('GRN-%s-%s-', $kode, now()->format('ymd'));
        $urut = Receipt::withoutGlobalScopes()->where('location_id', $locationId)->whereDate('created_at', today())->count() + 1;
        while (Receipt::withoutGlobalScopes()->where('no_dok', sprintf('%s%03d', $prefix, $urut))->exists()) {
            $urut++;
        }

        return sprintf('%s%03d', $prefix, $urut);
    }
}

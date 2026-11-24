<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use App\Modules\OrderFulfillment\Models\Shipment;
use App\Modules\PointOfSale\Models\PosSale;
use Illuminate\Validation\ValidationException;

class RequestReturnAction
{
    /** BR-10 / IR-12: wajib merujuk dokumen asal; kg tidak boleh melebihi sisa yang boleh diretur. */
    public function execute(?string $refTipe, ?int $refId, float $kg, User $oleh, string $alasan, int $locationId): ReturnDoc
    {
        if (! $refTipe || ! $refId) {
            throw ValidationException::withMessages(['ref' => 'Retur wajib merujuk dokumen asal (BR-10).']);
        }
        if (! in_array($refTipe, ['SHIPMENT', 'SALE'], true)) {
            throw ValidationException::withMessages(['ref' => 'Referensi retur tidak valid.']);
        }
        if ($kg <= 0) {
            throw ValidationException::withMessages(['kg' => 'Jumlah retur harus lebih dari nol.']);
        }

        $asalKg = $this->kuantitasAsal($refTipe, $refId);
        $sudah = (float) ReturnDoc::withoutGlobalScopes()
            ->where('ref_tipe', $refTipe)->where('ref_id', $refId)->where('status', '<>', 'REJECTED')->sum('kg');
        $sisa = round($asalKg - $sudah, 3);

        if ($kg > $sisa) {
            throw ValidationException::withMessages(['kg' => 'Jumlah retur melebihi sisa yang boleh diretur ('.number_format($sisa, 3, ',', '.').' kg).']);
        }

        $r = ReturnDoc::create([
            'no_dok' => $this->noDok(), 'ref_tipe' => $refTipe, 'ref_id' => $refId, 'location_id' => $locationId,
            'diminta_oleh' => $oleh->id, 'kg' => $kg, 'status' => 'REQUESTED', 'alasan' => $alasan,
        ]);

        AuditLogger::log('RETUR_DIAJUKAN', 'return_docs', $r->id, null, ['kg' => $kg, 'ref' => $refTipe.'#'.$refId]);

        return $r;
    }

    private function kuantitasAsal(string $tipe, int $id): float
    {
        if ($tipe === 'SHIPMENT') {
            return (float) Shipment::withoutGlobalScopes()->findOrFail($id)->jumlah_dikirim_kg;
        }

        return (float) PosSale::withoutGlobalScopes()->findOrFail($id)->lines()->sum('qty_kg');
    }

    private function noDok(): string
    {
        return sprintf('RET-%s-%03d', now()->format('ymd'), ReturnDoc::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1);
    }
}

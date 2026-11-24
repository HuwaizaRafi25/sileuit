<?php

namespace App\Modules\ProcurementReceiving\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveDiscrepancyAction
{
    public function execute(Receipt $receipt, User $oleh, bool $setuju, string $alasan): Receipt
    {
        return DB::transaction(function () use ($receipt, $oleh, $setuju, $alasan) {
            $r = Receipt::withoutGlobalScopes()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($r->status !== 'MENUNGGU_PERSETUJUAN') {
                throw ValidationException::withMessages(['status' => 'Penerimaan ini tidak sedang menunggu persetujuan.']);
            }
            if (trim($alasan) === '') {
                throw ValidationException::withMessages(['alasan' => 'Alasan persetujuan wajib diisi.']);
            }

            $r->update([
                'status' => $setuju ? 'INSPECTED' : 'REJECTED',
                'selisih_disetujui_oleh' => $setuju ? $oleh->id : null,
                'selisih_alasan' => $alasan,
            ]);

            AuditLogger::log($setuju ? 'SELISIH_DISETUJUI' : 'SELISIH_DITOLAK', 'receipts', $r->id,
                ['status' => 'MENUNGGU_PERSETUJUAN'], ['status' => $r->status, 'alasan' => $alasan]);

            return $r->fresh();
        });
    }
}

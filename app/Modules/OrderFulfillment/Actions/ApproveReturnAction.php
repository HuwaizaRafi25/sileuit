<?php

namespace App\Modules\OrderFulfillment\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\OrderFulfillment\Models\ReturnDoc;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveReturnAction
{
    public function execute(ReturnDoc $retur, User $oleh, bool $setuju): ReturnDoc
    {
        if (! $oleh->hasRole('KEPALA_GUDANG')) {
            abort(403, 'Hanya Kepala Gudang yang boleh menyetujui retur.');
        }

        return DB::transaction(function () use ($retur, $oleh, $setuju) {
            $r = ReturnDoc::withoutGlobalScopes()->whereKey($retur->id)->lockForUpdate()->firstOrFail();
            if ($r->status !== 'REQUESTED') {
                throw ValidationException::withMessages(['status' => 'Retur ini sudah diproses.']);
            }

            $r->update(['status' => $setuju ? 'APPROVED' : 'REJECTED', 'disetujui_oleh' => $oleh->id]);
            AuditLogger::log($setuju ? 'RETUR_DISETUJUI' : 'RETUR_DITOLAK', 'return_docs', $r->id,
                ['status' => 'REQUESTED'], ['status' => $r->status]);

            return $r->fresh();
        });
    }
}

<?php

namespace App\Modules\ProcurementReceiving\Policies;

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Receipt;

class ReceiptPolicy
{
    private function diLokasi(User $u, Receipt $r): bool
    {
        return $u->isGlobal() || $u->locationScopes->contains('id', $r->location_id);
    }

    public function view(User $u, Receipt $r): bool
    {
        if ($u->hasRole('PETANI', 'MITRA_B2B')) {
            return $u->partner_id !== null && $u->partner_id === $r->contract->partner_id;
        }

        return $this->diLokasi($u, $r) && ! $u->hasRole('KURIR', 'KASIR');
    }

    public function create(User $u): bool
    {
        return $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG');
    }

    public function update(User $u, Receipt $r): bool
    {
        return $u->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG')
            && $u->locationScopes->contains('id', $r->location_id)
            && in_array($r->status, ['DRAFT', 'DIAJUKAN', 'INSPECTED', 'MENUNGGU_PERSETUJUAN'], true);
    }

    public function confirm(User $u, Receipt $r): bool
    {
        return $this->update($u, $r);
    }

    public function approve(User $u, Receipt $r): bool
    {
        return $u->hasRole('KEPALA_GUDANG') && $u->locationScopes->contains('id', $r->location_id);
    }

    public function pay(User $u, Receipt $r): bool
    {
        return $u->hasRole('KEUANGAN');
    }
}

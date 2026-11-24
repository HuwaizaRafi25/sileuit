<?php

namespace App\Modules\BillingAccountsReceivable\Services;

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use Illuminate\Support\Collection;

class AgingCalculator
{
    /**
     * Mengelompokkan saldo piutang per mitra ke dalam bucket umur:
     * CURRENT, 1-7, 8-14, 15-30, >30 hari lewat jatuh tempo.
     *
     * @return array{buckets:array<string,int>, total:int, per_partner:Collection}
     */
    public function hitung(?int $locationId = null): array
    {
        $q = Invoice::withoutGlobalScopes()->with('partner');
        if ($locationId) {
            $q->where('location_id', $locationId);
        }
        $invoices = $q->get()->filter(fn (Invoice $i) => $i->saldo() > 0);

        $buckets = ['CURRENT' => 0, '1-7' => 0, '8-14' => 0, '15-30' => 0, '>30' => 0];
        $perPartner = [];

        foreach ($invoices as $inv) {
            $saldo = $inv->saldo();
            $buckets[$inv->agingBucket()] += $saldo;
            $key = $inv->partner_id;
            if (! isset($perPartner[$key])) {
                $perPartner[$key] = ['partner' => $inv->partner->nama, 'saldo' => 0, 'invoice_count' => 0];
            }
            $perPartner[$key]['saldo'] += $saldo;
            $perPartner[$key]['invoice_count']++;
        }

        return [
            'buckets' => $buckets,
            'total' => array_sum($buckets),
            'per_partner' => collect($perPartner)->sortByDesc('saldo')->values(),
        ];
    }
}

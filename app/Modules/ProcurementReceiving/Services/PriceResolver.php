<?php

namespace App\Modules\ProcurementReceiving\Services;

use App\Modules\ProcurementReceiving\Models\PriceListItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class PriceResolver
{
    /** BR-01: harga hanya dari daftar bertanggal yang DISETUJUI, berlaku pada tanggal timbang. */
    public function resolve(int $commodityId, int $gradeId, CarbonInterface $tanggal): PriceListItem
    {
        $item = PriceListItem::query()
            ->where('commodity_id', $commodityId)
            ->where('grade_id', $gradeId)
            ->whereNotNull('disetujui_oleh')
            ->whereDate('berlaku_dari', '<=', $tanggal)
            ->where(fn (Builder $q) => $q->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', $tanggal))
            ->orderByDesc('berlaku_dari')
            ->first();

        if (! $item) {
            throw new HargaTidakDitemukanException('Harga untuk grade ini belum ditetapkan. Hubungi Staf Pengadaan.');
        }

        return $item;
    }
}

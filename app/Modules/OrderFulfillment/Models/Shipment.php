<?php

namespace App\Modules\OrderFulfillment\Models;

use App\Modules\ProcurementReceiving\Models\Attachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = ['no_dok', 'sales_order_id', 'kurir_id', 'status', 'jumlah_dikirim_kg', 'jumlah_diterima_kg', 'kekurangan_kg', 'bukti_attachment_id', 'waktu_kirim', 'waktu_terima'];

    protected function casts(): array
    {
        return ['jumlah_dikirim_kg' => 'decimal:3', 'jumlah_diterima_kg' => 'decimal:3', 'kekurangan_kg' => 'decimal:3', 'waktu_kirim' => 'datetime', 'waktu_terima' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('akses', function (Builder $q) {
            $u = auth()->user();
            if (! $u || $u->isGlobal()) {
                return;
            }
            if ($u->hasRole('PETANI', 'MITRA_B2B')) {
                $q->whereHas('order', fn (Builder $o) => $o->where('partner_id', $u->partner_id));
            } else {
                $q->whereHas('order', fn (Builder $o) => $o->whereIn('location_id', $u->locationScopes->pluck('id')));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function bukti(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'bukti_attachment_id');
    }
}

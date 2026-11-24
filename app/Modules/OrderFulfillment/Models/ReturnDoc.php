<?php

namespace App\Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReturnDoc extends Model
{
    protected $fillable = ['no_dok', 'ref_tipe', 'ref_id', 'location_id', 'diminta_oleh', 'kg', 'status', 'hasil', 'alasan', 'disetujui_oleh'];

    protected function casts(): array
    {
        return ['kg' => 'decimal:3'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('akses', function (Builder $q) {
            $u = auth()->user();
            if (! $u || $u->isGlobal()) {
                return;
            }
            if ($u->hasRole('PETANI', 'MITRA_B2B')) {
                $q->where('diminta_oleh', $u->id);
            } else {
                $q->whereIn('location_id', $u->locationScopes->pluck('id'));
            }
        });
    }
}

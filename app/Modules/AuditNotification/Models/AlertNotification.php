<?php

namespace App\Modules\AuditNotification\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AlertNotification extends Model
{
    protected $fillable = ['tipe', 'judul', 'isi', 'entitas', 'entitas_id', 'untuk_peran', 'location_id', 'dibaca_pada'];

    protected function casts(): array
    {
        return ['dibaca_pada' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('akses', function (Builder $q) {
            $u = auth()->user();
            if (! $u) {
                return;
            }
            if ($u->hasRole('PETANI', 'MITRA_B2B')) {
                $q->whereRaw('1 = 0');

                return;
            }
            if ($u->isGlobal()) {
                return;
            }
            $q->where(function (Builder $w) use ($u) {
                $w->whereIn('location_id', $u->locationScopes->pluck('id'))->orWhereNull('location_id');
            });
        });
    }
}

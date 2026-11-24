<?php

namespace App\Modules\InventoryLedger\Models;

use App\Infrastructure\Traits\TanpaAksesMitra;
use App\Modules\IdentityAccess\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use TanpaAksesMitra;

    protected $fillable = ['no_dok', 'dari_lokasi', 'ke_lokasi', 'status', 'dikirim_oleh', 'diterima_oleh', 'waktu_kirim', 'waktu_terima'];

    protected function casts(): array
    {
        return ['waktu_kirim' => 'datetime', 'waktu_terima' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('lokasiTransfer', function (Builder $q) {
            $u = auth()->user();
            if ($u && ! $u->isGlobal()) {
                $ids = $u->locationScopes->pluck('id');
                $q->where(fn (Builder $w) => $w->whereIn('dari_lokasi', $ids)->orWhereIn('ke_lokasi', $ids));
            }
        });
    }

    public function asal(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'dari_lokasi');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'ke_lokasi');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransferLine::class);
    }
}

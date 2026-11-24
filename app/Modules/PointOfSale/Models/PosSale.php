<?php

namespace App\Modules\PointOfSale\Models;

use App\Infrastructure\Traits\BelongsToLocation;
use App\Infrastructure\Traits\TanpaAksesMitra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    use BelongsToLocation, TanpaAksesMitra;

    protected $fillable = ['no_nota', 'location_id', 'kasir_id', 'total', 'metode', 'peringatan_het', 'idempotency_key'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'peringatan_het' => 'boolean'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class);
    }
}

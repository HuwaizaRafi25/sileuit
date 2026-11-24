<?php

namespace App\Modules\PointOfSale\Models;

use App\Modules\ProcurementReceiving\Models\Commodity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HetConfig extends Model
{
    protected $fillable = ['commodity_id', 'harga_per_kg', 'mode', 'aktif'];

    protected function casts(): array
    {
        return ['harga_per_kg' => 'integer', 'aktif' => 'boolean'];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}

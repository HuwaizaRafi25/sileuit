<?php

namespace App\Modules\InventoryLedger\Models;

use App\Modules\IdentityAccess\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    // PK gabungan (lot_id, location_id): semua tulis lewat query builder, bukan save().
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['lot_id', 'location_id', 'on_hand_kg', 'reserved_kg'];

    protected function casts(): array
    {
        return ['on_hand_kg' => 'decimal:3', 'reserved_kg' => 'decimal:3'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tersediaKg(): float
    {
        return round((float) $this->on_hand_kg - (float) $this->reserved_kg, 3);
    }
}

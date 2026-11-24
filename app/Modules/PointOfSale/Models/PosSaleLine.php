<?php

namespace App\Modules\PointOfSale\Models;

use App\Modules\InventoryLedger\Models\Lot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleLine extends Model
{
    protected $fillable = ['pos_sale_id', 'lot_id', 'commodity_id', 'grade_id', 'qty_kg', 'harga_per_kg', 'subtotal'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'harga_per_kg' => 'integer', 'subtotal' => 'integer'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}

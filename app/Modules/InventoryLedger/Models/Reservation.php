<?php

namespace App\Modules\InventoryLedger\Models;

use App\Modules\OrderFulfillment\Models\SalesOrderLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = ['sales_order_line_id', 'lot_id', 'location_id', 'qty_kg', 'status', 'override_kedaluwarsa', 'disetujui_oleh'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'override_kedaluwarsa' => 'boolean'];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class, 'sales_order_line_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}

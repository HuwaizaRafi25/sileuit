<?php

namespace App\Modules\OrderFulfillment\Models;

use App\Modules\InventoryLedger\Models\Reservation;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderLine extends Model
{
    protected $fillable = ['sales_order_id', 'commodity_id', 'grade_id', 'qty_kg', 'qty_terpenuhi'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'qty_terpenuhi' => 'decimal:3'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function sisaKg(): float
    {
        return round((float) $this->qty_kg - (float) $this->qty_terpenuhi, 3);
    }
}

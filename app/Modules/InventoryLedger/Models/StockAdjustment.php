<?php

namespace App\Modules\InventoryLedger\Models;

use App\Infrastructure\Traits\BelongsToLocation;
use App\Infrastructure\Traits\TanpaAksesMitra;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use BelongsToLocation, TanpaAksesMitra;

    protected $fillable = ['no_dok', 'tipe', 'location_id', 'lot_id', 'commodity_id', 'grade_id', 'qty_kg', 'tanggal', 'alasan', 'status', 'dibuat_oleh', 'disetujui_oleh'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'tanggal' => 'date'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
}

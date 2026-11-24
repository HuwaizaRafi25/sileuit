<?php

namespace App\Modules\InventoryLedger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferLine extends Model
{
    protected $fillable = ['stock_transfer_id', 'lot_id', 'kg_kirim', 'kg_terima', 'selisih_kg'];

    protected function casts(): array
    {
        return ['kg_kirim' => 'decimal:3', 'kg_terima' => 'decimal:3', 'selisih_kg' => 'decimal:3'];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}

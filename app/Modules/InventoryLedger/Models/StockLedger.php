<?php

namespace App\Modules\InventoryLedger\Models;

use App\Modules\IdentityAccess\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class StockLedger extends Model
{
    public $timestamps = false;

    protected $fillable = ['lot_id', 'location_id', 'jenis_mutasi', 'qty_kg', 'ref_tipe', 'ref_id', 'waktu', 'oleh'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'waktu' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('stock_ledgers append-only (IR-07).'));
        static::deleting(fn () => throw new RuntimeException('stock_ledgers append-only (IR-07).'));
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

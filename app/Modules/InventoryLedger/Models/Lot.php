<?php

namespace App\Modules\InventoryLedger\Models;

use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = ['kode_lot', 'commodity_id', 'grade_id', 'receipt_id', 'parent_lot_id', 'tgl_panen', 'tgl_terima', 'batas_simpan', 'status'];

    protected function casts(): array
    {
        return ['tgl_panen' => 'date', 'tgl_terima' => 'date', 'batas_simpan' => 'date'];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    public function persenUmur(): float
    {
        $total = $this->tgl_terima->diffInDays($this->batas_simpan);
        $lewat = $this->tgl_terima->diffInDays(now()->startOfDay(), false);

        return $total > 0 ? round(max(0, $lewat) / $total * 100, 1) : 0.0;
    }
}

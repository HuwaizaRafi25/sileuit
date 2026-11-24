<?php

namespace App\Modules\BillingAccountsReceivable\Models;

use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = ['invoice_id', 'commodity_id', 'grade_id', 'qty_kg', 'harga_per_kg', 'subtotal'];

    protected function casts(): array
    {
        return ['qty_kg' => 'decimal:3', 'harga_per_kg' => 'integer', 'subtotal' => 'integer'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

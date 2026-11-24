<?php

namespace App\Modules\BillingAccountsReceivable\Models;

use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $fillable = ['payment_id', 'invoice_id', 'receipt_id', 'sale_id', 'jumlah_alokasi'];

    protected function casts(): array
    {
        return ['jumlah_alokasi' => 'integer'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}

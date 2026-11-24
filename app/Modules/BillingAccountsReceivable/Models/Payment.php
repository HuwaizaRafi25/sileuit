<?php

namespace App\Modules\BillingAccountsReceivable\Models;

use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = ['arah', 'partner_id', 'metode', 'jumlah', 'referensi', 'idempotency_key', 'dicatat_oleh'];

    protected function casts(): array
    {
        return ['jumlah' => 'integer'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}

<?php

namespace App\Modules\OrderFulfillment\Models;

use App\Infrastructure\Traits\BelongsToLocation;
use App\Infrastructure\Traits\TanpaAksesMitra;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    use BelongsToLocation, TanpaAksesMitra;

    protected $fillable = ['no_dok', 'partner_id', 'location_id', 'lokasi_tujuan_id', 'status', 'dibuat_oleh'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'sales_order_id');
    }
}

<?php

namespace App\Modules\BillingAccountsReceivable\Models;

use App\Infrastructure\Traits\BelongsToLocation;
use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToLocation;

    protected $fillable = ['no', 'partner_id', 'sales_order_id', 'location_id', 'tgl_invoice', 'jatuh_tempo', 'total', 'dibuat_oleh', 'disetujui'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'tgl_invoice' => 'date', 'jatuh_tempo' => 'date', 'disetujui' => 'boolean'];
    }

    protected static function booted(): void
    {
        // BR-16: mitra B2B hanya melihat invoice miliknya.
        static::addGlobalScope('milikMitra', function (Builder $q) {
            $u = auth()->user();
            if ($u && $u->hasRole('MITRA_B2B')) {
                $q->where('partner_id', $u->partner_id);
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function dibayar(): int
    {
        return (int) $this->allocations()->sum('jumlah_alokasi');
    }

    public function saldo(): int
    {
        return (int) $this->total - $this->dibayar();
    }

    public function status(): string
    {
        $dibayar = $this->dibayar();
        if ($dibayar === 0) {
            return now()->greaterThan($this->jatuh_tempo) ? 'OVERDUE' : 'ISSUED';
        }
        if ($dibayar >= (int) $this->total) {
            return 'PAID';
        }

        return now()->greaterThan($this->jatuh_tempo) ? 'OVERDUE' : 'PARTIAL';
    }

    public function hariTelat(): int
    {
        return now()->greaterThan($this->jatuh_tempo) ? now()->diffInDays($this->jatuh_tempo) : 0;
    }

    public function agingBucket(): string
    {
        $h = $this->hariTelat();
        if ($h <= 0) {
            return 'CURRENT';
        }
        if ($h <= 7) {
            return '1-7';
        }
        if ($h <= 14) {
            return '8-14';
        }
        if ($h <= 30) {
            return '15-30';
        }

        return '>30';
    }
}

<?php

namespace App\Modules\ProcurementReceiving\Models;

use App\Infrastructure\Traits\BelongsToLocation;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\Lot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use BelongsToLocation;

    protected $fillable = [
        'no_dok', 'contract_id', 'location_id', 'harvest_plan_id', 'bruto_kg', 'tara_kg', 'netto_kg',
        'harga_per_kg', 'nilai_bayar', 'status', 'selisih_persen', 'selisih_alasan',
        'selisih_disetujui_oleh', 'diterima_oleh', 'waktu_timbang', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'bruto_kg' => 'decimal:3', 'tara_kg' => 'decimal:3', 'netto_kg' => 'decimal:3',
            'selisih_persen' => 'decimal:2', 'harga_per_kg' => 'integer', 'nilai_bayar' => 'integer',
            'waktu_timbang' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // BR-16: petani/mitra hanya melihat baris miliknya sendiri.
        static::addGlobalScope('partner', function (Builder $q) {
            $u = auth()->user();
            if ($u && $u->hasRole('PETANI', 'MITRA_B2B')) {
                $q->whereHas('contract', fn (Builder $c) => $c->where('partner_id', $u->partner_id));
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function harvestPlan(): BelongsTo
    {
        return $this->belongsTo(HarvestPlan::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selisih_disetujui_oleh');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function inspectionTerakhir(): ?Inspection
    {
        return $this->inspections()->latest('id')->first();
    }

    public function butuhPersetujuanSelisih(): bool
    {
        return $this->selisih_persen !== null && abs((float) $this->selisih_persen) > 1.0;
    }
}

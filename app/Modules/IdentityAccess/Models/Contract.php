<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Contract extends Model
{
    protected $fillable = ['partner_id', 'standard_package_id', 'musim', 'mulai', 'selesai', 'estimasi_kg', 'status'];

    protected function casts(): array
    {
        return ['mulai' => 'date', 'selesai' => 'date', 'estimasi_kg' => 'decimal:3'];
    }

    protected static function booted(): void
    {
        // Q-D2: satu kontrak AKTIF per petani per musim.
        static::saving(function (self $contract) {
            if ($contract->status !== 'AKTIF') {
                return;
            }
            $ada = static::query()
                ->where('partner_id', $contract->partner_id)
                ->where('musim', $contract->musim)
                ->where('status', 'AKTIF');
            if ($contract->exists) {
                $ada->whereKeyNot($contract->getKey());
            }
            if ($ada->exists()) {
                throw ValidationException::withMessages(['musim' => 'Sudah ada kontrak aktif untuk petani ini pada musim tersebut (Q-D2).']);
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(StandardPackage::class, 'standard_package_id');
    }

    public function harvestPlans(): HasMany
    {
        return $this->hasMany(HarvestPlan::class);
    }
}

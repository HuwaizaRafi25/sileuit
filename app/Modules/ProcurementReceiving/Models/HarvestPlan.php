<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestPlan extends Model
{
    protected $fillable = ['contract_id', 'tgl_perkiraan', 'kg_perkiraan', 'status'];

    protected function casts(): array
    {
        return ['tgl_perkiraan' => 'date', 'kg_perkiraan' => 'decimal:3'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}

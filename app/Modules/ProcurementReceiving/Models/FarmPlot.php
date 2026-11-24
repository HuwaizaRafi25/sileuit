<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmPlot extends Model
{
    protected $fillable = ['partner_id', 'nama_desa', 'luas_ha'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}

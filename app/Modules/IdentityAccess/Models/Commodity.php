<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commodity extends Model
{
    protected $fillable = ['nama', 'satuan_dasar', 'umur_simpan_hari'];

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}

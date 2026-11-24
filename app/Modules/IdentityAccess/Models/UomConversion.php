<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;

class UomConversion extends Model
{
    protected $fillable = ['nama', 'faktor_ke_kg'];

    protected function casts(): array
    {
        return ['faktor_ke_kg' => 'decimal:3'];
    }
}

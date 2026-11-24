<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;

class StandardPackage extends Model
{
    protected $fillable = ['nama', 'jenis_pupuk', 'jendela_tanam', 'parameter_mutu'];

    protected function casts(): array
    {
        return ['parameter_mutu' => 'array'];
    }
}

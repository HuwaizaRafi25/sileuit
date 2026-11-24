<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = ['tipe', 'nama', 'hp', 'alamat', 'kelompok', 'batas_kredit', 'termin_hari', 'no_rekening', 'status'];

    protected function casts(): array
    {
        return [
            'hp' => 'encrypted',
            'no_rekening' => 'encrypted',
            'batas_kredit' => 'integer',
            'termin_hari' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $partner) {
            if ($partner->isDirty('hp') && $partner->hp) {
                $partner->hp_hash = hash('sha256', $partner->hp);
            }
        });
    }

    public function farmPlots(): HasMany
    {
        return $this->hasMany(FarmPlot::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}

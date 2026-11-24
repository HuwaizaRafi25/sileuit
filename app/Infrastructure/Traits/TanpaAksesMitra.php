<?php

namespace App\Infrastructure\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TanpaAksesMitra
{
    protected static function bootTanpaAksesMitra(): void
    {
        static::addGlobalScope('bukanMitra', function (Builder $q) {
            $u = auth()->user();
            if ($u && $u->hasRole('PETANI', 'MITRA_B2B')) {
                $q->whereRaw('1 = 0');
            }
        });
    }
}

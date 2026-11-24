<?php

namespace App\Infrastructure\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToLocation
{
    protected static function bootBelongsToLocation(): void
    {
        static::addGlobalScope('lokasi', function (Builder $q) {
            $user = auth()->user();
            if (! $user || $user->isGlobal() || $user->hasRole('PETANI', 'MITRA_B2B')) {
                return;
            }
            $q->whereIn($q->getModel()->getTable().'.location_id', $user->locationScopes->pluck('id'));
        });
    }
}

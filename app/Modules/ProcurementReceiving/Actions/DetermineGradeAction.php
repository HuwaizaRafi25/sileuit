<?php

namespace App\Modules\ProcurementReceiving\Actions;

use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Grade;

class DetermineGradeAction
{
    /** A-20: grade ditentukan sistem dari ambang kadar air, bukan dipilih tangan. */
    public function execute(Commodity $commodity, float $kadarAir): ?Grade
    {
        return $commodity->grades
            ->filter(fn (Grade $g) => isset($g->ambang_mutu['kadar_air_max'])
                && $kadarAir <= (float) $g->ambang_mutu['kadar_air_max'])
            ->sortBy(fn (Grade $g) => (float) $g->ambang_mutu['kadar_air_max'])
            ->first();
    }
}

<?php

namespace App\Modules\ProcurementReceiving\Actions;

use Illuminate\Validation\ValidationException;

class CalculateNetWeightAction
{
    /** IR-03: netto = bruto − tara, bruto >= tara. */
    public function execute(float $bruto, float $tara): float
    {
        if ($bruto <= 0 || $tara < 0 || $bruto < $tara) {
            throw ValidationException::withMessages(['bruto' => 'Bruto harus lebih besar atau sama dengan tara, dan lebih dari nol.']);
        }

        return round($bruto - $tara, 3);
    }

    /** BR-05: toleransi ±1% terhadap rencana panen. */
    public function selisihPersen(float $netto, ?float $rencana): ?float
    {
        if (! $rencana || $rencana <= 0) {
            return null;
        }

        return round(($netto - $rencana) / $rencana * 100, 2);
    }
}

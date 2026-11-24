<?php

namespace Database\Seeders;

use App\Modules\PointOfSale\Models\HetConfig;
use App\Modules\ProcurementReceiving\Models\Commodity;
use Illuminate\Database\Seeder;

class HetConfigSeeder extends Seeder
{
    public function run(): void
    {
        $beras = Commodity::where('nama', 'BERAS')->first();
        HetConfig::updateOrCreate(
            ['commodity_id' => $beras->id],
            ['harga_per_kg' => 13500, 'mode' => 'BLOKIR', 'aktif' => true]
        );
    }
}

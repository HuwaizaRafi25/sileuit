<?php

namespace App\Console\Commands;

use App\Modules\InventoryLedger\Models\Lot;
use Illuminate\Console\Command;

class CekUmurSimpan extends Command
{
    protected $signature = 'sileuit:cek-umur-simpan';

    protected $description = 'Tandai lot kedaluwarsa dan laporkan lot mendekati batas simpan (FR-07)';

    public function handle(): int
    {
        $kedaluwarsa = Lot::where('status', 'TERSEDIA')->whereDate('batas_simpan', '<', today())->get();
        foreach ($kedaluwarsa as $lot) {
            $lot->update(['status' => 'KEDALUWARSA']);
        }

        $mendekati = Lot::where('status', 'TERSEDIA')->get()->filter(fn (Lot $l) => $l->persenUmur() >= 80);

        $this->info('Lot baru kedaluwarsa: '.$kedaluwarsa->count());
        $this->info('Lot >= 80% umur simpan: '.$mendekati->count());
        foreach ($mendekati as $l) {
            $this->line(" - {$l->kode_lot} ({$l->persenUmur()}%)");
        }

        return self::SUCCESS;
    }
}

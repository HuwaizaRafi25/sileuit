<?php

namespace App\Console\Commands;

use App\Modules\AuditNotification\Models\AlertNotification;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\InventoryLedger\Models\Lot;
use App\Modules\InventoryLedger\Models\StockTransfer;
use Illuminate\Console\Command;

class BuatNotifikasi extends Command
{
    protected $signature = 'sileuit:buat-notifikasi';

    protected $description = 'Buat notifikasi in-app: umur simpan ≥80%, invoice lewat jatuh tempo, transfer selisih (FR-17)';

    public function handle(): int
    {
        $dibuat = 0;

        $lots = Lot::where('status', 'TERSEDIA')->get()->filter(fn (Lot $l) => $l->persenUmur() >= 80);
        foreach ($lots as $lot) {
            $ada = AlertNotification::withoutGlobalScopes()->where('tipe', 'UMUR_SIMPAN')->where('entitas', 'lots')->where('entitas_id', $lot->id)->exists();
            if (! $ada) {
                AlertNotification::create([
                    'tipe' => 'UMUR_SIMPAN',
                    'judul' => "Lot {$lot->kode_lot} mendekati batas simpan",
                    'isi' => "Umur simpan {$lot->persenUmur()}%. Prioritaskan pengeluaran (FEFO).",
                    'entitas' => 'lots', 'entitas_id' => $lot->id, 'untuk_peran' => 'KEPALA_GUDANG',
                ]);
                $dibuat++;
            }
        }

        $overdue = Invoice::withoutGlobalScopes()->where('jatuh_tempo', '<', today())->get()->filter(fn (Invoice $i) => $i->saldo() > 0);
        foreach ($overdue as $inv) {
            $ada = AlertNotification::withoutGlobalScopes()->where('tipe', 'INVOICE_OVERDUE')->where('entitas', 'invoices')->where('entitas_id', $inv->id)->exists();
            if (! $ada) {
                AlertNotification::create([
                    'tipe' => 'INVOICE_OVERDUE',
                    'judul' => "Invoice {$inv->no} lewat jatuh tempo",
                    'isi' => 'Saldo Rp'.number_format($inv->saldo(), 0, ',', '.').' belum dibayar. Kirim pengingat ke mitra.',
                    'entitas' => 'invoices', 'entitas_id' => $inv->id, 'untuk_peran' => 'KEUANGAN',
                ]);
                $dibuat++;
            }
        }

        $disc = StockTransfer::withoutGlobalScopes()->where('status', 'DISCREPANCY')->get();
        foreach ($disc as $t) {
            $ada = AlertNotification::withoutGlobalScopes()->where('tipe', 'TRANSFER_SELIH')->where('entitas', 'stock_transfers')->where('entitas_id', $t->id)->exists();
            if (! $ada) {
                AlertNotification::create([
                    'tipe' => 'TRANSFER_SELIH',
                    'judul' => "Transfer {$t->no_dok} selisih",
                    'isi' => 'Ada selisih terima. Selesaikan lewat penyesuaian beralasan.',
                    'entitas' => 'stock_transfers', 'entitas_id' => $t->id, 'untuk_peran' => 'KEPALA_GUDANG',
                    'location_id' => $t->ke_lokasi,
                ]);
                $dibuat++;
            }
        }

        $this->info("Notifikasi baru dibuat: {$dibuat}");

        return self::SUCCESS;
    }
}

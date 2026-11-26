<?php

namespace App\Console\Commands;

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Models\StockLedger;
use Illuminate\Console\Command;

class VerifikasiIntegritas extends Command
{
    protected $signature = 'sileuit:verifikasi-integritas';

    protected $description = 'Periksa invariant: saldo ledger = stock_balance, on-hand ≥ 0, alokasi ≤ total invoice, satu penerimaan satu pembayaran (J.5, Bagian I.5)';

    public function handle(): int
    {
        $gagal = 0;

        $ledgers = StockLedger::select('lot_id', 'location_id')->selectRaw('SUM(qty_kg) as total')->groupBy('lot_id', 'location_id')->get();
        foreach ($ledgers as $l) {
            $balance = StockBalance::where('lot_id', $l->lot_id)->where('location_id', $l->location_id)->first();
            $saldo = $balance ? (float) $balance->on_hand_kg : 0.0;
            if (abs(round((float) $l->total, 3) - round($saldo, 3)) > 0.001) {
                $this->error("SELISIH ledger lot {$l->lot_id} lokasi {$l->location_id}: ledger {$l->total} vs saldo {$saldo}");
                $gagal++;
            }
        }

        $neg = StockBalance::where('on_hand_kg', '<', 0)->count();
        if ($neg > 0) {
            $this->error("Ada {$neg} saldo on-hand negatif (BR-03)");
            $gagal++;
        }

        foreach (Invoice::withoutGlobalScopes()->get() as $inv) {
            if ($inv->dibayar() > (int) $inv->total) {
                $this->error("Invoice {$inv->no}: alokasi melebihi total (IR-10)");
                $gagal++;
            }
        }

        $ganda = PaymentAllocation::select('receipt_id')->whereNotNull('receipt_id')->groupBy('receipt_id')->havingRaw('COUNT(*) > 1')->count();
        if ($ganda > 0) {
            $this->error("Ada {$ganda} penerimaan dengan alokasi ganda (BR-07)");
            $gagal++;
        }

        if ($gagal === 0) {
            $this->info('INTEGRITAS OK: semua invariant terpenuhi.');

            return self::SUCCESS;
        }
        $this->error("INTEGRITAS GAGAL: {$gagal} masalah.");

        return self::FAILURE;
    }
}

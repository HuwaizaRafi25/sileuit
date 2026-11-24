<?php

namespace App\Modules\PointOfSale\Actions;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\InventoryLedger\Models\StockBalance;
use App\Modules\InventoryLedger\Services\StockLedgerWriter;
use App\Modules\PointOfSale\Models\HetConfig;
use App\Modules\PointOfSale\Models\PosSale;
use App\Modules\PointOfSale\Models\PosSaleLine;
use App\Modules\ProcurementReceiving\Models\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessPosCheckoutAction
{
    public function __construct(private StockLedgerWriter $ledger) {}

    /** BR-09 / BR-11 / IR-05: jual tidak melebihi stok, HET ditegakkan, row-locking anti-minus. */
    public function execute(User $kasir, int $locationId, array $lines, string $metode, string $idempotencyKey): PosSale
    {
        return DB::transaction(function () use ($kasir, $locationId, $lines, $metode, $idempotencyKey) {
            $sudah = PosSale::withoutGlobalScopes()->where('idempotency_key', $idempotencyKey)->first();
            if ($sudah) {
                return $sudah;
            }

            $sale = PosSale::create([
                'no_nota' => $this->noNota(), 'location_id' => $locationId, 'kasir_id' => $kasir->id,
                'total' => 0, 'metode' => $metode, 'idempotency_key' => $idempotencyKey,
            ]);

            $total = 0;
            $peringatan = false;

            foreach ($lines as $line) {
                $kom = (int) $line['commodity_id'];
                $grade = (int) $line['grade_id'];
                $kodeGrade = Grade::whereKey($grade)->value('kode');
                $grade = (int) Grade::where('commodity_id', $kom)->where('kode', $kodeGrade)->value('id');
                $qty = (float) $line['qty_kg'];
                $harga = (int) $line['harga_per_kg'];

                if ($grade === 0) {
                    throw ValidationException::withMessages(['grade_id' => 'Grade tidak tersedia untuk komoditas yang dipilih.']);
                }

                if ($qty <= 0 || $harga <= 0) {
                    throw ValidationException::withMessages(['line' => 'Qty dan harga harus positif.']);
                }

                $het = HetConfig::where('commodity_id', $kom)->where('aktif', true)->first();
                if ($het && $harga > (int) $het->harga_per_kg) {
                    if ($het->mode === 'BLOKIR') {
                        throw ValidationException::withMessages(['het' => 'Harga melebihi HET (Rp'.number_format($het->harga_per_kg, 0, ',', '.').'). Transaksi diblokir (BR-11).']);
                    }
                    $peringatan = true;
                }

                $balances = StockBalance::where('stock_balances.location_id', $locationId)
                    ->join('lots', 'lots.id', '=', 'stock_balances.lot_id')
                    ->where('lots.commodity_id', $kom)
                    ->where('lots.grade_id', $grade)
                    ->where('lots.status', 'TERSEDIA')
                    ->whereDate('lots.batas_simpan', '>=', today())
                    ->whereRaw('(stock_balances.on_hand_kg - stock_balances.reserved_kg) > 0')
                    ->orderBy('lots.batas_simpan')
                    ->orderBy('lots.tgl_terima')
                    ->orderBy('lots.id')
                    ->select('stock_balances.*')
                    ->lockForUpdate()
                    ->get()
                    ->map(fn ($row) => StockBalance::where('lot_id', $row->lot_id)->where('location_id', $row->location_id)->first());

                $totalTersedia = round($balances->sum(fn (StockBalance $b) => $b->tersediaKg()), 3);
                if ($qty > $totalTersedia) {
                    throw ValidationException::withMessages(['stok' => 'Stok tersedia '.number_format($totalTersedia, 3, ',', '.').' kg. Kurangi jumlah (BR-09).']);
                }

                $sisa = $qty;
                foreach ($balances as $b) {
                    if ($sisa <= 0) {
                        break;
                    }
                    $ambil = round(min($sisa, $b->tersediaKg()), 3);
                    if ($ambil <= 0) {
                        continue;
                    }
                    $this->ledger->post($b->lot, $locationId, 'JUAL', -$ambil, 'SALE', $sale->id, $kasir);
                    PosSaleLine::create([
                        'pos_sale_id' => $sale->id, 'lot_id' => $b->lot_id, 'commodity_id' => $kom, 'grade_id' => $grade,
                        'qty_kg' => $ambil, 'harga_per_kg' => $harga, 'subtotal' => (int) round($ambil * $harga),
                    ]);
                    $total += (int) round($ambil * $harga);
                    $sisa = round($sisa - $ambil, 3);
                }
            }

            $sale->update(['total' => $total, 'peringatan_het' => $peringatan]);

            // R-05: pembayaran kasir ikut rekonsiliasi lewat PaymentAllocation.sale_id.
            $payment = Payment::create([
                'arah' => 'MASUK', 'partner_id' => null, 'metode' => $metode, 'jumlah' => $total,
                'referensi' => 'POS '.$sale->no_nota, 'idempotency_key' => $idempotencyKey.':pay', 'dicatat_oleh' => $kasir->id,
            ]);
            PaymentAllocation::create(['payment_id' => $payment->id, 'sale_id' => $sale->id, 'jumlah_alokasi' => $total]);

            AuditLogger::log('POS_PENJUALAN', 'pos_sales', $sale->id, null, ['total' => $total, 'peringatan_het' => $peringatan]);

            return $sale->fresh();
        });
    }

    private function noNota(): string
    {
        return sprintf('NOTA-%s-%03d', now()->format('ymd'), PosSale::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1);
    }
}

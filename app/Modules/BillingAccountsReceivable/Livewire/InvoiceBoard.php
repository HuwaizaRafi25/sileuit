<?php

namespace App\Modules\BillingAccountsReceivable\Livewire;

use App\Modules\BillingAccountsReceivable\Actions\IssueInvoiceAction;
use App\Modules\BillingAccountsReceivable\Actions\RecordInvoicePaymentAction;
use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Services\AgingCalculator;
use App\Modules\OrderFulfillment\Models\SalesOrder;
use App\Modules\ProcurementReceiving\Models\Partner;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class InvoiceBoard extends Component
{
    public ?int $order_id = null;

    public ?int $partner_id = null;

    public string $jumlah = '';

    public string $metode = 'TRANSFER';

    public string $referensi = '';

    public ?string $pesan = null;

    public bool $butuhPersetujuanKredit = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-invoice'), 403);
    }

    public function updatedOrderId(): void
    {
        $this->butuhPersetujuanKredit = false;
    }

    public function terbitkan(IssueInvoiceAction $aksi, bool $paksa = false): void
    {
        abort_unless(auth()->user()->can('terbit-invoice'), 403);
        if ($paksa) {
            abort_unless(auth()->user()->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN'), 403);
        }
        if (! $this->order_id) {
            $this->addError('order_id', 'Pilih pesanan DELIVERED.');

            return;
        }
        try {
            $inv = $aksi->execute(SalesOrder::withoutGlobalScopes()->findOrFail($this->order_id), auth()->user(), $paksa);
            $this->pesan = 'Invoice '.$inv->no.' terbit. Jatuh tempo '.$inv->jatuh_tempo->format('d M Y').'.';
            $this->butuhPersetujuanKredit = false;
            $this->reset('order_id');
        } catch (ValidationException $e) {
            if (array_key_exists('kredit', $e->errors())) {
                $this->butuhPersetujuanKredit = true;
            }
            $this->addError('terbit', collect($e->errors())->flatten()->first());
        }
    }

    public function catatBayar(RecordInvoicePaymentAction $aksi): void
    {
        abort_unless(auth()->user()->can('terbit-invoice'), 403);
        $this->jumlah = str_replace(['.', ',', ' '], '', trim($this->jumlah));
        $this->validate(['partner_id' => 'required|exists:partners,id', 'jumlah' => 'required|integer|min:1', 'referensi' => 'required|string']);
        try {
            $aksi->execute(auth()->user(), (int) $this->partner_id, (int) $this->jumlah, $this->metode, $this->referensi, (string) Str::uuid());
            $this->pesan = 'Pembayaran tercatat dan dialokasikan ke invoice tertua.';
            $this->reset('partner_id', 'jumlah', 'referensi');
        } catch (ValidationException $e) {
            $this->addError('bayar', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.invoice-board', [
            'daftar' => Invoice::with('partner')->latest('id')->limit(30)->get(),
            'pesananSiap' => SalesOrder::withoutGlobalScopes()->where('status', 'DELIVERED')->whereNotNull('partner_id')->whereDoesntHave('invoice')->with('partner')->get(),
            'mitra' => Partner::whereIn('tipe', ['RETAIL', 'CUSTOMER'])->orderBy('nama')->get(),
            'aging' => app(AgingCalculator::class)->hitung(),
        ]);
    }
}

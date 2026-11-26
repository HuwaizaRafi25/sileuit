<?php

namespace App\Infrastructure\Http\Controllers;

use App\Modules\BillingAccountsReceivable\Models\Invoice;
use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\BillingAccountsReceivable\Models\PaymentAllocation;
use App\Modules\OrderFulfillment\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ExportController
{
    public function slip(int $paymentId): Response
    {
        abort_unless(auth()->user()->hasRole('KEUANGAN', 'ADMIN', 'PEMILIK'), 403);
        $payment = Payment::with('partner')->findOrFail($paymentId);
        $alloc = PaymentAllocation::where('payment_id', $payment->id)->whereNotNull('receipt_id')->with('receipt.contract.partner')->first();
        $pdf = Pdf::loadView('cetak.slip', ['payment' => $payment, 'alloc' => $alloc])->setPaper('a4');

        return $pdf->download('slip-pembayaran-'.$payment->id.'.pdf');
    }

    public function suratJalan(int $shipmentId): Response
    {
        abort_unless(auth()->user()->can('lihat-fulfillment') || auth()->user()->hasRole('MITRA_B2B'), 403);
        $sj = Shipment::withoutGlobalScopes()->with('order.lines.commodity', 'order.lines.grade', 'order.partner')->findOrFail($shipmentId);
        $pdf = Pdf::loadView('cetak.surat-jalan', ['sj' => $sj])->setPaper('a4');

        return $pdf->download($sj->no_dok.'.pdf');
    }

    public function invoice(int $invoiceId): Response
    {
        abort_unless(auth()->user()->can('lihat-invoice'), 403);
        $inv = Invoice::withoutGlobalScopes()->with('partner', 'lines.commodity', 'lines.grade')->findOrFail($invoiceId);
        $pdf = Pdf::loadView('cetak.invoice', ['inv' => $inv])->setPaper('a4');

        return $pdf->download($inv->no.'.pdf');
    }
}

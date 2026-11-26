<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Slip Pembayaran</title>
<style>body{font-family:sans-serif;font-size:12px;color:#1F2A1C}.head{font-size:16px;font-weight:bold;color:#2F5A27}table{width:100%;border-collapse:collapse;margin-top:12px}td,th{border:1px solid #999;padding:6px;text-align:left}</style></head>
<body>
<p class="head">SiLeuit.id — Slip Pembayaran Petani</p>
<table>
<tr><th style="width:35%">No. Pembayaran</th><td>PAY-{{ $payment->id }}</td></tr>
<tr><th>Petani</th><td>{{ $payment->partner->nama ?? '-' }}</td></tr>
<tr><th>Metode</th><td>{{ $payment->metode }}</td></tr>
<tr><th>Jumlah</th><td>Rp{{ number_format($payment->jumlah, 0, ',', '.') }}</td></tr>
<tr><th>Referensi bukti</th><td>{{ $payment->referensi }}</td></tr>
<tr><th>Tanggal</th><td>{{ $payment->created_at->format('d M Y H:i') }} WIB</td></tr>
@if ($alloc && $alloc->receipt)
<tr><th>Penerimaan</th><td>{{ $alloc->receipt->no_dok }} · {{ number_format((float) $alloc->receipt->netto_kg, 3, ',', '.') }} kg</td></tr>
@endif
</table>
<p style="margin-top:16px;color:#5B6655">Dicetak {{ now()->format('d M Y H:i') }} WIB · Dokumen resmi, simpan sebagai bukti.</p>
</body></html>

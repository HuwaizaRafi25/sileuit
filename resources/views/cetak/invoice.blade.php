<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Invoice</title>
<style>body{font-family:sans-serif;font-size:12px;color:#1F2A1C}.head{font-size:16px;font-weight:bold;color:#2F5A27}table{width:100%;border-collapse:collapse;margin-top:12px}td,th{border:1px solid #999;padding:6px;text-align:left}.kanan{text-align:right}</style></head>
<body>
<p class="head">SiLeuit.id — Invoice</p>
<table>
<tr><th style="width:35%">No. Invoice</th><td>{{ $inv->no }}</td></tr>
<tr><th>Mitra</th><td>{{ $inv->partner->nama }}</td></tr>
<tr><th>Tanggal invoice</th><td>{{ $inv->tgl_invoice->format('d M Y') }}</td></tr>
<tr><th>Jatuh tempo</th><td>{{ $inv->jatuh_tempo->format('d M Y') }}</td></tr>
<tr><th>Status</th><td>{{ $inv->status() }}</td></tr>
</table>
<table style="margin-top:12px">
<tr><th>Komoditas</th><th>Grade</th><th class="kanan">Qty (kg)</th><th class="kanan">Harga/kg</th><th class="kanan">Subtotal</th></tr>
@foreach ($inv->lines as $l)
<tr><td>{{ $l->commodity->nama }}</td><td>{{ $l->grade->kode }}</td><td class="kanan">{{ number_format((float) $l->qty_kg, 3, ',', '.') }}</td><td class="kanan">Rp{{ number_format($l->harga_per_kg, 0, ',', '.') }}</td><td class="kanan">Rp{{ number_format($l->subtotal, 0, ',', '.') }}</td></tr>
@endforeach
</table>
<table style="margin-top:12px">
<tr><th class="kanan">Total</th><td class="kanan">Rp{{ number_format($inv->total, 0, ',', '.') }}</td></tr>
<tr><th class="kanan">Dibayar</th><td class="kanan">Rp{{ number_format($inv->dibayar(), 0, ',', '.') }}</td></tr>
<tr><th class="kanan">Saldo</th><td class="kanan">Rp{{ number_format($inv->saldo(), 0, ',', '.') }}</td></tr>
</table>
<p style="margin-top:16px;color:#5B6655">Mohon lakukan pembayaran sebelum jatuh tempo. Konfirmasi: Keuangan SiLeuit.id.</p>
</body></html>

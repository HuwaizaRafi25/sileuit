<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Surat Jalan</title>
<style>body{font-family:sans-serif;font-size:12px;color:#1F2A1C}.head{font-size:16px;font-weight:bold;color:#2F5A27}table{width:100%;border-collapse:collapse;margin-top:12px}td,th{border:1px solid #999;padding:6px;text-align:left}</style></head>
<body>
<p class="head">SiLeuit.id — Surat Jalan</p>
<table>
<tr><th style="width:35%">No. Surat Jalan</th><td>{{ $sj->no_dok }}</td></tr>
<tr><th>Pesanan</th><td>{{ $sj->order->no_dok }}</td></tr>
<tr><th>Mitra</th><td>{{ $sj->order->partner->nama ?? '-' }}</td></tr>
<tr><th>Status</th><td>{{ $sj->status }}</td></tr>
<tr><th>Jumlah dikirim</th><td>{{ number_format((float) $sj->jumlah_dikirim_kg, 3, ',', '.') }} kg</td></tr>
<tr><th>Waktu kirim</th><td>{{ $sj->waktu_kirim?->format('d M Y H:i') ?? '-' }}</td></tr>
</table>
<table style="margin-top:12px">
<tr><th>Komoditas</th><th>Grade</th><th>Qty (kg)</th></tr>
@foreach ($sj->order->lines as $l)
<tr><td>{{ $l->commodity->nama }}</td><td>{{ $l->grade->kode }}</td><td>{{ number_format((float) $l->qty_terpenuhi, 3, ',', '.') }}</td></tr>
@endforeach
</table>
<p style="margin-top:16px;color:#5B6655">Diterima oleh: ____________  Tanggal: ____________  Tanda tangan: ____________</p>
</body></html>

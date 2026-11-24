<x-layouts.app title="Kartu Stok — SiLeuit.id">
    <div class="mx-auto max-w-3xl px-4 py-6">
        <a href="/app/stok" class="text-sm text-primary">← Stok</a>
        <div class="mt-2 rounded-[12px] border border-line bg-card p-5">
            <h1 class="font-mono text-lg font-semibold text-primary">{{ $lot->kode_lot }}</h1>
            <p class="mt-1 text-base">{{ $lot->commodity->nama }} {{ $lot->grade?->kode }} · {{ $lot->status }}</p>
            <p class="text-sm text-ink-soft">Terima {{ $lot->tgl_terima->format('d M Y') }} · batas simpan {{ $lot->batas_simpan->format('d M Y') }} · umur {{ $lot->persenUmur() }}%</p>
        </div>
        <h2 class="mt-5 font-display text-lg font-semibold text-primary">Kartu stok (append-only)</h2>
        <div class="mt-2 overflow-x-auto rounded-[12px] border border-line bg-card">
            <table class="w-full text-sm">
                <thead class="bg-cream text-left text-ink-soft">
                    <tr><th class="p-3">Waktu</th><th class="p-3">Lokasi</th><th class="p-3">Mutasi</th><th class="p-3 text-right">Qty</th><th class="p-3 text-right">Saldo</th></tr>
                </thead>
                <tbody>
                    @forelse ($mutasi as $m)
                        <tr class="border-t border-line">
                            <td class="p-3">{{ $m->waktu->format('d M Y H:i') }}</td>
                            <td class="p-3">{{ $m->location->kode }}</td>
                            <td class="p-3">{{ $m->jenis_mutasi }}</td>
                            <td class="p-3 text-right {{ (float) $m->qty_kg < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $m->qty_kg, 3, ',', '.') }}</td>
                            <td class="p-3 text-right font-semibold">{{ number_format($m->saldo_jalan, 3, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-ink-soft">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>

<x-layouts.app title="Kartu Stok — SiLeuit.id">
    <div class="mx-auto max-w-5xl px-4 py-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="/app/stok" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Daftar Stok</a>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="font-mono text-2xl font-bold text-primary">{{ $lot->kode_lot }}</h1>
                    <span class="rounded-full bg-cream border border-line px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ $lot->commodity->nama }} Grade {{ $lot->grade?->kode }}
                    </span>
                    <span class="rounded-full bg-success-bg px-2.5 py-0.5 text-xs font-semibold text-success">
                        {{ $lot->status }}
                    </span>
                </div>
                <p class="text-xs text-ink-soft">
                    Buku Besar Mutasi Append-Only (Immutable Audit Trail)
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-ink-soft">Terima: <strong class="text-ink">{{ $lot->tgl_terima->format('d M Y') }}</strong></p>
                <p class="text-xs text-ink-soft">Batas Simpan: <strong class="text-ink">{{ $lot->batas_simpan->format('d M Y') }}</strong></p>
            </div>
        </div>

        <!-- Shelf Life Progress Banner -->
        @php
            $persen = $lot->persenUmur();
            $barColor = match(true) {
                $persen >= 100 || $lot->status === 'KEDALUWARSA' => 'bg-danger',
                $persen >= 80 => 'bg-warning',
                default => 'bg-success',
            };
        @endphp
        <div class="mt-6 rounded-[14px] border border-line bg-card p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs text-ink-soft mb-1.5">
                <span class="font-semibold">Umur Simpan Lot: {{ $persen }}%</span>
                <span>Batas kedaluwarsa: {{ $lot->batas_simpan->format('d M Y') }}</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-paper border border-line/60">
                <div class="h-full {{ $barColor }}" style="width: {{ min(100, $persen) }}%"></div>
            </div>
        </div>

        <!-- Append-Only Mutation Table -->
        <div class="mt-6 overflow-hidden rounded-[14px] border border-line bg-card shadow-xs">
            <div class="border-b border-line/60 bg-paper px-5 py-3">
                <h2 class="font-display text-base font-bold text-primary">Kartu Stok Fisik (Buku Besar)</h2>
                <p class="text-xs text-ink-soft">Setiap baris mutasi terkunci dan tidak dapat diubah atau dihapus.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-cream/60 text-left text-xs font-semibold text-ink-soft uppercase tracking-wider">
                        <tr>
                            <th class="p-3.5">Waktu Transaksi</th>
                            <th class="p-3.5">Lokasi Gudang</th>
                            <th class="p-3.5">Jenis Mutasi</th>
                            <th class="p-3.5 text-right">Kuantitas (kg)</th>
                            <th class="p-3.5 text-right">Saldo Berjalan (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/50">
                        @forelse ($mutasi as $m)
                            <tr class="hover:bg-paper/80 transition">
                                <td class="p-3.5 font-mono text-xs text-ink-soft">{{ $m->waktu->format('d M Y, H:i') }}</td>
                                <td class="p-3.5">
                                    <span class="rounded-[6px] bg-cream px-2 py-0.5 text-xs font-semibold text-primary">
                                        {{ $m->location->kode }}
                                    </span>
                                </td>
                                <td class="p-3.5 font-medium text-ink">{{ $m->jenis_mutasi }}</td>
                                <td class="p-3.5 text-right font-mono font-bold {{ (float) $m->qty_kg < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ (float) $m->qty_kg > 0 ? '+' : '' }}{{ number_format((float) $m->qty_kg, 3, ',', '.') }}
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold text-primary">
                                    {{ number_format($m->saldo_jalan, 3, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-ink-soft">
                                    Belum ada catatan mutasi untuk lot ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>

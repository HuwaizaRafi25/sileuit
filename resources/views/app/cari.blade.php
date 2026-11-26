<x-layouts.app title="Pencarian Global — SiLeuit.id">
    <div class="mx-auto max-w-5xl px-4 py-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
                <h1 class="mt-1 font-display text-2xl font-bold text-primary">Pencarian Global Sistem</h1>
                <p class="text-xs text-ink-soft">Cari kode lot, nomor dokumen (GRN/SO/SJ/INV), atau nama mitra</p>
            </div>
        </div>

        <!-- Search Input -->
        <div class="mt-6 rounded-[14px] border border-line bg-card p-4 shadow-xs">
            <form method="GET" action="/app/cari" class="flex gap-2">
                <input name="q" value="{{ $q }}" type="text" placeholder="Masukkan nomor dokumen, kode lot, atau nama mitra..."
                    class="h-11 flex-1 rounded-[10px] border border-line bg-paper px-3.5 text-sm text-ink focus:border-primary focus:outline-none">
                <button type="submit" class="h-11 rounded-[10px] bg-primary px-6 text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                    Cari
                </button>
            </form>
        </div>

        <!-- Search Results -->
        @if ($q !== '')
            <div class="mt-6 space-y-4">
                @foreach ($hasil as $jenis => $items)
                    @if ($items->isNotEmpty())
                        <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-line/60 pb-2 mb-3">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-primary">
                                    Hasil Kategori: {{ strtoupper($jenis) }} ({{ $items->count() }})
                                </h2>
                            </div>
                            <div class="divide-y divide-line/50">
                                @foreach ($items as $it)
                                    <div class="py-2.5 flex items-center justify-between text-sm">
                                        @if ($jenis === 'lots')
                                            <div>
                                                <a href="/app/stok/{{ $it->id }}" class="font-mono font-bold text-primary hover:underline">
                                                    {{ $it->kode_lot }}
                                                </a>
                                                <p class="text-xs text-ink-soft">{{ $it->commodity->nama }} Grade {{ $it->grade?->kode }}</p>
                                            </div>
                                            <a href="/app/stok/{{ $it->id }}" class="text-xs font-semibold text-primary underline">Kartu Stok →</a>
                                        @elseif ($jenis === 'receipts')
                                            <div>
                                                <a href="/app/penerimaan/{{ $it->id }}" class="font-mono font-bold text-primary hover:underline">
                                                    {{ $it->no_dok }}
                                                </a>
                                                <p class="text-xs text-ink-soft">Petani: {{ $it->contract->partner->nama }} · Status: {{ $it->status }}</p>
                                            </div>
                                            <a href="/app/penerimaan/{{ $it->id }}" class="text-xs font-semibold text-primary underline">Lihat Detail →</a>
                                        @elseif ($jenis === 'shipments')
                                            <div>
                                                <span class="font-mono font-bold text-primary">{{ $it->no_dok }}</span>
                                                <p class="text-xs text-ink-soft">Status: {{ $it->status }} · Jumlah: {{ number_format((float) $it->jumlah_dikirim_kg, 1, ',', '.') }} kg</p>
                                            </div>
                                            <a href="/app/pengiriman" class="text-xs font-semibold text-primary underline">Buka Pengiriman →</a>
                                        @elseif ($jenis === 'invoices')
                                            <div>
                                                <a href="/app/invoice" class="font-mono font-bold text-primary hover:underline">
                                                    {{ $it->no }}
                                                </a>
                                                <p class="text-xs text-ink-soft">Pelanggan: {{ $it->partner->nama }} · Status: {{ $it->status() }}</p>
                                            </div>
                                            <a href="/app/invoice" class="text-xs font-semibold text-primary underline">Buka Invoice →</a>
                                        @else
                                            <div>
                                                <span class="font-bold text-ink">{{ $it->nama }}</span>
                                                <p class="text-xs text-ink-soft">Tipe: {{ $it->tipe }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                @if (collect($hasil)->every(fn ($c) => $c->isEmpty()))
                    <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                        <p class="text-base text-ink-soft">Tidak ditemukan data yang cocok dengan kata kunci "{{ $q }}".</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>

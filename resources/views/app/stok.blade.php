<x-layouts.app title="Stok Gudang — SiLeuit.id">
    <div class="mx-auto max-w-6xl px-4 py-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
                <h1 class="mt-1 font-display text-2xl font-bold text-primary">Stok Gabah &amp; Beras per Lot</h1>
                <p class="text-xs text-ink-soft">Pengawasan inventaris berbasis lot bertanggal simpan (FEFO)</p>
            </div>
            <div class="flex items-center gap-2">
                @can('buat-transfer')
                    <a href="/app/transfer" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] border border-line bg-card px-4 text-xs font-semibold text-primary shadow-xs hover:bg-cream">
                        <span>🔄</span>
                        <span>Transfer Antar-Gudang</span>
                    </a>
                @endcan
                @can('buat-penyesuaian')
                    <a href="/app/penyesuaian" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] border border-line bg-card px-4 text-xs font-semibold text-primary shadow-xs hover:bg-cream">
                        <span>⚖️</span>
                        <span>Penyesuaian Stok</span>
                    </a>
                @endcan
            </div>
        </div>

        @php
            $totalOnHand = (float) $saldo->sum('on_hand_kg');
            $totalReserved = (float) $saldo->sum('reserved_kg');
            $totalTersedia = max(0, $totalOnHand - $totalReserved);
            $lotKritis = $saldo->filter(fn($s) => $s->lot->persenUmur() >= 80)->count();
        @endphp

        <!-- KPI Mini Cards -->
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-[12px] border border-line bg-card p-4 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Total On-Hand</p>
                <p class="mt-1 font-mono text-xl font-bold text-primary">{{ number_format($totalOnHand, 1, ',', '.') }} <span class="text-xs font-normal text-ink-soft">kg</span></p>
            </div>
            <div class="rounded-[12px] border border-line bg-card p-4 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Stok Tersedia</p>
                <p class="mt-1 font-mono text-xl font-bold text-success">{{ number_format($totalTersedia, 1, ',', '.') }} <span class="text-xs font-normal text-ink-soft">kg</span></p>
            </div>
            <div class="rounded-[12px] border border-line bg-card p-4 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Dicadangkan (FEFO)</p>
                <p class="mt-1 font-mono text-xl font-bold text-warning">{{ number_format($totalReserved, 1, ',', '.') }} <span class="text-xs font-normal text-ink-soft">kg</span></p>
            </div>
            <div class="rounded-[12px] border border-line bg-card p-4 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Lot Umur &ge;80%</p>
                <p class="mt-1 font-mono text-xl font-bold {{ $lotKritis > 0 ? 'text-danger' : 'text-primary' }}">{{ $lotKritis }} <span class="text-xs font-normal text-ink-soft">lot</span></p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mt-6 rounded-[14px] border border-line bg-card p-4 shadow-xs">
            <form method="GET" action="/app/stok" class="flex gap-2">
                <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Cari kode lot (LOT-...) atau nama komoditas..."
                    class="h-11 flex-1 rounded-[10px] border border-line bg-paper px-3.5 text-sm text-ink focus:border-primary focus:outline-none">
                <button type="submit" class="h-11 rounded-[10px] bg-primary px-5 text-sm font-semibold text-white shadow-xs hover:bg-primary-dark">
                    Cari
                </button>
            </form>
        </div>

        <!-- Lot Stock Grid -->
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($saldo as $s)
                @php
                    $persen = $s->lot->persenUmur();
                    $barColor = match(true) {
                        $persen >= 100 || $s->lot->status === 'KEDALUWARSA' => 'bg-danger',
                        $persen >= 80 => 'bg-warning',
                        default => 'bg-success',
                    };
                @endphp
                <div class="group rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base font-bold text-primary">{{ $s->lot->kode_lot }}</span>
                            <span class="rounded-[6px] bg-cream px-2 py-0.5 text-xs font-semibold text-primary">
                                {{ $s->location->kode }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-sm font-bold text-ink">{{ $s->lot->commodity->nama }}</span>
                            @if ($s->lot->grade)
                                <span class="rounded-full bg-paper border border-line px-2 py-0.5 text-xs font-semibold text-primary">
                                    Grade {{ $s->lot->grade->kode }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-ink-soft">
                            Diterima: {{ $s->lot->tgl_terima->format('d M Y') }} · Batas: {{ $s->lot->batas_simpan->format('d M Y') }}
                        </p>

                        <!-- Storage Life Bar -->
                        <div class="mt-3">
                            <div class="flex justify-between text-[11px] text-ink-soft mb-1">
                                <span>Umur Simpan (FEFO)</span>
                                <span class="font-bold {{ $persen >= 80 ? 'text-warning' : 'text-ink' }}">{{ $persen }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-paper border border-line/60">
                                <div class="h-full {{ $barColor }}" style="width: {{ min(100, $persen) }}%"></div>
                            </div>
                        </div>

                        <!-- Quantities -->
                        <div class="mt-4 grid grid-cols-3 gap-2 rounded-[10px] border border-line/60 bg-paper p-2.5 text-center">
                            <div>
                                <p class="text-[10px] text-ink-soft uppercase">On-Hand</p>
                                <p class="font-mono text-xs font-bold text-ink">{{ number_format((float) $s->on_hand_kg, 1, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-ink-soft uppercase">Reserved</p>
                                <p class="font-mono text-xs font-bold text-warning">{{ number_format((float) $s->reserved_kg, 1, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-primary uppercase font-bold">Tersedia</p>
                                <p class="font-mono text-xs font-bold text-primary">{{ number_format($s->tersediaKg(), 1, ',', '.') }}</p>
                            </div>
                        </div>

                        <!-- Status Notes -->
                        @if ($s->lot->status === 'KEDALUWARSA' || $persen >= 100)
                            <p class="mt-3 rounded-[8px] bg-danger-bg px-2.5 py-1 text-xs font-semibold text-danger">
                                ⚠️ Diblokir dari alokasi normal (Kedaluwarsa)
                            </p>
                        @elseif ($persen >= 80)
                            <p class="mt-3 rounded-[8px] bg-warning-bg px-2.5 py-1 text-xs font-semibold text-warning">
                                ⏳ Prioritaskan pengeluaran (&ge;80% umur simpan)
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 border-t border-line/60 pt-3 flex justify-between items-center">
                        <span class="text-xs text-ink-soft">Status: <strong class="text-ink">{{ $s->lot->status }}</strong></span>
                        <a href="/app/stok/{{ $s->lot_id }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                            <span>Kartu Stok</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-[14px] border border-line bg-card p-10 text-center">
                    <p class="text-base text-ink-soft">Tidak ada saldo stok yang cocok dengan kriteria pencarian.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>

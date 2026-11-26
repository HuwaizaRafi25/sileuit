<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Pemenuhan Pesanan &amp; Pengiriman</h1>
            <p class="text-xs text-ink-soft">Proses picking gudang, pencetakan surat jalan, dan monitoring kurir</p>
        </div>
        <div class="flex items-center gap-2">
            @can('buat-pesanan')
                <a href="/app/pesanan" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] border border-line bg-card px-4 text-xs font-semibold text-primary shadow-xs hover:bg-cream">
                    <span>📑</span>
                    <span>Alokasi Pesanan FEFO</span>
                </a>
            @endcan
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('aksi')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    <!-- Two-Column Grid: Left Orders to Pick, Right Shipments in Transit -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Column 1: Pesanan Siap Diproses / Picking -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-base font-bold text-primary">Pesanan Siap Picking</h2>
                    <p class="text-xs text-ink-soft">Pesanan dengan alokasi lot terverifikasi</p>
                </div>
                <span class="rounded-full bg-cream px-2.5 py-0.5 text-xs font-bold text-primary">{{ $pesanan->count() }}</span>
            </div>

            <div class="space-y-3">
                @forelse ($pesanan as $o)
                    <div wire:key="fulfillment-order-{{ $o->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base font-bold text-primary">{{ $o->no_dok }}</span>
                            <span class="rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $o->status }}</span>
                        </div>

                        <p class="mt-1 text-xs text-ink-soft">
                            Mitra: <strong class="text-ink">{{ $o->partner?->nama ?? 'Penjualan Internal' }}</strong>
                        </p>

                        <div class="mt-3 space-y-1.5 rounded-[10px] border border-line/60 bg-paper p-3 text-xs">
                            @foreach ($o->lines as $l)
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-ink">{{ $l->commodity->nama }} Grade {{ $l->grade->kode }}</span>
                                    <span class="font-mono font-bold text-primary">{{ number_format((float) $l->qty_kg, 1, ',', '.') }} kg</span>
                                </div>
                            @endforeach
                        </div>

                        @if (in_array($o->status, ['ALLOCATED', 'PARTIAL'], true) && auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'ADMIN', 'PEMILIK'))
                            <div class="mt-4">
                                <button wire:click="pick({{ $o->id }})" type="button"
                                    class="h-11 w-full rounded-[10px] bg-primary text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                    📦 Picking &amp; Buat Surat Jalan
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-8 text-center text-xs text-ink-soft">
                        Tidak ada pesanan yang menunggu proses picking saat ini.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: Pengiriman Berjalan (Surat Jalan & Kurir) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-base font-bold text-primary">Pengiriman &amp; Surat Jalan</h2>
                    <p class="text-xs text-ink-soft">Monitoring armada kurir dan penerimaan barang</p>
                </div>
                <span class="rounded-full bg-cream px-2.5 py-0.5 text-xs font-bold text-primary">{{ $pengiriman->count() }}</span>
            </div>

            <div class="space-y-3">
                @forelse ($pengiriman as $s)
                    @php
                        $shipmentBadge = match($s->status) {
                            'MENUNGGU' => 'bg-cream text-ink-soft',
                            'DIKIRIM' => 'bg-warning-bg text-warning font-semibold',
                            'DITERIMA' => 'bg-success-bg text-success font-semibold',
                            default => 'bg-cream text-primary',
                        };
                    @endphp
                    <div wire:key="shipment-row-{{ $s->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base font-bold text-primary">{{ $s->no_dok }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs {{ $shipmentBadge }}">{{ $s->status }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-xs text-ink-soft">
                            <span>Pesanan: <strong class="text-ink font-mono">{{ $s->order->no_dok }}</strong></span>
                            <span>Muatan: <strong class="text-ink font-mono">{{ number_format((float) $s->jumlah_dikirim_kg, 1, ',', '.') }} kg</strong></span>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a href="/app/cetak/surat-jalan/{{ $s->id }}" target="_blank"
                                class="inline-flex h-9 items-center gap-1.5 rounded-[8px] border border-primary px-3 text-xs font-semibold text-primary hover:bg-cream">
                                <span>📄</span>
                                <span>Cetak Surat Jalan PDF</span>
                            </a>
                        </div>

                        <!-- Actions for Kurir / Delivery Confirmation -->
                        @if ($s->status === 'MENUNGGU')
                            <div class="mt-3">
                                <button wire:click="kirim({{ $s->id }})" type="button"
                                    class="h-10 w-full rounded-[10px] bg-primary text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                    🚚 Mulai Pengiriman Kurir
                                </button>
                            </div>
                        @elseif ($s->status === 'DIKIRIM')
                            <div class="mt-3 rounded-[10px] border border-line/60 bg-paper p-3">
                                <label class="block text-xs font-semibold text-ink-soft mb-1">Konfirmasi Kuantitas Diterima Pelanggan (kg):</label>
                                <div class="flex gap-2">
                                    <input wire:key="input-diterima-{{ $s->id }}" wire:model="diterima.{{ $s->id }}" type="number" step="0.001" placeholder="kg diterima"
                                        class="h-10 flex-1 rounded-[8px] border border-line bg-card px-3 text-xs font-mono focus:border-primary focus:outline-none">
                                    <button wire:click="terima({{ $s->id }})" type="button"
                                        class="h-10 rounded-[8px] bg-primary px-4 text-xs font-bold text-white shadow-xs hover:bg-primary-dark">
                                        ✓ Konfirmasi
                                    </button>
                                </div>
                            </div>
                        @elseif ($s->status === 'DITERIMA' && (float) $s->kekurangan_kg > 0)
                            <div class="mt-3 rounded-[10px] bg-warning-bg/60 p-3 text-xs text-warning border border-warning/40">
                                <p class="font-semibold">⚠️ Terdapat Selisih Pengiriman!</p>
                                <p class="mt-0.5">
                                    Diterima: <strong>{{ number_format((float) $s->jumlah_diterima_kg, 1, ',', '.') }} kg</strong> (Kurang: <strong>{{ number_format((float) $s->kekurangan_kg, 3, ',', '.') }} kg</strong>).
                                </p>
                                @can('buat-retur')
                                    <div class="mt-2">
                                        <a href="/app/retur" class="inline-flex items-center gap-1 font-bold text-primary underline">
                                            <span>Ajukan Retur Barang</span> →
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-8 text-center text-xs text-ink-soft">
                        Belum ada dokumen pengiriman aktif.
                    </div>
                @endforelse
            </div>

            @if ($transferKurir->isNotEmpty())
                <div class="pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="font-display text-base font-bold text-primary">Tugas Transfer Antar-Gudang</h2>
                            <p class="text-xs text-ink-soft">Pengantaran lot antar-gudang yang ditugaskan ke kurir</p>
                        </div>
                        @can('lihat-transfer')
                            <a href="/app/transfer" class="text-xs font-semibold text-primary underline">Lihat Semua →</a>
                        @endcan
                    </div>
                    <div class="space-y-2.5">
                        @foreach ($transferKurir as $trf)
                            <div wire:key="fulfillment-trf-{{ $trf->id }}" class="rounded-[12px] border border-line bg-card p-4 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-sm font-bold text-primary">{{ $trf->no_dok }}</span>
                                    <span class="rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $trf->status }}</span>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between text-xs text-ink-soft">
                                    <span>Rute: <strong class="text-ink">{{ $trf->asal->kode }} → {{ $trf->tujuan->kode }}</strong></span>
                                    <span>Kurir: <strong class="text-ink">{{ $trf->kurir?->name }}</strong></span>
                                </div>
                                <div class="mt-2 space-y-1 text-xs">
                                    @foreach ($trf->lines as $tl)
                                        <div class="flex justify-between font-mono text-ink">
                                            <span>{{ $tl->lot->kode_lot }} ({{ $tl->lot->commodity->nama }})</span>
                                            <span>{{ number_format((float) $tl->kg_kirim, 1, ',', '.') }} kg</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

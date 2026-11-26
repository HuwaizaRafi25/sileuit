<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Kasir Penjualan Langsung (POS)</h1>
            <p class="text-xs text-ink-soft">Lokasi Kasir: <strong class="text-ink">{{ auth()->user()->activeLocation?->nama }} ({{ auth()->user()->activeLocation?->kode }})</strong></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-success-bg px-3 py-1.5 text-xs font-semibold text-success">
                ● Kasir Siap Melayani
            </span>
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('checkout')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    <!-- Two-Column Grid: Left Catalog & Input, Right Digital Nota -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left: Input & Stok Tersedia (2 cols) -->
        <div class="space-y-5 lg:col-span-2">
            <!-- Form Input Produk -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-base font-bold text-primary">Pilih Produk &amp; Timbang</h2>
                <p class="text-xs text-ink-soft">Pilih jenis beras/gabah dan tetapkan harga jual</p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Komoditas <span class="text-danger">*</span></label>
                        <select wire:model.live="commodity_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Komoditas —</option>
                            @foreach ($komoditas as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Grade Mutu <span class="text-danger">*</span></label>
                        <select wire:model="grade_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Grade —</option>
                            @foreach ($grades->where('commodity_id', (int) $commodity_id) as $g)
                                <option value="{{ $g->id }}">Grade {{ $g->kode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Kuantitas Timbang (kg) <span class="text-danger">*</span></label>
                        <input wire:model="qty" type="number" step="0.001" placeholder="Contoh: 25"
                            class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                        @error('qty') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Harga Satuan (Rp/kg) <span class="text-danger">*</span></label>
                        <input wire:model="harga" type="number" step="1" placeholder="Contoh: 14000"
                            class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                        @error('harga') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button wire:click="tambah" type="button"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] bg-primary px-6 text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                        <span>+</span>
                        <span>Tambah ke Nota Penjualan</span>
                    </button>
                </div>
            </div>

            <!-- Stok Tersedia di Cabang Ini -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-sm font-bold text-primary">Stok On-Hand di Cabang Ini</h3>
                    <span class="text-xs text-ink-soft">Alokasi otomatis saat checkout</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($stok as $s)
                        <div class="rounded-[8px] border border-line/70 bg-paper px-3 py-1.5 text-xs">
                            <span class="font-mono font-bold text-primary">{{ $s->lot->kode_lot }}</span>
                            <span class="text-ink-soft">({{ $s->lot->commodity->nama }} {{ $s->lot->grade?->kode }}):</span>
                            <strong class="text-ink font-mono">{{ number_format($s->tersediaKg(), 1, ',', '.') }} kg</strong>
                        </div>
                    @empty
                        <p class="text-xs text-ink-soft">Belum ada stok fisik di cabang ini. Buat transfer dari gudang pusat.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Digital Receipt / Nota Bill (1 col) -->
        <div class="space-y-4">
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-line/60">
                    <h2 class="font-display text-base font-bold text-primary">Nota Penjualan</h2>
                    <span class="font-mono text-xs text-ink-soft">{{ date('d/m/Y') }}</span>
                </div>

                <!-- Lines List -->
                <div class="mt-3 divide-y divide-line/60 min-h-36">
                    @php
                        $subtotal = 0;
                        $totalKg = 0;
                    @endphp
                    @forelse ($lines as $i => $l)
                        @php
                            $lineTotal = (float) $l['qty_kg'] * (int) $l['harga_per_kg'];
                            $subtotal += $lineTotal;
                            $totalKg += (float) $l['qty_kg'];
                        @endphp
                        <div wire:key="pos-line-{{ $i }}" class="py-2.5 flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-ink">{{ $l['nama'] }} Grade {{ $l['grade'] }}</p>
                                <p class="text-[11px] font-mono text-ink-soft">
                                    {{ number_format($l['qty_kg'], 1, ',', '.') }} kg × Rp{{ number_format($l['harga_per_kg'], 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-primary">Rp{{ number_format($lineTotal, 0, ',', '.') }}</span>
                                <button wire:click="hapus({{ $i }})" type="button" class="text-xs text-danger hover:underline">
                                    ✕
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-ink-soft">
                            Nota masih kosong.<br>Pilih produk di sebelah kiri untuk memulai.
                        </div>
                    @endforelse
                </div>

                <!-- Totals & Payment Method -->
                <div class="mt-4 border-t border-line/60 pt-3 space-y-2 text-xs">
                    <div class="flex justify-between text-ink-soft">
                        <span>Total Kuantitas</span>
                        <span class="font-mono font-semibold text-ink">{{ number_format($totalKg, 1, ',', '.') }} kg</span>
                    </div>
                    <div class="flex justify-between items-baseline pt-1 border-t border-line/40">
                        <span class="text-sm font-bold text-ink">Total Tagihan</span>
                        <span class="font-mono text-xl font-bold text-primary">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Metode Bayar -->
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Metode Bayar</label>
                    <select wire:model="metode" class="h-10 w-full rounded-[10px] border border-line bg-paper px-3 text-xs focus:border-primary focus:outline-none font-semibold">
                        <option value="TUNAI">💵 Tunai (Cash)</option>
                        <option value="TRANSFER">💳 Transfer Bank / QRIS</option>
                    </select>
                </div>

                <!-- Checkout Button -->
                <button wire:click="checkout" wire:loading.attr="disabled" type="button"
                    class="mt-4 h-12 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition disabled:opacity-50">
                    Selesaikan &amp; Cetak Nota Kasir
                </button>
            </div>
        </div>
    </div>
</div>

<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Pesanan Penjualan &amp; Alokasi FEFO</h1>
            <p class="text-xs text-ink-soft">Alokasi otomatis First-Expired, First-Out (FEFO) dari lot terdaftar</p>
        </div>
        <div class="flex items-center gap-2">
            @can('lihat-fulfillment')
                <a href="/app/pengiriman" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] border border-line bg-card px-4 text-xs font-semibold text-primary shadow-xs hover:bg-cream">
                    <span>🚚</span>
                    <span>Fulfillment &amp; Pengiriman</span>
                </a>
            @endcan
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('alokasi')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    <!-- Two-Column Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left: Form Buat Pesanan (1 col) -->
        <div class="space-y-4">
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-base font-bold text-primary">Buat Pesanan Baru</h2>
                <p class="text-xs text-ink-soft">Pesanan retail atau grosir B2B</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Mitra / Pelanggan</label>
                        <select wire:model.live="partner_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Internal Cabang / Pelanggan Umum —</option>
                            @foreach ($mitra as $m)
                                <option value="{{ $m->id }}">{{ $m->nama }} ({{ $m->tipe }})</option>
                            @endforeach
                        </select>
                        @error('partner_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Gudang Asal Pemenuhan <span class="text-danger">*</span></label>
                        <select wire:model="location_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            @foreach ($gudangList as $g)
                                <option value="{{ $g->id }}">{{ $g->kode }} — {{ $g->nama }}</option>
                            @endforeach
                        </select>
                        @error('location_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    @if (! $partner_id)
                        <div wire:key="order-cabang-tujuan">
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Cabang Tujuan Pengiriman (Opsional)</label>
                            <select wire:model="lokasi_tujuan_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="">— Tidak ke Cabang (Walk-in/Umum) —</option>
                                @foreach ($cabangList as $c)
                                    <option value="{{ $c->id }}">{{ $c->kode }} — {{ $c->nama }}</option>
                                @endforeach
                            </select>
                            @error('lokasi_tujuan_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Komoditas <span class="text-danger">*</span></label>
                        <select wire:model.live="commodity_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Komoditas —</option>
                            @foreach ($komoditas as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                        @error('commodity_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Grade Mutu <span class="text-danger">*</span></label>
                        <select wire:model="grade_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Grade —</option>
                            @foreach ($grades->where('commodity_id', (int) $commodity_id) as $g)
                                <option value="{{ $g->id }}">{{ $g->kode }}</option>
                            @endforeach
                        </select>
                        @error('grade_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Jumlah Pesanan (kg) <span class="text-danger">*</span></label>
                        <input wire:model="qty" type="number" step="0.001" placeholder="Contoh: 1000"
                            class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                        @error('qty') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <button wire:click="buat" type="button"
                        class="h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                        Buat Pesanan
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Daftar Pesanan (2 cols) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold text-primary">Daftar Pesanan &amp; Status Alokasi</h2>
                <span class="text-xs text-ink-soft">Total: {{ $daftar->count() }} pesanan</span>
            </div>

            <div class="space-y-3">
                @forelse ($daftar as $o)
                    @php
                        $orderBadge = match($o->status) {
                            'NEW' => 'bg-cream text-ink-soft',
                            'ALLOCATED' => 'bg-warning-bg text-warning font-semibold',
                            'PICKED' => 'bg-paper text-primary font-bold border border-primary/30',
                            'SHIPPED' => 'bg-paper text-primary font-bold border border-primary/30',
                            'DELIVERED' => 'bg-success-bg text-success font-semibold',
                            'PARTIAL' => 'bg-warning-bg text-warning font-semibold',
                            default => 'bg-cream text-primary',
                        };
                    @endphp
                    <div wire:key="order-card-{{ $o->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-base font-bold text-primary">{{ $o->no_dok }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs {{ $orderBadge }}">{{ $o->status }}</span>
                            </div>
                            @if ($o->partner)
                                <span class="text-xs font-semibold text-ink">Mitra: {{ $o->partner->nama }}</span>
                            @endif
                        </div>

                        <!-- Lines & Reservations -->
                        <div class="mt-3 divide-y divide-line/60 rounded-[10px] border border-line/60 bg-paper p-3 text-xs">
                            @foreach ($o->lines as $line)
                                <div wire:key="order-line-{{ $line->id }}" class="py-1.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-ink">{{ $line->commodity->nama }} Grade {{ $line->grade->kode }}</span>
                                        <span class="font-mono font-bold text-ink">
                                            {{ number_format((float) $line->qty_kg, 1, ',', '.') }} kg
                                            <span class="text-ink-soft font-normal">(terpenuhi: {{ number_format((float) $line->qty_terpenuhi, 1, ',', '.') }} kg)</span>
                                        </span>
                                    </div>

                                    @if ($line->reservations->isNotEmpty())
                                        <div class="mt-2 space-y-1 pl-3 border-l-2 border-primary/40">
                                            @foreach ($line->reservations as $r)
                                                <div wire:key="res-{{ $r->id }}" class="flex items-center justify-between text-[11px] text-ink-soft">
                                                    <span>↳ Lot: <strong class="font-mono text-primary">{{ $r->lot->kode_lot }}</strong> {{ $r->override_kedaluwarsa ? '(⚠️ override)' : '' }}</span>
                                                    <span class="font-mono font-semibold">{{ number_format((float) $r->qty_kg, 1, ',', '.') }} kg</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Action Buttons -->
                        @if (in_array($o->status, ['NEW','PARTIAL','BACKORDER']) && auth()->user()->can('alokasi-pesanan'))
                            <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                <button wire:click="alokasi({{ $o->id }}, false)" type="button"
                                    class="h-10 rounded-[10px] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                    ⚡ Alokasi Otomatis (FEFO)
                                </button>
                                @if (auth()->user()->hasRole('KEPALA_GUDANG'))
                                    <button wire:click="alokasi({{ $o->id }}, true)" type="button"
                                        class="h-10 rounded-[10px] border border-warning px-4 text-xs font-semibold text-warning hover:bg-warning-bg transition">
                                        Setujui Lot Kedaluwarsa
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                        <p class="text-base text-ink-soft">Belum ada dokumen pesanan penjualan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

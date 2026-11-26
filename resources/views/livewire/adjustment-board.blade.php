<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Penyesuaian Stok (Stock Opname)</h1>
            <p class="text-xs text-ink-soft">Koreksi fisik inventaris gudang &amp; cutover saldo awal berbasis persetujuan</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-cream px-3 py-1.5 text-xs font-semibold text-primary">
                Gudang: {{ auth()->user()->activeLocation?->nama ?? 'Semua Lokasi' }}
            </span>
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('proses')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    @php
        $canBuatAdj = auth()->user()->can('buat-penyesuaian');
    @endphp

    <!-- Two-Column Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 {{ $canBuatAdj ? 'lg:grid-cols-3' : '' }}">
        @if ($canBuatAdj)
            <!-- Left: Form Pengajuan (1 col) -->
            <div class="space-y-4">
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <h2 class="font-display text-base font-bold text-primary">Ajukan Penyesuaian Baru</h2>
                    <p class="text-xs text-ink-soft">Memerlukan persetujuan Kepala Gudang / Pemilik</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Jenis Penyesuaian</label>
                            <select wire:model.live="tipe" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="PENYESUAIAN">Penyesuaian Fisik (Opname / Susut)</option>
                                <option value="SALDO_AWAL">Saldo Awal Cutover Sistem</option>
                            </select>
                        </div>

                        @if ($tipe === 'PENYESUAIAN')
                            <div wire:key="adj-input-lot">
                                <label class="block text-xs font-semibold text-ink-soft mb-1">Pilih Lot Stok <span class="text-danger">*</span></label>
                                <select wire:model="lot_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                    <option value="">— Pilih Lot —</option>
                                    @foreach ($stok as $s)
                                        <option value="{{ $s->lot_id }}">{{ $s->lot->kode_lot }} ({{ $s->lot->commodity->nama }} {{ $s->lot->grade?->kode }}) · Sisa {{ number_format($s->tersediaKg(), 1, ',', '.') }} kg</option>
                                    @endforeach
                                </select>
                                @error('lot_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div wire:key="adj-input-komoditas" class="space-y-3">
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
                                            <option value="{{ $g->id }}">{{ $g->kode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('commodity_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Kuantitas Penyesuaian (kg) <span class="text-danger">*</span></label>
                            <input wire:model="qty" type="number" step="0.001" placeholder="Gunakan tanda minus (-) untuk pengurangan"
                                class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                            <p class="mt-1 text-[11px] text-ink-soft">Contoh: <strong>-5.5</strong> untuk susut 5,5 kg, atau <strong>10</strong> untuk temuan lebih.</p>
                            @error('qty') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Alasan Penyesuaian <span class="text-danger">*</span></label>
                            <textarea wire:model="alasan" rows="3" placeholder="Tuliskan justifikasi opname secara rinci..."
                                class="w-full rounded-[10px] border border-line bg-paper px-3 py-2 text-sm focus:border-primary focus:outline-none"></textarea>
                            @error('alasan') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <button wire:click="buat" type="button"
                            class="h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                            Ajukan Penyesuaian
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Right: Daftar Penyesuaian (2 cols) -->
        <div class="space-y-4 {{ $canBuatAdj ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold text-primary">Riwayat &amp; Status Penyesuaian</h2>
                <span class="text-xs text-ink-soft">Total: {{ $daftar->count() }} dokumen</span>
            </div>

            <div class="space-y-3">
                @forelse ($daftar as $a)
                    @php
                        $statusBadge = match($a->status) {
                            'MENUNGGU' => 'bg-warning-bg text-warning font-semibold',
                            'DISETUJUI' => 'bg-success-bg text-success font-semibold',
                            'DITOLAK' => 'bg-danger-bg text-danger font-semibold',
                            default => 'bg-cream text-primary',
                        };
                    @endphp
                    <div wire:key="adj-card-{{ $a->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-base font-bold text-primary">{{ $a->no_dok }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs {{ $statusBadge }}">{{ $a->status }}</span>
                                <span class="rounded-[6px] bg-paper border border-line px-2 py-0.5 text-xs text-ink-soft">{{ $a->tipe }}</span>
                            </div>
                            <div class="font-mono text-sm font-bold {{ (float)$a->qty_kg < 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float)$a->qty_kg > 0 ? '+' : '' }}{{ number_format((float) $a->qty_kg, 3, ',', '.') }} kg
                            </div>
                        </div>

                        <div class="mt-2 text-xs text-ink-soft">
                            @if ($a->lot)
                                Lot: <strong class="text-ink font-mono">{{ $a->lot->kode_lot }}</strong> ({{ $a->lot->commodity->nama }})
                            @else
                                Komoditas: <strong class="text-ink">{{ $a->commodity?->nama }} {{ $a->grade?->kode }}</strong>
                            @endif
                        </div>

                        <div class="mt-2 rounded-[8px] bg-paper p-3 text-xs text-ink border border-line/60">
                            <strong>Alasan:</strong> {{ $a->alasan }}
                        </div>

                        <!-- Approval Actions -->
                        @if ($a->status === 'MENUNGGU')
                            @php
                                $boleh = $a->tipe === 'SALDO_AWAL'
                                    ? auth()->user()->hasRole('PEMILIK')
                                    : (auth()->user()->hasRole('KEPALA_GUDANG') && auth()->user()->locationScopes->contains('id', $a->location_id));
                            @endphp
                            @if ($boleh)
                                <div class="mt-4 flex gap-2 justify-end border-t border-line/60 pt-3">
                                    <button wire:click="proses({{ $a->id }}, false)" type="button"
                                        class="h-9 rounded-[8px] border border-danger px-4 text-xs font-semibold text-danger hover:bg-danger-bg">
                                        ✕ Tolak Penyesuaian
                                    </button>
                                    <button wire:click="proses({{ $a->id }}, true)" type="button"
                                        class="h-9 rounded-[8px] bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-dark">
                                        ✓ Setujui Penyesuaian
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                        <p class="text-base text-ink-soft">Belum ada dokumen penyesuaian stok.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

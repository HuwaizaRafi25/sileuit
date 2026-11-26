<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Transfer Antar-Gudang</h1>
            <p class="text-xs text-ink-soft">Pemindahan lot antar-lokasi gudang dengan penugasan kurir dan verifikasi kuantitas penerimaan</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-cream px-3 py-1.5 text-xs font-semibold text-primary">
                Lokasi Kerja: {{ auth()->user()->activeLocation?->nama ?? 'Semua Lokasi' }}
                @if (auth()->user()->activeLocation?->kode)
                    ({{ auth()->user()->activeLocation->kode }})
                @endif
            </span>
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('buat')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror
    @error('kirim')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror
    @error('terima')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    @php
        $canBuatTransfer = auth()->user()->can('buat-transfer');
        $totalRencanaKg = collect($items)->sum(fn ($r) => (float) str_replace(',', '.', (string) ($r['kg'] ?? 0)));
    @endphp

    <!-- Grid: Form Buat Transfer (for Warehouse Staff) + Daftar Transfer -->
    <div class="mt-6 grid grid-cols-1 gap-6 {{ $canBuatTransfer ? 'lg:grid-cols-3' : '' }}">
        @if ($canBuatTransfer)
            <div class="space-y-4">
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <h2 class="font-display text-base font-bold text-primary">Buat Pengiriman Baru</h2>
                    <p class="text-xs text-ink-soft">Dari {{ auth()->user()->activeLocation?->kode }} ke gudang lain (bisa gabung beberapa lot)</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Gudang Tujuan <span class="text-danger">*</span></label>
                            <select wire:model="ke_lokasi" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="">— Pilih Gudang Tujuan —</option>
                                @foreach ($lokasi as $l)
                                    <option value="{{ $l->id }}">{{ $l->kode }} — {{ $l->nama }}</option>
                                @endforeach
                            </select>
                            @error('ke_lokasi') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-semibold text-ink-soft">Rincian Lot Sumber <span class="text-danger">*</span></label>
                                <button wire:click="tambahBaris" type="button"
                                    class="text-xs font-bold text-primary hover:underline">
                                    + Tambah Lot Lain
                                </button>
                            </div>

                            @foreach ($items as $idx => $item)
                                <div wire:key="trf-form-row-{{ $idx }}" class="rounded-[10px] border border-line/80 bg-paper p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-ink-soft">Lot #{{ $idx + 1 }}</span>
                                        @if (count($items) > 1)
                                            <button wire:click="hapusBaris({{ $idx }})" type="button"
                                                class="text-[11px] font-semibold text-danger hover:underline">
                                                Hapus
                                            </button>
                                        @endif
                                    </div>
                                    <select wire:model="items.{{ $idx }}.lot_id" class="h-10 w-full rounded-[8px] border border-line bg-card px-2.5 text-xs focus:border-primary focus:outline-none">
                                        <option value="">— Pilih Lot Tersedia —</option>
                                        @foreach ($stok as $s)
                                            <option value="{{ $s->lot_id }}">{{ $s->lot->kode_lot }} ({{ $s->lot->commodity->nama }} {{ $s->lot->grade?->kode }}) · Sisa {{ number_format($s->tersediaKg(), 1, ',', '.') }} kg</option>
                                        @endforeach
                                    </select>
                                    @error("items.{$idx}.lot_id") <p class="text-[11px] text-danger font-medium">{{ $message }}</p> @enderror

                                    <input wire:model.live="items.{{ $idx }}.kg" type="number" step="0.001" placeholder="Jumlah transfer (kg)"
                                        class="h-10 w-full rounded-[8px] border border-line bg-card px-2.5 text-xs font-mono focus:border-primary focus:outline-none">
                                    @error("items.{$idx}.kg") <p class="text-[11px] text-danger font-medium">{{ $message }}</p> @enderror
                                </div>
                            @endforeach

                            <div class="flex items-center justify-between rounded-[8px] bg-cream/70 px-3 py-1.5 text-xs">
                                <span class="text-ink-soft">Total Rencana Kirim:</span>
                                <strong class="font-mono text-primary">{{ number_format($totalRencanaKg, 1, ',', '.') }} kg</strong>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Kurir Pengantar</label>
                            <select wire:model="kurir_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="">— Pilih Kurir (Opsional) —</option>
                                @foreach ($kurirList as $kurir)
                                    <option value="{{ $kurir->id }}">{{ $kurir->name }} ({{ $kurir->activeLocation?->kode ?? 'Armada' }})</option>
                                @endforeach
                            </select>
                            @error('kurir_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <button wire:click="buat" type="button"
                            class="mt-2 h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                            Buat Dokumen Transfer
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Right Column: Daftar Transfer -->
        <div class="space-y-4 {{ $canBuatTransfer ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold text-primary">Daftar Transfer Antar-Gudang</h2>
                <span class="text-xs text-ink-soft">Total: {{ $daftar->count() }} transfer</span>
            </div>

            <div class="space-y-3">
                @forelse ($daftar as $t)
                    @php
                        $statusBadge = match($t->status) {
                            'DRAFT' => 'bg-cream text-ink-soft',
                            'SHIPPED' => 'bg-warning-bg text-warning font-semibold',
                            'RECEIVED' => 'bg-success-bg text-success font-semibold',
                            'DISCREPANCY' => 'bg-danger-bg text-danger font-semibold',
                            default => 'bg-cream text-primary',
                        };
                    @endphp
                    <div wire:key="trf-card-{{ $t->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="font-mono text-base font-bold text-primary">{{ $t->no_dok }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs {{ $statusBadge }}">{{ $t->status }}</span>
                                @if ($t->kurir)
                                    <span class="rounded-full border border-line bg-paper px-2.5 py-0.5 text-xs font-medium text-ink">
                                        Kurir: {{ $t->kurir->name }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 font-mono text-xs font-semibold text-ink">
                                <span class="rounded-[6px] bg-paper border border-line px-2 py-0.5">{{ $t->asal->kode }}</span>
                                <span>→</span>
                                <span class="rounded-[6px] bg-paper border border-line px-2 py-0.5">{{ $t->tujuan->kode }}</span>
                            </div>
                        </div>

                        <!-- Transfer Lines -->
                        <div class="mt-3 divide-y divide-line/60 rounded-[10px] border border-line/60 bg-paper p-3">
                            @foreach ($t->lines as $line)
                                <div wire:key="trf-line-{{ $line->id }}" class="py-1.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-mono font-bold text-primary">{{ $line->lot->kode_lot }} ({{ $line->lot->commodity->nama }})</span>
                                        <span class="text-ink">Kirim: <strong>{{ number_format((float) $line->kg_kirim, 1, ',', '.') }} kg</strong></span>
                                    </div>

                                    @if ($line->kg_terima !== null)
                                        <div class="mt-1 flex items-center justify-between text-xs">
                                            <span class="text-ink-soft">Diterima di tujuan:</span>
                                            <span class="font-mono font-bold text-success">{{ number_format((float) $line->kg_terima, 1, ',', '.') }} kg</span>
                                        </div>
                                        @if ((float) $line->selisih_kg !== 0.0)
                                            <div class="mt-1 flex items-center justify-between text-xs rounded-[6px] bg-danger-bg/60 px-2 py-1 text-danger font-semibold">
                                                <span>⚠️ Selisih Pengiriman:</span>
                                                <span class="font-mono">{{ number_format((float) $line->selisih_kg, 3, ',', '.') }} kg</span>
                                            </div>
                                        @endif
                                    @endif

                                    <!-- Input Qty Terima if SHIPPED & Receiver is active -->
                                    @if ($t->status === 'SHIPPED' && auth()->user()->can('terima-transfer') && auth()->user()->locationScopes->contains('id', $t->ke_lokasi))
                                        <div class="mt-2.5 flex items-center gap-2">
                                            <label class="text-xs font-medium text-ink-soft whitespace-nowrap">Timbang Diterima (kg):</label>
                                            <input wire:key="input-terima-line-{{ $line->id }}" wire:model="terima.{{ $line->id }}" type="number" step="0.001" placeholder="kg diterima"
                                                class="h-9 flex-1 rounded-[8px] border border-line bg-card px-2.5 text-xs font-mono focus:border-primary focus:outline-none">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if (filled($t->catatan))
                            <div class="mt-2.5 rounded-[8px] border border-line/60 bg-paper px-3 py-2 text-xs text-ink-soft">
                                <strong class="text-ink">Catatan Penerimaan:</strong> {{ $t->catatan }}
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        @if ($t->status === 'DRAFT' && $canBuatTransfer && (auth()->user()->locationScopes->contains('id', $t->dari_lokasi) || auth()->user()->isGlobal()))
                            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                <select wire:model="kurirDraft.{{ $t->id }}" class="h-10 rounded-[10px] border border-line bg-paper px-3 text-xs focus:border-primary focus:outline-none">
                                    <option value="">{{ $t->kurir ? 'Kurir: '.$t->kurir->name : '— Pilih Kurir (Opsional) —' }}</option>
                                    @foreach ($kurirList as $kurir)
                                        <option value="{{ $kurir->id }}">{{ $kurir->name }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="kirim({{ $t->id }})" type="button"
                                    class="h-10 rounded-[10px] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                    Kirim Barang Sekarang
                                </button>
                            </div>
                        @elseif ($t->status === 'SHIPPED' && auth()->user()->can('terima-transfer') && auth()->user()->locationScopes->contains('id', $t->ke_lokasi))
                            <div class="mt-3 space-y-2.5">
                                <div>
                                    <label class="block text-[11px] font-semibold text-ink-soft mb-1">Catatan Penerimaan / Keterangan Selisih (opsional)</label>
                                    <input wire:key="input-catatan-trf-{{ $t->id }}" wire:model="catatan.{{ $t->id }}" type="text"
                                        placeholder="Contoh: Susut perjalanan 10 kg, karung sedikit robek"
                                        class="h-9 w-full rounded-[8px] border border-line bg-paper px-3 text-xs focus:border-primary focus:outline-none">
                                </div>
                                <div class="flex justify-end">
                                    <button wire:click="terimaBarang({{ $t->id }})" type="button"
                                        class="h-10 rounded-[10px] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                        ✓ Catat Penerimaan Transfer
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                        <p class="text-base text-ink-soft">Belum ada dokumen transfer antar-gudang.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

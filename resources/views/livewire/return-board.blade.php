<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Manajemen Retur Barang</h1>
            <p class="text-xs text-ink-soft">Pengembalian barang dari pengiriman pelanggan atau transaksi kasir</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-cream px-3 py-1.5 text-xs font-semibold text-primary">
                Kebijakan: Inspeksi &amp; Karantina Mutu
            </span>
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('retur')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    @php
        $canBuatRetur = auth()->user()->can('buat-retur');
    @endphp

    <!-- Two-Column Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 {{ $canBuatRetur ? 'lg:grid-cols-3' : '' }}">
        @if ($canBuatRetur)
            <!-- Left: Form Ajukan Retur (1 col) -->
            <div class="space-y-4">
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <h2 class="font-display text-base font-bold text-primary">Ajukan Retur Baru</h2>
                    <p class="text-xs text-ink-soft">Dari dokumen surat jalan atau nota kasir</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Tipe Dokumen Asal</label>
                            <select wire:model.live="ref_tipe" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="SHIPMENT">Pengiriman (Surat Jalan)</option>
                                <option value="SALE">Nota Penjualan Kasir</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Pilih Dokumen Asal <span class="text-danger">*</span></label>
                            <select wire:key="select-ref-id-{{ $ref_tipe }}" wire:model="ref_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="">— Pilih Dokumen —</option>
                                @if ($ref_tipe === 'SHIPMENT')
                                    @foreach ($pengiriman as $s)
                                        <option value="{{ $s->id }}">{{ $s->no_dok }} ({{ number_format((float) $s->jumlah_dikirim_kg, 1, ',', '.') }} kg)</option>
                                    @endforeach
                                @else
                                    @foreach ($nota as $n)
                                        <option value="{{ $n->id }}">{{ $n->no_nota }} (Rp{{ number_format((int) $n->total, 0, ',', '.') }})</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('ref_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Kuantitas Retur (kg) <span class="text-danger">*</span></label>
                            <input wire:model="kg" type="number" step="0.001" placeholder="Contoh: 15"
                                class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                            @error('kg') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Alasan Retur <span class="text-danger">*</span></label>
                            <textarea wire:model="alasan" rows="3" placeholder="Contoh: Kemasan robek saat pengiriman..."
                                class="w-full rounded-[10px] border border-line bg-paper px-3 py-2 text-sm focus:border-primary focus:outline-none"></textarea>
                            @error('alasan') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>

                        <button wire:click="ajukan" type="button"
                            class="h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                            Ajukan Permintaan Retur
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Right: Daftar Dokumen Retur (2 cols) -->
        <div class="space-y-4 {{ $canBuatRetur ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold text-primary">Daftar Pengajuan Retur</h2>
                <span class="text-xs text-ink-soft">Total: {{ $daftar->count() }} dokumen</span>
            </div>

            <div class="space-y-3">
                @forelse ($daftar as $r)
                    @php
                        $returnBadge = match($r->status) {
                            'REQUESTED' => 'bg-warning-bg text-warning font-semibold',
                            'APPROVED' => 'bg-paper text-primary font-bold border border-primary/40',
                            'REJECTED' => 'bg-danger-bg text-danger font-semibold',
                            'RECEIVED' => 'bg-success-bg text-success font-semibold',
                            default => 'bg-cream text-primary',
                        };
                    @endphp
                    <div wire:key="return-card-{{ $r->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-base font-bold text-primary">{{ $r->no_dok }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs {{ $returnBadge }}">{{ $r->status }}</span>
                                @if ($r->hasil)
                                    <span class="rounded-[6px] bg-cream px-2 py-0.5 text-xs font-semibold text-primary">{{ $r->hasil }}</span>
                                @endif
                            </div>
                            <div class="font-mono text-sm font-bold text-primary">
                                {{ number_format((float) $r->kg, 1, ',', '.') }} kg
                            </div>
                        </div>

                        <div class="mt-2 text-xs text-ink-soft">
                            Dokumen Asal: <strong class="text-ink font-mono">{{ $r->ref_tipe }}#{{ $r->ref_id }}</strong>
                        </div>

                        <div class="mt-2 rounded-[8px] bg-paper p-3 text-xs text-ink border border-line/60">
                            <strong>Alasan:</strong> {{ $r->alasan }}
                        </div>

                        <!-- Actions for Supervisor Approval -->
                        @if ($r->status === 'REQUESTED' && auth()->user()->can('retur-approve'))
                            <div class="mt-4 flex gap-2 justify-end border-t border-line/60 pt-3">
                                <button wire:click="proses({{ $r->id }}, false)" type="button"
                                    class="h-9 rounded-[8px] border border-danger px-4 text-xs font-semibold text-danger hover:bg-danger-bg">
                                    ✕ Tolak Retur
                                </button>
                                <button wire:click="proses({{ $r->id }}, true)" type="button"
                                    class="h-9 rounded-[8px] bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-dark">
                                    ✓ Setujui Retur
                                </button>
                            </div>
                        @elseif ($r->status === 'APPROVED' && auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'ADMIN'))
                            <!-- Action for Warehouse Receiving of Returned Goods -->
                            <div class="mt-4 border-t border-line/60 pt-3">
                                <p class="text-xs font-semibold text-ink-soft mb-2">Pilih Penanganan Fisik Barang di Gudang:</p>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <select wire:key="select-hasil-ret-{{ $r->id }}" wire:model="hasil.{{ $r->id }}" class="h-10 flex-1 rounded-[8px] border border-line bg-paper px-3 text-xs focus:border-primary focus:outline-none">
                                        <option value="">— Pilih Penanganan (Default: Kembali ke Stok Normal) —</option>
                                        <option value="STOK_KEMBALI">Kembali ke Stok Normal</option>
                                        <option value="KARANTINA">Masuk Lot Karantina</option>
                                        <option value="DIHAPUS">Dihapus / Afkir</option>
                                    </select>
                                    <button wire:click="terimaBarang({{ $r->id }})" type="button"
                                        class="h-10 rounded-[8px] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:bg-primary-dark transition">
                                        Terima Fisik Barang
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                        <p class="text-base text-ink-soft">Belum ada pengajuan retur barang.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

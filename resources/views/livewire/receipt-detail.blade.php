<div class="mx-auto max-w-5xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ auth()->user()->hasRole('PETANI') ? '/portal' : '/app/penerimaan' }}" class="text-xs font-semibold text-primary hover:underline">
                {{ auth()->user()->hasRole('PETANI') ? '← Kembali ke Portal Petani' : '← Kembali ke Daftar Penerimaan' }}
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="font-mono text-2xl font-bold text-primary">{{ $receipt->no_dok }}</h1>
                @php
                    $badgeStyle = match($receipt->status) {
                        'CONFIRMED' => 'bg-success-bg text-success',
                        'PAID' => 'bg-primary/10 text-primary font-bold',
                        'MENUNGGU_PERSETUJUAN' => 'bg-warning-bg text-warning font-semibold',
                        'REJECTED' => 'bg-danger-bg text-danger font-semibold',
                        'DRAFT' => 'bg-cream text-ink-soft',
                        default => 'bg-cream text-primary',
                    };
                @endphp
                <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ $badgeStyle }}">{{ $receipt->status }}</span>
            </div>
            <p class="text-xs text-ink-soft">
                Lokasi: <span class="font-medium text-ink">{{ $receipt->location->nama }} ({{ $receipt->location->kode }})</span> · Ditimbang pada {{ $receipt->waktu_timbang->format('d M Y, H:i') }} WIB
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if ($pembayaran && auth()->user()->hasRole('KEUANGAN', 'ADMIN', 'PEMILIK'))
                <a href="/app/cetak/slip/{{ $pembayaran->id }}" target="_blank"
                    class="inline-flex h-10 items-center gap-1.5 rounded-[10px] border border-primary bg-card px-4 text-xs font-semibold text-primary hover:bg-cream">
                    <span>📄</span>
                    <span>Cetak Slip PDF</span>
                </a>
            @endif
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif

    <!-- Main Content 2-Column Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left: Weight & Inspection Details (2 cols) -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Informasi Penimbangan & Petani -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-base font-bold text-primary">Informasi Penimbangan &amp; Petani</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-[10px] border border-line/60 bg-paper p-3.5">
                        <p class="text-xs text-ink-soft">Nama Petani Mitra</p>
                        <p class="mt-1 text-base font-bold text-ink">{{ $receipt->contract->partner->nama }}</p>
                        <p class="text-xs text-ink-soft">Kontrak #{{ str_pad($receipt->contract->id, 3, '0', STR_PAD_LEFT) }} · Musim {{ $receipt->contract->musim }}</p>
                    </div>
                    <div class="rounded-[10px] border border-line/60 bg-paper p-3.5">
                        <p class="text-xs text-ink-soft">Petugas Pos Timbang</p>
                        <p class="mt-1 text-base font-bold text-ink">{{ $receipt->receiver?->name ?? 'Petugas Gudang' }}</p>
                        <p class="text-xs text-ink-soft">Gudang: {{ $receipt->location->nama }}</p>
                    </div>
                </div>

                <!-- Weight Breakdown -->
                <div class="mt-4 rounded-[12px] border border-line/80 bg-paper p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Hasil Timbang Gabah</p>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-[8px] bg-card p-2.5 border border-line/50">
                            <p class="text-xs text-ink-soft">Bruto</p>
                            <p class="mt-1 font-mono text-base font-semibold text-ink">{{ number_format((float) $receipt->bruto_kg, 1, ',', '.') }} kg</p>
                        </div>
                        <div class="rounded-[8px] bg-card p-2.5 border border-line/50">
                            <p class="text-xs text-ink-soft">Tara (Kendaraan)</p>
                            <p class="mt-1 font-mono text-base font-semibold text-ink">{{ number_format((float) $receipt->tara_kg, 1, ',', '.') }} kg</p>
                        </div>
                        <div class="rounded-[8px] bg-card p-2.5 border border-primary/40 bg-cream/30">
                            <p class="text-xs font-semibold text-primary">Netto Gabah</p>
                            <p class="mt-1 font-mono text-lg font-bold text-primary">{{ number_format((float) $receipt->netto_kg, 1, ',', '.') }} kg</p>
                        </div>
                    </div>

                    @if ($receipt->selisih_persen !== null)
                        <div class="mt-3 rounded-[8px] p-3 {{ abs((float)$receipt->selisih_persen) > 1.0 ? 'bg-warning-bg/60 border border-warning/40 text-warning' : 'bg-success-bg text-success' }}">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold">Deviasi Timbangan terhadap Rencana Panen:</span>
                                <span class="font-mono font-bold">{{ $receipt->selisih_persen }}%</span>
                            </div>
                            @if ($receipt->selisih_alasan)
                                <p class="mt-1 text-xs"><strong>Alasan:</strong> {{ $receipt->selisih_alasan }}</p>
                            @endif
                            @if ($receipt->approver)
                                <p class="mt-0.5 text-[11px]">Disetujui oleh: {{ $receipt->approver->name }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Hasil Uji Mutu & Inspeksi -->
            @if ($insp)
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-base font-bold text-primary">Hasil Uji Mutu Gabah (SNI)</h2>
                        <span class="rounded-full bg-cream border border-line px-3 py-0.5 text-xs font-bold text-primary">
                            {{ $insp->grade?->kode ? 'Grade '.$insp->grade->kode : 'Tanpa Grade' }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 text-center">
                        <div class="rounded-[10px] border border-line/60 bg-paper p-3">
                            <p class="text-xs text-ink-soft">Kadar Air</p>
                            <p class="mt-1 font-mono text-base font-bold {{ (float)$insp->kadar_air > 18.0 ? 'text-danger' : 'text-primary' }}">{{ $insp->kadar_air }}%</p>
                            <p class="text-[10px] text-ink-soft">Maks. 14% (A)</p>
                        </div>
                        <div class="rounded-[10px] border border-line/60 bg-paper p-3">
                            <p class="text-xs text-ink-soft">Butir Hampa</p>
                            <p class="mt-1 font-mono text-base font-bold text-primary">{{ $insp->kadar_hampa }}%</p>
                            <p class="text-[10px] text-ink-soft">Maks. 3% (A)</p>
                        </div>
                        <div class="rounded-[10px] border border-line/60 bg-paper p-3">
                            <p class="text-xs text-ink-soft">Butir Patah</p>
                            <p class="mt-1 font-mono text-base font-bold text-primary">{{ $insp->butir_patah !== null ? $insp->butir_patah.'%' : '—' }}</p>
                            <p class="text-[10px] text-ink-soft">Maks. 5% (A)</p>
                        </div>
                        <div class="rounded-[10px] border border-line/60 bg-paper p-3">
                            <p class="text-xs text-ink-soft">Varietas</p>
                            <p class="mt-1 text-sm font-bold text-primary truncate">{{ $insp->varietas }}</p>
                            <p class="text-[10px] text-ink-soft">Komoditas</p>
                        </div>
                    </div>

                    @if ($insp->alasan)
                        <div class="mt-3 rounded-[10px] bg-warning-bg/60 p-3 text-xs text-warning border border-warning/30">
                            <strong>Keputusan Inspeksi ({{ $insp->keputusan }}):</strong> {{ $insp->alasan }}
                        </div>
                    @endif

                    @if ($foto)
                        <div class="mt-4">
                            <p class="text-xs font-semibold text-ink-soft mb-2">Foto Sampel Gabah &amp; Hasil Pengujian:</p>
                            <div class="overflow-hidden rounded-[10px] border border-line max-h-60 bg-black/5">
                                <img src="{{ $foto }}" alt="Bukti inspeksi mutu" class="w-full object-cover">
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Lot Inventory Created -->
            @if ($receipt->lots->isNotEmpty())
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <h2 class="font-display text-base font-bold text-primary">Lot Stok Terdaftar</h2>
                    <div class="mt-3 divide-y divide-line/60">
                        @foreach ($receipt->lots as $lot)
                            <div wire:key="lot-row-{{ $lot->id }}" class="flex items-center justify-between py-2.5">
                                <div>
                                    <a href="/app/stok/{{ $lot->id }}" class="font-mono text-sm font-bold text-primary hover:underline">
                                        {{ $lot->kode_lot }}
                                    </a>
                                    <p class="text-xs text-ink-soft">
                                        {{ $lot->commodity->nama }} Grade {{ $lot->grade?->kode }} · Terima {{ $lot->tgl_terima->format('d M Y') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-primary">
                                        {{ $lot->status }}
                                    </span>
                                    <a href="/app/stok/{{ $lot->id }}" class="text-xs font-semibold text-primary underline">
                                        Kartu Stok →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right: Payment & Workflow Actions (1 col) -->
        <div class="space-y-6">
            <!-- Nilai Pembayaran Summary -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h3 class="font-display text-base font-bold text-primary">Kalkulasi Pembayaran</h3>
                <div class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-ink-soft">Netto Diterima</span>
                        <span class="font-mono font-semibold">{{ number_format((float) $receipt->netto_kg, 1, ',', '.') }} kg</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-soft">Harga Satuan</span>
                        <span class="font-mono font-semibold">{{ $receipt->harga_per_kg ? 'Rp'.number_format($receipt->harga_per_kg, 0, ',', '.').'/kg' : '—' }}</span>
                    </div>
                    <div class="border-t border-line/60 pt-2.5 flex justify-between items-baseline">
                        <span class="font-semibold text-ink">Total Nilai Bayar</span>
                        <span class="font-mono text-xl font-bold text-primary">{{ $receipt->nilai_bayar ? 'Rp'.number_format($receipt->nilai_bayar, 0, ',', '.') : '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Draft Actions (Petugas Gudang) -->
            @if ($receipt->status === 'DRAFT' && auth()->user()->can('create', App\Modules\ProcurementReceiving\Models\Receipt::class))
                <div wire:key="receipt-draft-actions" class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <h3 class="font-display text-sm font-bold text-primary">Lanjutkan Penginputan</h3>
                    <p class="mt-1 text-xs text-ink-soft">Draft penerimaan ini belum diselesaikan.</p>
                    <div class="mt-4 space-y-2">
                        <a href="/app/terima?draft={{ $receipt->id }}" class="flex h-11 w-full items-center justify-center rounded-[10px] bg-primary text-sm font-semibold text-white shadow-xs hover:bg-primary-dark">
                            Lanjutkan Input Form
                        </a>
                        <button type="button" wire:click="hapusDraft" wire:confirm="Hapus draft penerimaan ini?"
                            class="h-10 w-full rounded-[10px] border border-danger text-xs font-semibold text-danger hover:bg-danger-bg">
                            Hapus Draft
                        </button>
                    </div>
                </div>
            @endif

            <!-- Approval Box (Kepala Gudang) -->
            @if ($receipt->status === 'MENUNGGU_PERSETUJUAN' && auth()->user()->can('approve', $receipt))
                <div wire:key="receipt-approval-box" class="rounded-[14px] border border-warning/60 bg-card p-5 shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-warning text-xs font-bold text-white">!</span>
                        <h3 class="font-display text-base font-bold text-warning">Persetujuan Selisih Timbang</h3>
                    </div>
                    <p class="mt-1.5 text-xs text-ink-soft">
                        Terdapat deviasi timbangan {{ $receipt->selisih_persen }}%. Sebagai Kepala Gudang, masukkan justifikasi untuk menyetujui atau menolak.
                    </p>
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-ink">Alasan / Catatan Persetujuan <span class="text-danger">*</span></label>
                        <textarea wire:model="alasan_setuju" rows="3" placeholder="Contoh: Susut transportasi wajar dari sawah..."
                            class="mt-1 w-full rounded-[10px] border border-line bg-paper px-3 py-2 text-sm focus:border-primary focus:outline-none"></textarea>
                        @error('alasan_setuju') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button wire:click="tolak" type="button"
                            class="h-11 flex-1 rounded-[10px] border border-danger text-sm font-semibold text-danger hover:bg-danger-bg">
                            Tolak
                        </button>
                        <button wire:click="setujui" type="button"
                            class="h-11 flex-1 rounded-[10px] bg-primary text-sm font-semibold text-white hover:bg-primary-dark">
                            Setujui
                        </button>
                    </div>
                </div>
            @endif

            <!-- Payment Box (Keuangan) -->
            @if ($receipt->status === 'CONFIRMED' && auth()->user()->can('pay', $receipt))
                <div wire:key="receipt-payment-box" class="rounded-[14px] border border-primary/40 bg-card p-5 shadow-xs">
                    <h3 class="font-display text-base font-bold text-primary">Pembayaran Petani</h3>
                    <p class="mt-1 text-xs text-ink-soft">Catat pembayaran untuk penerimaan gabah ini.</p>
                    <div class="mt-3 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Metode Pembayaran</label>
                            <select wire:model.live="metode" class="h-10 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="TUNAI">Tunai / Kasir Pos</option>
                                <option value="TRANSFER">Transfer Bank</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">
                                {{ $metode === 'TRANSFER' ? 'Nomor Referensi Transfer' : 'Nomor Kwitansi / Tanda Terima Tunai' }} <span class="text-danger">*</span>
                            </label>
                            <input wire:model="referensi" type="text"
                                placeholder="{{ $metode === 'TRANSFER' ? 'Contoh: TRF-BCA-88912' : 'Contoh: KWT-G1-001' }}"
                                class="h-10 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            @error('referensi') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">
                                @if ($metode === 'TRANSFER')
                                    Foto Struk / Bukti Transfer <span class="text-danger">*</span>
                                @else
                                    Foto Kwitansi / Serah Terima <span class="text-ink-soft font-normal">(Opsional)</span>
                                @endif
                            </label>
                            <input wire:key="bukti-foto-{{ $metode }}" wire:model="bukti_foto" type="file" accept="image/*"
                                class="w-full rounded-[10px] border border-line bg-paper px-3 py-2 text-xs file:mr-2 file:rounded-[6px] file:border-0 file:bg-cream file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-primary">
                            <div wire:loading wire:target="bukti_foto" class="mt-1 text-xs text-ink-soft">Mengunggah gambar...</div>
                            @if ($bukti_foto)
                                <p class="mt-1 text-xs font-medium text-success">✓ Gambar bukti siap diunggah.</p>
                            @endif
                            @error('bukti_foto') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="bayar" wire:loading.attr="disabled" type="button"
                            class="mt-2 h-12 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark disabled:opacity-50">
                            Catat Pembayaran Rp{{ number_format((int) $receipt->nilai_bayar, 0, ',', '.') }}
                        </button>
                    </div>
                </div>
            @endif

            <!-- Bukti Pembayaran / Slip Tercatat -->
            @if ($pembayaran)
                <div wire:key="receipt-slip-box" class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-primary">Slip Pembayaran</h3>
                        <span class="rounded-full bg-success-bg px-2.5 py-0.5 text-xs font-bold text-success">LUNAS</span>
                    </div>
                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-ink-soft">Metode</span>
                            <span class="font-semibold text-ink">{{ $pembayaran->metode }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-soft">Nomor Referensi</span>
                            <span class="font-mono font-semibold text-ink">{{ $pembayaran->referensi }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-soft">Waktu Bayar</span>
                            <span class="text-ink">{{ $pembayaran->created_at->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                    @if ($buktiUrl)
                        <div class="mt-3">
                            <p class="text-[11px] font-semibold text-ink-soft mb-1.5">Bukti / Struk Pembayaran:</p>
                            <div class="overflow-hidden rounded-[8px] border border-line max-h-44 bg-black/5">
                                <img src="{{ $buktiUrl }}" alt="Bukti pembayaran" class="w-full object-cover">
                            </div>
                        </div>
                    @endif
                    @if (auth()->user()->hasRole('KEUANGAN', 'ADMIN', 'PEMILIK'))
                        <a href="/app/cetak/slip/{{ $pembayaran->id }}" target="_blank"
                            class="mt-4 flex h-10 w-full items-center justify-center gap-1.5 rounded-[10px] border border-primary text-xs font-semibold text-primary hover:bg-cream">
                            <span>📄 Cetak Slip Pembayaran</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

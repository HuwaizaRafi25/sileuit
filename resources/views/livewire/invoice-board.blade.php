<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Faktur &amp; Manajemen Piutang (AR)</h1>
            <p class="text-xs text-ink-soft">Penerbitan invoice pesanan terkirim, pencatatan pelunasan, dan analisis aging</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-cream px-3 py-1.5 text-xs font-semibold text-primary">
                Batas Termin: Sesuai Profil Mitra
            </span>
        </div>
    </div>

    @if ($pesan)
        <div class="mt-4 rounded-[12px] bg-success-bg p-4 text-sm font-semibold text-success border border-success/30">
            ✓ {{ $pesan }}
        </div>
    @endif
    @error('terbit')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror
    @error('bayar')
        <div class="mt-4 rounded-[12px] bg-danger-bg p-4 text-sm font-semibold text-danger border border-danger/30">
            ⚠️ {{ $message }}
        </div>
    @enderror

    <!-- Aging Piutang Dashboard Hero -->
    <div class="mt-6 rounded-[14px] border border-line bg-card p-5 shadow-xs">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-line/60 pb-3">
            <div>
                <h2 class="font-display text-base font-bold text-primary">Distribusi Umur Piutang (Aging AR)</h2>
                <p class="text-xs text-ink-soft">Pemantauan risiko kredit dan piutang jatuh tempo</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-ink-soft">Total Piutang Berjalan</p>
                <p class="font-display text-2xl font-bold text-primary">Rp{{ number_format($aging['total'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ($aging['buckets'] as $bucket => $nilai)
                @php
                    $isRisk = in_array($bucket, ['61-90', '>90']) && $nilai > 0;
                @endphp
                <div class="rounded-[10px] border {{ $isRisk ? 'border-danger/40 bg-danger-bg/20' : 'border-line/60 bg-paper' }} p-3 text-center">
                    <p class="text-xs font-semibold {{ $isRisk ? 'text-danger' : 'text-ink-soft' }}">{{ $bucket }} Hari</p>
                    <p class="mt-1 font-mono text-sm font-bold {{ $isRisk ? 'text-danger' : 'text-ink' }}">
                        Rp{{ number_format($nilai, 0, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Two Operational Cards: Terbitkan & Catat Bayar -->
    @can('terbit-invoice')
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Card 1: Terbitkan Invoice -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-base font-bold text-primary">Terbitkan Invoice Baru</h2>
                <p class="text-xs text-ink-soft">Untuk pesanan yang telah dikirim dan diterima (DELIVERED)</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Pilih Pesanan Siap Faktur <span class="text-danger">*</span></label>
                        <select wire:model.live="order_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Pesanan (DELIVERED) —</option>
                            @foreach ($pesananSiap as $o)
                                <option value="{{ $o->id }}">{{ $o->no_dok }} · {{ $o->partner->nama }}</option>
                            @endforeach
                        </select>
                        @error('order_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <button wire:click="terbitkan" type="button"
                        class="h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                        Terbitkan Invoice
                    </button>

                    @if ($butuhPersetujuanKredit && auth()->user()->hasRole('PEMILIK', 'ADMIN', 'KEUANGAN'))
                        <button wire:click="terbitkan(true)" type="button"
                            class="h-11 w-full rounded-[10px] border border-warning text-xs font-bold text-warning hover:bg-warning-bg transition">
                            ⚠️ Lewati Batas Kredit &amp; Terbitkan (Override Pemilik/Keuangan)
                        </button>
                    @endif
                </div>
            </div>

            <!-- Card 2: Catat Pembayaran -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-base font-bold text-primary">Catat Pembayaran Mitra</h2>
                <p class="text-xs text-ink-soft">Alokasi otomatis metode FIFO ke invoice terlama</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-ink-soft mb-1">Mitra B2B / Pelanggan <span class="text-danger">*</span></label>
                        <select wire:model="partner_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            <option value="">— Pilih Mitra —</option>
                            @foreach ($mitra as $m)
                                <option value="{{ $m->id }}">{{ $m->nama }} (Limit: Rp{{ number_format($m->batas_kredit, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                        @error('partner_id') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Metode <span class="text-danger">*</span></label>
                            <select wire:model="metode" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                                <option value="TRANSFER">Transfer Bank</option>
                                <option value="TUNAI">Tunai</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Jumlah Bayar (Rp) <span class="text-danger">*</span></label>
                            <input wire:model="jumlah" type="number" placeholder="Contoh: 5000000"
                                class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono focus:border-primary focus:outline-none">
                            @error('jumlah') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-ink-soft mb-1">Nomor Bukti / Referensi <span class="text-danger">*</span></label>
                            <input wire:model="referensi" type="text" placeholder="Contoh: TRF-BNI-9921"
                                class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none">
                            @error('referensi') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button wire:click="catatBayar" type="button"
                        class="h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                        Catat Pembayaran &amp; Alokasikan
                    </button>
                </div>
            </div>
        </div>
    @endcan

    <!-- Daftar Invoice -->
    <div class="mt-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-base font-bold text-primary">Daftar Invoice Terbit</h2>
            <span class="text-xs text-ink-soft">Total: {{ $daftar->count() }} invoice</span>
        </div>

        <div class="space-y-3">
            @forelse ($daftar as $inv)
                @php
                    $isLunas = $inv->status() === 'LUNAS';
                    $statusClass = $isLunas ? 'bg-success-bg text-success' : 'bg-warning-bg text-warning font-semibold';
                @endphp
                <div wire:key="inv-card-{{ $inv->id }}" class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-base font-bold text-primary">{{ $inv->no }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs {{ $statusClass }}">{{ $inv->status() }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/app/cetak/invoice/{{ $inv->id }}" target="_blank"
                                class="inline-flex h-9 items-center gap-1.5 rounded-[8px] border border-primary bg-card px-3.5 text-xs font-semibold text-primary hover:bg-cream">
                                <span>📄</span>
                                <span>Cetak Invoice PDF</span>
                            </a>
                        </div>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-ink-soft">
                        <span>Pelanggan: <strong class="text-ink">{{ $inv->partner->nama }}</strong></span>
                        <span>·</span>
                        <span>Jatuh Tempo: <strong class="text-ink">{{ $inv->jatuh_tempo->format('d M Y') }}</strong></span>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2 rounded-[10px] border border-line/60 bg-paper p-3 text-center text-xs">
                        <div>
                            <p class="text-ink-soft">Total Tagihan</p>
                            <p class="mt-0.5 font-mono font-bold text-ink">Rp{{ number_format($inv->total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-ink-soft">Telah Dibayar</p>
                            <p class="mt-0.5 font-mono font-bold text-success">Rp{{ number_format($inv->dibayar(), 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-primary font-semibold">Sisa Saldo</p>
                            <p class="mt-0.5 font-mono font-bold text-primary">Rp{{ number_format($inv->saldo(), 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                    <p class="text-base text-ink-soft">Belum ada invoice yang terbit.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

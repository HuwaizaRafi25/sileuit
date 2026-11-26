<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- User Profile & Location Header -->
    <div class="rounded-[16px] border border-line bg-card p-6 shadow-xs">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-xl font-bold text-primary">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-display text-2xl font-bold text-primary">{{ auth()->user()->name }}</h1>
                        <span class="rounded-full bg-success-bg px-2.5 py-0.5 text-xs font-semibold text-success">Aktif</span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-ink-soft">
                        @foreach (auth()->user()->roles as $role)
                            <span class="rounded-[6px] bg-cream px-2 py-0.5 font-medium text-primary">{{ $role->nama }}</span>
                        @endforeach
                        <span>·</span>
                        <span class="font-medium text-ink">{{ auth()->user()->email ?: auth()->user()->hp }}</span>
                    </div>
                </div>
            </div>

            <!-- Location Switcher / Indicator -->
            <div class="flex items-center gap-3">
                @if ($availableLocations->count() > 1)
                    <div class="flex items-center gap-2 rounded-[10px] border border-line bg-paper px-3 py-1.5">
                        <span class="text-xs text-ink-soft">Lokasi:</span>
                        <select wire:change="gantiLokasi($event.target.value)" class="bg-transparent text-sm font-semibold text-primary focus:outline-none">
                            @foreach ($availableLocations as $loc)
                                <option value="{{ $loc->id }}" @selected(auth()->user()->active_location_id == $loc->id)>
                                    {{ $loc->kode }} — {{ $loc->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="rounded-[10px] border border-line bg-paper px-3.5 py-2 text-right">
                        <p class="text-xs text-ink-soft">Lokasi Kerja</p>
                        <p class="text-sm font-semibold text-primary">{{ auth()->user()->activeLocation?->nama ?? 'Semua Lokasi' }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert Banner (Approvals / Warnings) -->
    @php
        $canLihatPenerimaan = auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN');
        $hasVisibleAlerts = ($pendingReceipts > 0 && $canLihatPenerimaan)
            || ($pendingAdjustments > 0 && auth()->user()->can('lihat-penyesuaian'))
            || ($pendingReturns > 0 && auth()->user()->can('lihat-retur'))
            || ($lotMendekati > 0 && auth()->user()->can('lihat-stok'));
    @endphp
    @if ($hasVisibleAlerts)
        <div class="mt-4 rounded-[12px] border border-warning/40 bg-warning-bg/40 p-4">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-warning text-xs font-bold text-white">!</span>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-warning">Perhatian &amp; Tindakan Diperlukan</h3>
                    <div class="mt-1.5 flex flex-wrap gap-2 text-xs">
                        @if ($pendingReceipts > 0 && $canLihatPenerimaan)
                            <a href="/app/penerimaan" class="inline-flex items-center gap-1.5 rounded-full bg-card px-3 py-1 font-semibold text-warning shadow-xs hover:underline">
                                <span>{{ $pendingReceipts }} Penerimaan butuh persetujuan selisih</span> →
                            </a>
                        @endif
                        @if ($pendingAdjustments > 0)
                            @can('lihat-penyesuaian')
                                <a href="/app/penyesuaian" class="inline-flex items-center gap-1.5 rounded-full bg-card px-3 py-1 font-semibold text-warning shadow-xs hover:underline">
                                    <span>{{ $pendingAdjustments }} Penyesuaian stok butuh persetujuan</span> →
                                </a>
                            @endcan
                        @endif
                        @if ($pendingReturns > 0)
                            @can('lihat-retur')
                                <a href="/app/retur" class="inline-flex items-center gap-1.5 rounded-full bg-card px-3 py-1 font-semibold text-warning shadow-xs hover:underline">
                                    <span>{{ $pendingReturns }} Permintaan retur belum diproses</span> →
                                </a>
                            @endcan
                        @endif
                        @if ($lotMendekati > 0)
                            @can('lihat-stok')
                                <a href="/app/stok" class="inline-flex items-center gap-1.5 rounded-full bg-card px-3 py-1 font-semibold text-danger shadow-xs hover:underline">
                                    <span>{{ $lotMendekati }} Lot mendekati batas umur simpan (&ge;80%)</span> →
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Metric 1: Stok On-Hand -->
        @can('lihat-stok')
            <a href="/app/stok" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Stok On-Hand</span>
                    <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ number_format($stokKg, 1, ',', '.') }} <span class="text-base font-normal text-ink-soft">kg</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Tersedia: <strong class="text-success">{{ number_format($stokTersedia, 1, ',', '.') }}</strong></span>
                    <span class="text-ink-soft">Reserved: <strong class="text-warning">{{ number_format($stokReserved, 1, ',', '.') }}</strong></span>
                </div>
            </a>
        @endcan

        <!-- Metric 2: Penerimaan -->
        @if (auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'))
            <a href="/app/penerimaan" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Hari Ini</span>
                    <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ $terimaHariIni }} <span class="text-base font-normal text-ink-soft">truk/lot</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Total hari ini: <strong>{{ number_format($terimaHariIniKg, 1, ',', '.') }} kg</strong></span>
                    <span class="text-ink-soft">Total arsip: {{ $terimaTotal }}</span>
                </div>
            </a>
        @else
            <div class="rounded-[14px] border border-line bg-card p-5">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Hari Ini</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ $terimaHariIni }} <span class="text-base font-normal text-ink-soft">truk/lot</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Total: <strong>{{ number_format($terimaHariIniKg, 1, ',', '.') }} kg</strong></span>
                </div>
            </div>
        @endif

        <!-- Metric 3: Pesanan & Pengiriman -->
        @if (auth()->user()->hasRole('KURIR'))
            <a href="/app/pengiriman" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Tugas Pengiriman</span>
                    <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ $pengirimanAktif }} <span class="text-base font-normal text-ink-soft">tugas</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Armada siap jalan</span>
                    <span class="text-primary underline">Buka pengiriman →</span>
                </div>
            </a>
        @elseif (auth()->user()->can('buat-pesanan') || auth()->user()->can('lihat-fulfillment'))
            <a href="{{ auth()->user()->can('buat-pesanan') ? '/app/pesanan' : '/app/pengiriman' }}" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Pesanan Aktif</span>
                    <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ $pesananAktif }} <span class="text-base font-normal text-ink-soft">pesanan</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Dalam Pengiriman: <strong class="text-primary">{{ $pengirimanAktif }}</strong></span>
                    <span class="text-primary underline">Lihat alokasi</span>
                </div>
            </a>
        @else
            <div class="rounded-[14px] border border-line bg-card p-5">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Pesanan Aktif</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">{{ $pesananAktif }} <span class="text-base font-normal text-ink-soft">pesanan</span></p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Dalam Pengiriman: <strong class="text-primary">{{ $pengirimanAktif }}</strong></span>
                </div>
            </div>
        @endif

        <!-- Metric 4: Keuangan / Kasir / Approvals -->
        @can('lihat-invoice')
            <a href="/app/invoice" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                <div class="flex items-center justify-between text-ink-soft">
                    <span class="text-xs font-semibold uppercase tracking-wider">Piutang Berjalan</span>
                    <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-primary">Rp{{ number_format($piutang, 0, ',', '.') }}</p>
                <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                    <span class="text-ink-soft">Buku Piutang &amp; Aging</span>
                    <span class="text-primary underline">Detail →</span>
                </div>
            </a>
        @else
            @can('pos-jual')
                <a href="/app/pos" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                    <div class="flex items-center justify-between text-ink-soft">
                        <span class="text-xs font-semibold uppercase tracking-wider">Penjualan Kasir Hari Ini</span>
                        <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                    </div>
                    <p class="mt-2 font-display text-2xl font-bold text-primary">Rp{{ number_format($posHariIni, 0, ',', '.') }}</p>
                    <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                        <span class="text-ink-soft">Buka Kasir POS</span>
                        <span class="text-primary underline">Mulai transaksi →</span>
                    </div>
                </a>
            @else
                @if (auth()->user()->hasRole('KURIR'))
                    <a href="/app/pengiriman" class="group block rounded-[14px] border border-line bg-card p-5 transition hover:border-primary/50 hover:shadow-sm">
                        <div class="flex items-center justify-between text-ink-soft">
                            <span class="text-xs font-semibold uppercase tracking-wider">Status Armada Kurir</span>
                            <span class="text-primary group-hover:translate-x-0.5 transition-transform">→</span>
                        </div>
                        <p class="mt-2 font-display text-2xl font-bold text-success">Siap Antar</p>
                        <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                            <span class="text-ink-soft">Pangkalan: {{ auth()->user()->activeLocation?->kode ?? 'G1' }}</span>
                            <span class="text-primary underline">Buka rute →</span>
                        </div>
                    </a>
                @else
                    <div class="rounded-[14px] border border-line bg-card p-5">
                        <div class="flex items-center justify-between text-ink-soft">
                            <span class="text-xs font-semibold uppercase tracking-wider">Persetujuan Tertunda</span>
                        </div>
                        <p class="mt-2 font-display text-2xl font-bold {{ $totalPendingApprovals > 0 ? 'text-warning' : 'text-primary' }}">{{ $totalPendingApprovals }}</p>
                        <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                            <span class="text-ink-soft">Dokumen menunggu review</span>
                        </div>
                    </div>
                @endif
            @endcan
        @endcan
    </div>

    <!-- Aging Piutang Section (Keuangan/Pemilik) -->
    @can('lihat-laporan')
        @if (count($aging['buckets'] ?? []) > 0 && $piutang > 0)
            <div class="mt-6 rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-lg font-bold text-primary">Aging Piutang Dagang</h2>
                        <p class="text-xs text-ink-soft">Distribusi umur piutang mitra B2B &amp; retail</p>
                    </div>
                    <span class="font-display text-lg font-bold text-primary">Rp{{ number_format($aging['total'], 0, ',', '.') }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach ($aging['buckets'] as $b => $n)
                        <div class="rounded-[10px] border border-line/60 bg-paper p-3 text-center">
                            <p class="text-xs font-medium text-ink-soft">{{ $b }}</p>
                            <p class="mt-1 font-semibold text-primary">Rp{{ number_format($n, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endcan

    <!-- Main Two-Column Layout -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left: Recent Receipts & Active Orders (2 Cols) -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Recent Receipts Table or Courier Tasks -->
            @if (auth()->user()->hasRole('KURIR'))
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-line/60">
                        <div>
                            <h2 class="font-display text-lg font-bold text-primary">Tugas Pengiriman Kurir</h2>
                            <p class="text-xs text-ink-soft">Armada pengantaran beras ke pelanggan &amp; mitra</p>
                        </div>
                        <a href="/app/pengiriman" class="rounded-[8px] bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-dark">Buka Tugas Pengiriman →</a>
                    </div>
                    <div class="py-6 text-center text-xs text-ink-soft">
                        <p class="text-sm font-semibold text-ink">Semua rute pengiriman dan surat jalan dapat diakses di menu Pengiriman.</p>
                        <a href="/app/pengiriman" class="mt-3 inline-flex items-center gap-1.5 rounded-[10px] bg-primary px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-primary-dark">
                            <span>🚚</span>
                            <span>Mulai Pengantaran &amp; Konfirmasi Surat Jalan</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-line/60">
                        <div>
                            <h2 class="font-display text-lg font-bold text-primary">Penerimaan Gabah Terkini</h2>
                            <p class="text-xs text-ink-soft">5 transaksi timbang &amp; inspeksi terbaru</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                                <a href="/app/terima" class="rounded-[8px] bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-dark">+ Timbang Baru</a>
                            @endcan
                            @if (auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'))
                                <a href="/app/penerimaan" class="text-xs font-semibold text-primary underline">Lihat Semua</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 divide-y divide-line/50">
                        @forelse ($terimaTerbaru as $r)
                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 flex h-8 w-8 items-center justify-center rounded-[8px] bg-cream text-xs font-bold text-primary">
                                        {{ $r->location->kode }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            @if (auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'))
                                                <a href="/app/penerimaan/{{ $r->id }}" class="font-mono text-sm font-bold text-primary hover:underline">
                                                    {{ $r->no_dok }}
                                                </a>
                                            @else
                                                <span class="font-mono text-sm font-bold text-primary">{{ $r->no_dok }}</span>
                                            @endif
                                            @php
                                                $badgeClass = match($r->status) {
                                                    'CONFIRMED' => 'bg-success-bg text-success',
                                                    'PAID' => 'bg-primary/10 text-primary font-bold',
                                                    'MENUNGGU_PERSETUJUAN' => 'bg-warning-bg text-warning font-semibold',
                                                    'REJECTED' => 'bg-danger-bg text-danger font-semibold',
                                                    default => 'bg-cream text-primary',
                                                };
                                            @endphp
                                            <span class="rounded-[6px] px-2 py-0.5 text-[11px] {{ $badgeClass }}">{{ $r->status }}</span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-ink-soft">
                                            Petani: <span class="font-medium text-ink">{{ $r->contract->partner->nama }}</span> · {{ $r->waktu_timbang->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-bold text-ink">{{ number_format((float) $r->netto_kg, 1, ',', '.') }} kg</p>
                                    <p class="text-[11px] text-ink-soft">{{ $r->nilai_bayar ? 'Rp'.number_format($r->nilai_bayar, 0, ',', '.') : '—' }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-sm text-ink-soft">
                                Belum ada penerimaan gabah.
                                @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                                    <a href="/app/terima" class="ml-1 text-primary underline">Mulai timbang sekarang</a>
                                @endcan
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            <!-- Recent Orders / Shipments -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-line/60">
                    <div>
                        <h2 class="font-display text-lg font-bold text-primary">Pesanan &amp; Pengiriman Terkini</h2>
                        <p class="text-xs text-ink-soft">Aktivitas pemenuhan pesanan retail &amp; B2B</p>
                    </div>
                    @if (auth()->user()->can('buat-pesanan'))
                        <a href="/app/pesanan" class="text-xs font-semibold text-primary underline">Lihat Pesanan</a>
                    @elseif (auth()->user()->can('lihat-fulfillment'))
                        <a href="/app/pengiriman" class="text-xs font-semibold text-primary underline">Lihat Pengiriman</a>
                    @endif
                </div>

                <div class="mt-3 divide-y divide-line/50">
                    @forelse ($pesananTerbaru as $po)
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-primary">{{ $po->no_dok }}</span>
                                    <span class="rounded-[6px] bg-cream px-2 py-0.5 text-[11px] font-medium text-primary">{{ $po->status }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-ink-soft">
                                    Mitra: <span class="font-medium text-ink">{{ $po->partner?->nama ?? 'Internal' }}</span>
                                    @if ($po->lines->isNotEmpty())
                                        · {{ $po->lines->first()->commodity->nama }} ({{ number_format((float) $po->lines->sum('qty_kg'), 1, ',', '.') }} kg)
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                @if (auth()->user()->can('lihat-fulfillment'))
                                    <a href="/app/pengiriman" class="rounded-[6px] border border-line px-2.5 py-1 text-xs font-medium text-primary hover:bg-cream">
                                        Proses →
                                    </a>
                                @elseif (auth()->user()->can('buat-pesanan'))
                                    <a href="/app/pesanan" class="rounded-[6px] border border-line px-2.5 py-1 text-xs font-medium text-primary hover:bg-cream">
                                        Detail →
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-sm text-ink-soft">
                            Belum ada pesanan aktif.
                            @if (auth()->user()->can('buat-pesanan'))
                                <a href="/app/pesanan" class="ml-1 text-primary underline">Buat pesanan baru</a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Action Hub & Audit Feed (1 Col) -->
        <div class="space-y-6">
            <!-- Quick Action Hub -->
            <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                <h2 class="font-display text-lg font-bold text-primary">Menu &amp; Aksi Cepat</h2>
                <p class="text-xs text-ink-soft">Akses cepat sesuai peran Anda</p>

                <!-- Warehouse / Gudang Group -->
                <div class="mt-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-ink-soft">Operasional Gudang &amp; Logistik</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @if (auth()->user()->hasRole('KURIR'))
                            <a href="/app/pengiriman" class="col-span-2 flex items-center justify-center gap-2 rounded-[10px] bg-primary p-3 text-center text-xs font-bold text-white hover:bg-primary-dark shadow-xs">
                                <span class="text-base">🚚</span>
                                <span>Buka Tugas Pengiriman Kurir</span>
                            </a>
                        @endif
                        @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                            <a href="/app/terima" class="flex flex-col items-center justify-center rounded-[10px] bg-primary p-3 text-center text-xs font-semibold text-white hover:bg-primary-dark">
                                <span class="text-base">🌾</span>
                                <span class="mt-1">+ Terima Gabah</span>
                            </a>
                        @endcan
                        @if (auth()->user()->hasRole('PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KEUANGAN', 'ADMIN', 'PEMILIK', 'PENGADAAN'))
                            <a href="/app/penerimaan" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-3 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">📋</span>
                                <span class="mt-1">Daftar Penerimaan</span>
                            </a>
                        @endif
                        @can('lihat-stok')
                            <a href="/app/stok" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-3 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">📦</span>
                                <span class="mt-1">Stok &amp; Kartu Lot</span>
                            </a>
                        @endcan
                        @can('buat-transfer')
                            <a href="/app/transfer" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-3 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">🔄</span>
                                <span class="mt-1">Transfer Gudang</span>
                            </a>
                        @endcan
                        @can('buat-penyesuaian')
                            <a href="/app/penyesuaian" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-3 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">⚖️</span>
                                <span class="mt-1">Penyesuaian Stok</span>
                            </a>
                        @endcan
                        @can('lihat-fulfillment')
                            <a href="/app/pengiriman" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-3 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">🚚</span>
                                <span class="mt-1">Pengiriman</span>
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Sales & POS Group -->
                <div class="mt-4 border-t border-line/60 pt-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-ink-soft">Penjualan &amp; Kasir</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @can('pos-jual')
                            <a href="/app/pos" class="col-span-2 flex items-center justify-center gap-2 rounded-[10px] bg-secondary p-3 text-center text-xs font-bold text-white hover:opacity-90">
                                <span>🛒</span>
                                <span>Buka Kasir POS</span>
                            </a>
                        @endcan
                        @can('buat-pesanan')
                            <a href="/app/pesanan" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-2.5 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">📑</span>
                                <span class="mt-0.5">Pesanan FEFO</span>
                            </a>
                        @endcan
                        @can('buat-retur')
                            <a href="/app/retur" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-2.5 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">↩️</span>
                                <span class="mt-0.5">Retur Barang</span>
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Finance & Admin Group -->
                <div class="mt-4 border-t border-line/60 pt-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-ink-soft">Keuangan &amp; Tata Kelola</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @can('lihat-invoice')
                            <a href="/app/invoice" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-2.5 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">🧾</span>
                                <span class="mt-0.5">Invoice &amp; Piutang</span>
                            </a>
                        @endcan
                        @can('lihat-audit')
                            <a href="/app/audit" class="flex flex-col items-center justify-center rounded-[10px] border border-line bg-paper p-2.5 text-center text-xs font-medium text-primary hover:bg-cream">
                                <span class="text-base">🔍</span>
                                <span class="mt-0.5">Audit Trail</span>
                            </a>
                        @endcan
                        @if (auth()->user()->isGlobal())
                            <a href="/admin" class="col-span-2 flex items-center justify-center gap-2 rounded-[10px] border border-primary/40 bg-cream p-2.5 text-xs font-semibold text-primary hover:bg-tan/40">
                                <span>⚙️</span>
                                <span>Panel Admin Filament</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Audit Trail Feed -->
            @if (!empty($recentAudit) && count($recentAudit) > 0)
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-line/60">
                        <h3 class="font-display text-sm font-bold text-primary">Aktivitas Sistem Terkini</h3>
                        @can('lihat-audit')
                            <a href="/app/audit" class="text-xs text-primary underline">Semua</a>
                        @endcan
                    </div>
                    <div class="mt-3 space-y-3">
                        @foreach ($recentAudit as $log)
                            <div class="text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-ink">{{ $log->user?->name ?? 'SYSTEM' }}</span>
                                    <span class="text-[10px] text-ink-soft">{{ $log->waktu->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 font-mono text-[11px] text-primary">{{ $log->aksi }}</p>
                                <p class="text-[10px] text-ink-soft">{{ $log->entitas ? $log->entitas.'#'.$log->entitas_id : 'Akses Sistem' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

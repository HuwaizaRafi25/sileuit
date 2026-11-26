<x-layouts.app title="Penerimaan Gabah — SiLeuit.id">
    <div class="mx-auto max-w-6xl px-4 py-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
                <h1 class="mt-1 font-display text-2xl font-bold text-primary">Daftar Penerimaan Gabah</h1>
                <p class="text-xs text-ink-soft">Pos penimbangan, inspeksi mutu bertingkat, dan pembuatan lot gabah</p>
            </div>
            @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                <a href="/app/terima" class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] bg-primary px-5 text-sm font-semibold text-white shadow-xs hover:bg-primary-dark transition">
                    <span>🌾</span>
                    <span>+ Terima Gabah Baru</span>
                </a>
            @endcan
        </div>

        @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
            <!-- IndexedDB Offline Outbox Banner -->
            <div x-data="sileuitOfflineOutboxWidget()" x-show="outbox.length > 0 || !isOnline" x-cloak
                class="mt-4 rounded-[12px] border border-warning/40 bg-warning-bg/50 px-4 py-3 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2 font-semibold text-ink">
                        <span class="inline-block h-2.5 w-2.5 rounded-full" :class="isOnline ? 'bg-warning' : 'bg-danger'"></span>
                        <span x-show="!isOnline">Browser sedang Offline. Catat penerimaan sementara melalui formulir offline.</span>
                        <span x-show="isOnline && pendingCount > 0" x-text="pendingCount + ' dokumen penerimaan menunggu sinkronisasi dari IndexedDB.'"></span>
                        <span x-show="isOnline && pendingCount === 0 && outbox.length > 0" x-text="outbox.length + ' riwayat antrean offline masih tersimpan di IndexedDB.'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" x-show="isOnline && pendingCount > 0" @click="syncAllAndReload" :disabled="busy"
                            class="rounded-[8px] bg-primary px-3 py-1.5 font-bold text-white hover:bg-primary-dark disabled:opacity-50">
                            <span x-text="busy ? 'Menyinkronkan…' : 'Sinkronkan ke Server'"></span>
                        </button>
                        <a href="/app/terima" class="rounded-[8px] border border-primary bg-card px-3 py-1.5 font-semibold text-primary hover:bg-cream">
                            Buka Pos Offline →
                        </a>
                    </div>
                </div>
                <p x-show="note" x-text="note" class="mt-1 text-ink-soft"></p>
            </div>
        @endcan

        <!-- Filter & Search Bar -->
        <div class="mt-6 rounded-[14px] border border-line bg-card p-4 shadow-xs">
            <form method="GET" action="/app/penerimaan" class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="relative flex-1">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari nomor dokumen (GRN-...) atau nama petani..."
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3.5 text-sm text-ink focus:border-primary focus:outline-none">
                </div>

                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    @php
                        $statuses = [
                            '' => 'Semua',
                            'DRAFT' => 'Draft',
                            'MENUNGGU_PERSETUJUAN' => 'Persetujuan',
                            'CONFIRMED' => 'Dikonfirmasi',
                            'PAID' => 'Lunas',
                            'REJECTED' => 'Ditolak',
                        ];
                    @endphp
                    @foreach ($statuses as $val => $label)
                        <button type="submit" name="status" value="{{ $val }}"
                            class="rounded-[8px] px-3 py-1.5 text-xs font-semibold transition {{ ($statusFilter === $val || ($val === '' && !$statusFilter)) ? 'bg-primary text-white' : 'bg-paper text-ink-soft hover:bg-cream border border-line/60' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </form>
        </div>

        <!-- Receipts List -->
        <div class="mt-6 space-y-3">
            @forelse ($daftar as $r)
                @php
                    $statusStyles = match($r->status) {
                        'CONFIRMED' => ['bg' => 'bg-success-bg', 'text' => 'text-success', 'border' => 'border-success/30'],
                        'PAID' => ['bg' => 'bg-primary/10', 'text' => 'text-primary font-bold', 'border' => 'border-primary/30'],
                        'MENUNGGU_PERSETUJUAN' => ['bg' => 'bg-warning-bg', 'text' => 'text-warning font-semibold', 'border' => 'border-warning/40'],
                        'REJECTED' => ['bg' => 'bg-danger-bg', 'text' => 'text-danger font-semibold', 'border' => 'border-danger/30'],
                        'DRAFT' => ['bg' => 'bg-cream', 'text' => 'text-ink-soft', 'border' => 'border-line'],
                        default => ['bg' => 'bg-cream', 'text' => 'text-primary', 'border' => 'border-line'],
                    };
                    $insp = $r->inspections->last();
                @endphp
                <div class="group rounded-[14px] border border-line bg-card p-5 shadow-xs transition hover:border-primary/40">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <!-- Left Details -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] bg-paper border border-line/80 font-mono text-xs font-bold text-primary">
                                {{ $r->location->kode }}
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="/app/penerimaan/{{ $r->id }}" class="font-mono text-base font-bold text-primary hover:underline">
                                        {{ $r->no_dok }}
                                    </a>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusStyles['bg'] }} {{ $statusStyles['text'] }}">
                                        {{ $r->status }}
                                    </span>
                                    @if ($insp?->grade)
                                        <span class="rounded-full bg-paper border border-line px-2 py-0.5 text-[11px] font-semibold text-primary">
                                            Grade {{ $insp->grade->kode }}
                                        </span>
                                    @endif
                                    @if ($r->selisih_persen !== null && abs((float)$r->selisih_persen) > 1.0)
                                        <span class="rounded-full bg-warning-bg px-2 py-0.5 text-[11px] font-semibold text-warning">
                                            Selisih {{ $r->selisih_persen }}%
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-semibold text-ink">
                                    Petani: <span class="text-primary">{{ $r->contract->partner->nama }}</span>
                                    <span class="text-xs font-normal text-ink-soft">(Kontrak #{{ str_pad($r->contract->id, 3, '0', STR_PAD_LEFT) }} · {{ $r->contract->musim }})</span>
                                </p>
                                <p class="mt-0.5 text-xs text-ink-soft">
                                    Ditimbang pada {{ $r->waktu_timbang->format('d M Y, H:i') }} WIB · Lokasi: {{ $r->location->nama }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Stats & Actions -->
                        <div class="flex items-center justify-between gap-4 border-t border-line/60 pt-3 sm:border-0 sm:pt-0 sm:justify-end">
                            <div class="text-left sm:text-right">
                                <p class="font-mono text-lg font-bold text-primary">
                                    {{ number_format((float) $r->netto_kg, 1, ',', '.') }} <span class="text-xs font-normal text-ink-soft">kg</span>
                                </p>
                                <p class="text-xs text-ink-soft">
                                    {{ $r->nilai_bayar ? 'Rp'.number_format($r->nilai_bayar, 0, ',', '.') : ($r->harga_per_kg ? 'Rp'.number_format($r->harga_per_kg, 0, ',', '.').'/kg' : 'Belum dinilai') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($r->status === 'DRAFT' && auth()->user()->can('create', App\Modules\ProcurementReceiving\Models\Receipt::class))
                                    <a href="/app/terima?draft={{ $r->id }}" class="rounded-[8px] bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary-dark">
                                        Lanjutkan
                                    </a>
                                @elseif ($r->status === 'MENUNGGU_PERSETUJUAN' && auth()->user()->can('approve', $r))
                                    <a href="/app/penerimaan/{{ $r->id }}" class="rounded-[8px] bg-warning px-3.5 py-2 text-xs font-semibold text-white hover:opacity-90">
                                        Review Selisih
                                    </a>
                                @else
                                    <a href="/app/penerimaan/{{ $r->id }}" class="rounded-[8px] border border-line bg-paper px-3.5 py-2 text-xs font-semibold text-primary hover:bg-cream">
                                        Detail →
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                    <p class="text-base text-ink-soft">Tidak ada penerimaan gabah yang sesuai dengan filter pencarian.</p>
                    @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                        <a href="/app/terima" class="mt-4 inline-flex items-center gap-2 rounded-[10px] bg-primary px-4 py-2 text-sm font-semibold text-white">
                            + Mulai Penerimaan Baru
                        </a>
                    @endcan
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $daftar->links() }}
        </div>
    </div>
</x-layouts.app>

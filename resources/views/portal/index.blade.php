<x-layouts.app title="Portal Petani Mitra — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-8">
        <!-- Farmer Welcome Header -->
        <div class="rounded-[16px] border border-line bg-card p-6 shadow-xs">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xl font-bold text-primary">
                    🌾
                </div>
                <div>
                    <h1 class="font-display text-2xl font-bold text-primary">Halo, {{ auth()->user()->name }}</h1>
                    <p class="mt-1 text-xs text-ink-soft">
                        Petani Mitra SiLeuit.id ·
                        <span class="font-semibold text-ink">{{ auth()->user()->partner?->kelompok ?? 'Kelompok Tani Mitra' }}</span>
                    </p>
                </div>
            </div>
        </div>

        @if ($terakhir)
            <!-- Penerimaan Terakhir Hero Card -->
            <div class="mt-6 space-y-4">
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Penerimaan Terakhir</span>
                        <span class="font-mono text-xs font-bold text-primary">{{ $terakhir->no_dok }}</span>
                    </div>
                    <p class="mt-2 font-display text-3xl font-bold text-primary">
                        {{ number_format((float) $terakhir->netto_kg, 1, ',', '.') }} <span class="text-lg font-normal text-ink-soft">kg</span>
                    </p>
                    <p class="mt-1 text-xs text-ink-soft">
                        Ditimbang di {{ $terakhir->location->nama }} pada {{ $terakhir->waktu_timbang->format('d M Y, H:i') }} WIB
                    </p>
                </div>

                <!-- Mutu & Grade Card (BR-01 & BR-06) -->
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Hasil Uji Mutu &amp; Penetapan Harga</span>
                    @if ($insp && $insp->keputusan !== 'TERIMA')
                        <div class="mt-3 rounded-[10px] bg-danger-bg p-4 border border-danger/30">
                            <p class="text-lg font-bold text-danger">Tidak Diterima ({{ $insp->keputusan }})</p>
                            <p class="mt-1 text-sm text-ink-soft"><strong>Alasan Penolakan:</strong> {{ $insp->alasan }}</p>
                            <p class="mt-2 text-xs text-ink-soft">Gabah tidak memenuhi standar mutu penerimaan (BR-06). Silakan lakukan pengeringan ulang.</p>
                        </div>
                    @else
                        <div class="mt-3 flex items-baseline justify-between">
                            <div>
                                <p class="font-display text-3xl font-bold text-primary">Grade {{ $insp?->grade?->kode ?? 'A' }}</p>
                                <p class="mt-0.5 text-xs text-ink-soft">Sesuai standar mutu SNI Gabah</p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-xl font-bold text-primary">
                                    Rp{{ number_format((int) $terakhir->harga_per_kg, 0, ',', '.') }}<span class="text-xs font-normal text-ink-soft">/kg</span>
                                </p>
                                <p class="text-[11px] text-ink-soft">Terkunci otomatis dari daftar harga</p>
                            </div>
                        </div>

                        <!-- Inspection Specs -->
                        @if ($insp)
                            <div class="mt-4 grid grid-cols-3 gap-2 rounded-[10px] border border-line/60 bg-paper p-3 text-center text-xs">
                                <div>
                                    <p class="text-ink-soft">Kadar Air</p>
                                    <p class="mt-0.5 font-mono font-bold text-primary">{{ $insp->kadar_air }}%</p>
                                </div>
                                <div>
                                    <p class="text-ink-soft">Butir Hampa</p>
                                    <p class="mt-0.5 font-mono font-bold text-primary">{{ $insp->kadar_hampa }}%</p>
                                </div>
                                <div>
                                    <p class="text-ink-soft">Butir Patah</p>
                                    <p class="mt-0.5 font-mono font-bold text-primary">{{ $insp->butir_patah }}%</p>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Status Pembayaran Card (BR-02 & TC-06) -->
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Status Pembayaran</span>
                    @if ($terakhir->status === 'PAID')
                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                <p class="text-lg font-bold text-success flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>LUNAS DIBAYAR</span>
                                </p>
                                <p class="text-xs text-ink-soft">Telah ditransfer oleh staf keuangan</p>
                            </div>
                            <p class="font-mono text-2xl font-bold text-success">
                                Rp{{ number_format((int) $terakhir->nilai_bayar, 0, ',', '.') }}
                            </p>
                        </div>
                    @elseif ((int) $terakhir->nilai_bayar > 0)
                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                <p class="text-base font-bold text-warning">Menunggu Pencairan</p>
                                <p class="text-xs text-ink-soft">Sedang diverifikasi oleh bagian Keuangan</p>
                            </div>
                            <p class="font-mono text-xl font-bold text-ink">
                                Rp{{ number_format((int) $terakhir->nilai_bayar, 0, ',', '.') }}
                            </p>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-ink-soft">Tidak ada pembayaran untuk dokumen ini.</p>
                    @endif
                </div>
            </div>

            <!-- Riwayat Penyerahan -->
            <div class="mt-8">
                <h2 class="font-display text-lg font-bold text-primary">Riwayat Penyerahan Hasil Panen</h2>
                <div class="mt-3 space-y-2.5">
                    @forelse ($riwayat as $r)
                        @php
                            $rBadge = match($r->status) {
                                'PAID' => 'bg-success-bg text-success font-semibold',
                                'CONFIRMED' => 'bg-cream text-primary font-semibold',
                                'REJECTED' => 'bg-danger-bg text-danger font-semibold',
                                default => 'bg-paper text-ink-soft',
                            };
                        @endphp
                        <div class="rounded-[12px] border border-line bg-card p-4 shadow-xs flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-primary">{{ $r->no_dok }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] {{ $rBadge }}">{{ $r->status }}</span>
                                </div>
                                <p class="mt-1 text-xs text-ink-soft">
                                    {{ $r->waktu_timbang->format('d M Y') }} · {{ $r->location->kode }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-bold text-ink">{{ number_format((float) $r->netto_kg, 1, ',', '.') }} kg</p>
                                <p class="text-xs text-ink-soft">{{ $r->nilai_bayar ? 'Rp'.number_format($r->nilai_bayar, 0, ',', '.') : '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-ink-soft">Belum ada riwayat sebelumnya.</p>
                    @endforelse
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="mt-6 rounded-[14px] border border-line bg-card p-8 text-center shadow-xs">
                <span class="text-3xl">🌾</span>
                <p class="mt-3 text-base font-semibold text-primary">Belum Ada Catatan Penerimaan</p>
                <p class="mt-1 text-xs text-ink-soft">
                    Hasil timbang, inspeksi mutu kadar air, dan status pembayaran akan langsung tampil di sini begitu Anda menyerahkan hasil panen ke pos timbang gudang.
                </p>
            </div>
        @endif

        <!-- Logout Button -->
        <form method="POST" action="/logout" class="mt-8">
            @csrf
            <button type="submit" class="h-11 w-full rounded-[10px] border border-line bg-card px-4 text-sm font-semibold text-ink-soft hover:bg-cream hover:text-danger transition">
                Keluar dari Portal
            </button>
        </form>
    </div>
</x-layouts.app>

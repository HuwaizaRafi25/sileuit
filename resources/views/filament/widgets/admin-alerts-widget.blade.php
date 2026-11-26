<x-filament-widgets::widget>
    <div class="overflow-hidden rounded-2xl border border-sileuit-line bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="relative isolate overflow-hidden border-b border-sileuit-line bg-gradient-to-br from-sileuit-paper via-sileuit-paper to-sileuit-cream px-6 py-6 sm:px-8 sm:py-7">
            <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-20 h-64 w-64 rounded-full border border-sileuit-primary/10"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-1 -top-11 h-48 w-48 rounded-full border border-sileuit-primary/10"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-sileuit-line bg-white/80 px-3 py-1.5 text-xs font-semibold tracking-wide text-sileuit-primary-dark">
                        <span class="h-2 w-2 rounded-full bg-sileuit-secondary"></span>
                        SiLeuit.id <span class="text-sileuit-muted/60">/</span> ADMINISTRASI
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sileuit-primary text-2xl text-white shadow-sm">
                            🌾
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold leading-tight tracking-tight text-sileuit-primary-dark sm:text-3xl">
                                Portal Administrasi &amp;<br class="hidden sm:block"> Tata Kelola SiLeuit.id
                            </h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-sileuit-muted sm:text-base">
                                Pusat kendali master data, otorisasi pimpinan, dan integrasi operasional.
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="rounded-full border border-sileuit-line bg-white/70 px-3 py-1.5 text-xs font-medium text-sileuit-ink">Master data</span>
                        <span class="rounded-full border border-sileuit-line bg-white/70 px-3 py-1.5 text-xs font-medium text-sileuit-ink">Kontrol &amp; persetujuan</span>
                        <span class="rounded-full border border-sileuit-line bg-white/70 px-3 py-1.5 text-xs font-medium text-sileuit-ink">Operasional</span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col gap-3 sm:flex-row lg:flex-col lg:items-stretch">
                    <div class="inline-flex items-center gap-2 rounded-xl border border-sileuit-line bg-white/80 px-4 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sileuit-cream text-sm text-sileuit-primary">✓</span>
                        <span>
                            <span class="block text-xs text-sileuit-muted">Area kerja</span>
                            <span class="block text-sm font-semibold text-sileuit-ink">Panel administrasi</span>
                        </span>
                    </div>
                    <a href="/app/beranda" target="_blank" rel="noopener noreferrer"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-sileuit-primary px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sileuit-primary-dark focus:outline-none focus:ring-2 focus:ring-sileuit-secondary focus:ring-offset-2">
                        <span aria-hidden="true">📱</span>
                        <span>Buka Aplikasi Lapangan</span>
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">Ringkasan tindakan</p>
                    <h3 class="mt-1 text-base font-bold text-gray-950 dark:text-white">Persetujuan &amp; perhatian</h3>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold {{ $totalAlerts > 0 ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20' : 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20' }}">
                    <span class="h-2 w-2 rounded-full {{ $totalAlerts > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    {{ $totalAlerts > 0 ? $totalAlerts.' item perlu dicek' : 'Tidak ada tindakan tertunda' }}
                </span>
            </div>

            @if ($totalAlerts > 0)
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @if ($pendingReceipts > 0)
                        <a href="/app/penerimaan" target="_blank" rel="noopener noreferrer"
                            class="group rounded-xl border border-amber-200 bg-amber-50/70 p-4 transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm dark:border-amber-400/20 dark:bg-amber-400/5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-lg dark:bg-amber-400/10">⚖️</span>
                            <span class="mt-3 block text-2xl font-bold text-amber-950 dark:text-amber-200">{{ $pendingReceipts }}</span>
                            <span class="mt-1 block text-sm font-semibold text-amber-900 dark:text-amber-100">Penerimaan menunggu persetujuan</span>
                            <span class="mt-3 block text-xs font-semibold text-amber-700 group-hover:underline dark:text-amber-300">Periksa penerimaan →</span>
                        </a>
                    @endif
                    @if ($pendingAdjustments > 0 && auth()->user()?->can('lihat-penyesuaian'))
                        <a href="/app/penyesuaian" target="_blank" rel="noopener noreferrer"
                            class="group rounded-xl border border-amber-200 bg-amber-50/70 p-4 transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm dark:border-amber-400/20 dark:bg-amber-400/5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-lg dark:bg-amber-400/10">📦</span>
                            <span class="mt-3 block text-2xl font-bold text-amber-950 dark:text-amber-200">{{ $pendingAdjustments }}</span>
                            <span class="mt-1 block text-sm font-semibold text-amber-900 dark:text-amber-100">Penyesuaian stok menunggu otorisasi</span>
                            <span class="mt-3 block text-xs font-semibold text-amber-700 group-hover:underline dark:text-amber-300">Periksa penyesuaian →</span>
                        </a>
                    @endif
                    @if ($pendingReturns > 0 && auth()->user()?->can('lihat-retur'))
                        <a href="/app/retur" target="_blank" rel="noopener noreferrer"
                            class="group rounded-xl border border-amber-200 bg-amber-50/70 p-4 transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm dark:border-amber-400/20 dark:bg-amber-400/5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-lg dark:bg-amber-400/10">↩️</span>
                            <span class="mt-3 block text-2xl font-bold text-amber-950 dark:text-amber-200">{{ $pendingReturns }}</span>
                            <span class="mt-1 block text-sm font-semibold text-amber-900 dark:text-amber-100">Permintaan retur belum diproses</span>
                            <span class="mt-3 block text-xs font-semibold text-amber-700 group-hover:underline dark:text-amber-300">Periksa retur →</span>
                        </a>
                    @endif
                    @if ($lotMendekati > 0)
                        <a href="/app/stok" target="_blank" rel="noopener noreferrer"
                            class="group rounded-xl border border-red-200 bg-red-50/70 p-4 transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-sm dark:border-red-400/20 dark:bg-red-400/5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-lg dark:bg-red-400/10">⏳</span>
                            <span class="mt-3 block text-2xl font-bold text-red-950 dark:text-red-200">{{ $lotMendekati }}</span>
                            <span class="mt-1 block text-sm font-semibold text-red-900 dark:text-red-100">Lot mendekati batas umur simpan</span>
                            <span class="mt-3 block text-xs font-semibold text-red-700 group-hover:underline dark:text-red-300">Periksa stok →</span>
                        </a>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center gap-3 py-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-xl text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">✓</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Semua terlihat terkendali</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Belum ada persetujuan atau peringatan stok yang membutuhkan tindakan.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>

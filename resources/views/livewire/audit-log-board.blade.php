<div class="mx-auto max-w-6xl px-4 py-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
            <h1 class="mt-1 font-display text-2xl font-bold text-primary">Audit Trail &amp; Log Sistem</h1>
            <p class="text-xs text-ink-soft">Jejak audit permanen (immutable) seluruh transaksi dan otorisasi pengguna</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-[8px] bg-cream px-3 py-1.5 text-xs font-semibold text-primary">
                Kepatuhan ISO / SOX &amp; BR-12
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="mt-6 rounded-[14px] border border-line bg-card p-4 shadow-xs">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input wire:model.live.debounce.300ms="q" type="text" placeholder="Cari aksi (PENERIMAAN_...), entitas, nama staf..."
                class="h-11 flex-1 rounded-[10px] border border-line bg-paper px-3.5 text-sm text-ink focus:border-primary focus:outline-none">
            <select wire:model.live="aksi" class="h-11 rounded-[10px] border border-line bg-paper px-3 text-sm focus:border-primary focus:outline-none font-semibold">
                <option value="">— Semua Jenis Aksi —</option>
                @foreach ($aksiList as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="mt-6 overflow-hidden rounded-[14px] border border-line bg-card shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-cream/60 text-left text-xs font-semibold text-ink-soft uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5">Waktu Transaksi</th>
                        <th class="p-3.5">Pelaku (User)</th>
                        <th class="p-3.5">Aksi Sistem</th>
                        <th class="p-3.5">Entitas Terkait</th>
                        <th class="p-3.5">IP Address</th>
                        <th class="p-3.5">Perubahan Data (State)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/50">
                    @forelse ($logs as $l)
                        <tr wire:key="audit-row-{{ $l->id }}" class="hover:bg-paper/80 transition text-xs">
                            <td class="p-3.5 font-mono text-ink-soft whitespace-nowrap">{{ $l->waktu->format('d M Y, H:i:s') }}</td>
                            <td class="p-3.5 font-semibold text-ink whitespace-nowrap">{{ $l->user?->name ?? 'SYSTEM' }}</td>
                            <td class="p-3.5">
                                <span class="rounded-[6px] bg-paper border border-line px-2 py-0.5 font-mono font-bold text-primary">
                                    {{ $l->aksi }}
                                </span>
                            </td>
                            <td class="p-3.5 font-mono text-ink whitespace-nowrap">{{ $l->entitas ? $l->entitas.'#'.$l->entitas_id : 'Akses Sistem' }}</td>
                            <td class="p-3.5 font-mono text-ink-soft whitespace-nowrap">{{ $l->ip ?? '—' }}</td>
                            @php
                                $detailAudit = $l->nilai_baru ?? $l->nilai_lama;
                                $detailJson = $detailAudit ? json_encode($detailAudit, JSON_UNESCAPED_UNICODE) : '';
                            @endphp
                            <td class="p-3.5 font-mono text-[11px] text-ink-soft max-w-xs truncate" title="{{ $detailJson }}">
                                {{ $detailJson !== '' ? $detailJson : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-ink-soft">
                                Tidak ada log audit yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>

<x-layouts.app title="Stok — SiLeuit.id">
    <div class="mx-auto max-w-3xl px-4 py-6">
        <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
        <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Stok per lot</h1>
        <div class="mt-4 space-y-3">
            @forelse ($saldo as $s)
                @php $persen = $s->lot->persenUmur(); @endphp
                <a href="/app/stok/{{ $s->lot_id }}" class="block rounded-[12px] border border-line bg-card p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm font-semibold text-primary">{{ $s->lot->kode_lot }}</span>
                        <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $s->location->kode }} · {{ $s->lot->status }}</span>
                    </div>
                    <p class="mt-1 text-base">{{ $s->lot->commodity->nama }} {{ $s->lot->grade?->kode }} · terima {{ $s->lot->tgl_terima->format('d M Y') }}</p>
                    <p class="text-sm text-ink-soft">
                        On-hand {{ number_format((float) $s->on_hand_kg, 3, ',', '.') }} kg ·
                        Reserved {{ number_format((float) $s->reserved_kg, 3, ',', '.') }} kg ·
                        <span class="font-semibold text-primary">Tersedia {{ number_format($s->tersediaKg(), 3, ',', '.') }} kg</span>
                    </p>
                    @if ($s->lot->status === 'KEDALUWARSA' || $persen >= 100)
                        <p class="mt-2 rounded-[8px] bg-danger-bg px-2 py-1 text-sm text-danger">Diblokir dari alokasi normal</p>
                    @elseif ($persen >= 80)
                        <p class="mt-2 rounded-[8px] bg-warning-bg px-2 py-1 text-sm text-warning">Umur simpan {{ $persen }}% — prioritaskan keluar</p>
                    @endif
                </a>
            @empty
                <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada stok di gudang ini. Buat transfer dari gudang lain atau catat penerimaan.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>

<x-layouts.app title="Portal Petani — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-8">
        <h1 class="font-display text-2xl font-semibold text-primary">Halo, {{ auth()->user()->name }}</h1>
        @if ($terakhir)
            <div class="mt-4 space-y-3">
                <div class="rounded-[12px] border border-line bg-card p-5">
                    <p class="text-sm text-ink-soft">Penerimaan terakhir</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-primary">{{ number_format((float) $terakhir->netto_kg, 3, ',', '.') }} kg</p>
                    <p class="text-sm text-ink-soft">{{ $terakhir->waktu_timbang->format('d M Y') }}</p>
                </div>
                <div class="rounded-[12px] border border-line bg-card p-5">
                    <p class="text-sm text-ink-soft">Grade &amp; harga</p>
                    @if ($insp && $insp->keputusan !== 'TERIMA')
                        <p class="mt-1 text-xl font-semibold text-danger">Tidak diterima ({{ $insp->keputusan }})</p>
                        <p class="mt-1 text-base text-ink-soft">{{ $insp->alasan }}</p>
                    @else
                        <p class="mt-1 font-display text-3xl font-semibold text-primary">{{ $insp?->grade?->kode ?? '—' }}</p>
                        <p class="text-base">Rp{{ number_format((int) $terakhir->harga_per_kg, 0, ',', '.') }}/kg</p>
                    @endif
                </div>
                <div class="rounded-[12px] border border-line bg-card p-5">
                    <p class="text-sm text-ink-soft">Status bayar</p>
                    @if ($terakhir->status === 'PAID')
                        <p class="mt-1 text-xl font-semibold text-success">LUNAS · Rp{{ number_format((int) $terakhir->nilai_bayar, 0, ',', '.') }}</p>
                    @elseif ((int) $terakhir->nilai_bayar > 0)
                        <p class="mt-1 text-xl font-semibold text-warning">Belum dibayar</p>
                    @else
                        <p class="mt-1 text-xl font-semibold text-ink-soft">—</p>
                    @endif
                </div>
            </div>
            <h2 class="mt-6 font-display text-lg font-semibold text-primary">Riwayat</h2>
            <div class="mt-2 space-y-2">
                @foreach ($riwayat as $r)
                    <div class="rounded-[12px] border border-line bg-card p-3">
                        <p class="font-mono text-xs text-ink-soft">{{ $r->no_dok }}</p>
                        <p class="text-base">{{ number_format((float) $r->netto_kg, 3, ',', '.') }} kg · {{ $r->status }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-4 rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada penerimaan. Hasil timbang dan mutu akan tampil di sini setelah Anda menyerahkan hasil panen.</p>
        @endif
        <form method="POST" action="/logout" class="mt-6">
            @csrf
            <button type="submit" class="h-12 w-full rounded-[10px] border border-line px-3 text-base font-medium text-ink-soft">Keluar</button>
        </form>
    </div>
</x-layouts.app>

<div class="mx-auto max-w-3xl px-4 py-6">
    <div class="rounded-[12px] border border-line bg-card p-5">
        <h1 class="font-display text-2xl font-semibold text-primary">{{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-ink-soft">
            @foreach (auth()->user()->roles as $role)
                <span class="mr-1 inline-block rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $role->nama }}</span>
            @endforeach
            · {{ auth()->user()->activeLocation?->nama ?? 'Semua lokasi' }}
        </p>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
        @can('lihat-stok')
            <a href="/app/stok" class="rounded-[12px] border border-line bg-card p-4">
                <p class="text-xs text-ink-soft">Stok on-hand</p>
                <p class="mt-1 font-display text-xl font-semibold text-primary">{{ number_format($stokKg, 1, ',', '.') }} kg</p>
            </a>
        @endcan
        @can('lihat-stok')
            <div class="rounded-[12px] border border-line bg-card p-4">
                <p class="text-xs text-ink-soft">Lot ≥ 80% umur simpan</p>
                <p class="mt-1 font-display text-xl font-semibold {{ $lotMendekati > 0 ? 'text-warning' : 'text-primary' }}">{{ $lotMendekati }}</p>
            </div>
        @endcan
        <a href="/app/penerimaan" class="rounded-[12px] border border-line bg-card p-4">
            <p class="text-xs text-ink-soft">Penerimaan hari ini</p>
            <p class="mt-1 font-display text-xl font-semibold text-primary">{{ $terimaHariIni }}</p>
        </a>
        <a href="/app/pesanan" class="rounded-[12px] border border-line bg-card p-4">
            <p class="text-xs text-ink-soft">Pesanan aktif</p>
            <p class="mt-1 font-display text-xl font-semibold text-primary">{{ $pesananAktif }}</p>
        </a>
        @can('lihat-invoice')
            <a href="/app/invoice" class="rounded-[12px] border border-line bg-card p-4">
                <p class="text-xs text-ink-soft">Piutang berjalan</p>
                <p class="mt-1 font-display text-xl font-semibold text-primary">Rp{{ number_format($piutang, 0, ',', '.') }}</p>
            </a>
        @endcan
        <a href="/app/pengiriman" class="rounded-[12px] border border-line bg-card p-4">
            <p class="text-xs text-ink-soft">Perlu persetujuan</p>
            <p class="mt-1 font-display text-xl font-semibold {{ $pendingApprovals > 0 ? 'text-warning' : 'text-primary' }}">{{ $pendingApprovals }}</p>
        </a>
    </div>

    @can('lihat-laporan')
        @if (count($aging['buckets'] ?? []) > 0)
            <div class="mt-5 rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-lg font-semibold text-primary">Aging piutang</h2>
                <div class="mt-2 grid grid-cols-5 gap-2 text-center text-xs">
                    @foreach ($aging['buckets'] as $b => $n)
                        <div class="rounded-[8px] bg-cream p-2"><p class="font-semibold">{{ $b }}</p><p>Rp{{ number_format($n, 0, ',', '.') }}</p></div>
                    @endforeach
                </div>
            </div>
        @endif
    @endcan

    <div class="mt-5 grid grid-cols-2 gap-3">
        @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class) <a href="/app/terima" class="rounded-[10px] bg-primary p-3 text-center text-sm font-semibold text-white">+ Terima gabah</a> @endcan
        @can('pos-jual') <a href="/app/pos" class="rounded-[10px] bg-primary p-3 text-center text-sm font-semibold text-white">Buka kasir</a> @endcan
        @can('lihat-fulfillment') <a href="/app/pengiriman" class="rounded-[10px] border border-line p-3 text-center text-sm font-medium text-primary">Pengiriman</a> @endcan
        @can('buat-transfer') <a href="/app/transfer" class="rounded-[10px] border border-line p-3 text-center text-sm font-medium text-primary">Transfer</a> @endcan
    </div>

    <form method="POST" action="/logout" class="mt-6">
        @csrf
        <button type="submit" class="h-12 w-full rounded-[10px] border border-danger px-3 text-base font-medium text-danger">Keluar</button>
    </form>
</div>

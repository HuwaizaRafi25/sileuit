<x-layouts.app title="Beranda — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-8">
        <div class="rounded-[12px] border border-line bg-card p-6">
            <h1 class="font-display text-2xl font-semibold text-primary">{{ auth()->user()->name }}</h1>
            <p class="mt-2 text-base text-ink-soft">
                @foreach (auth()->user()->roles as $role)
                    <span class="mr-1 inline-block rounded-[8px] bg-cream px-2 py-0.5 text-sm text-primary">{{ $role->nama }}</span>
                @endforeach
            </p>
            <p class="mt-1 text-base text-ink-soft">Lokasi: {{ auth()->user()->activeLocation?->nama ?? 'Semua lokasi' }}</p>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3">
            @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                <a href="/app/terima" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Terima gabah</a>
            @endcan
            <a href="/app/penerimaan" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Penerimaan</a>
            @can('lihat-stok')
                <a href="/app/stok" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Stok &amp; kartu stok</a>
            @endcan
            @can('buat-transfer')
                <a href="/app/transfer" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Transfer</a>
            @endcan
            @can('buat-penyesuaian')
                <a href="/app/penyesuaian" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Penyesuaian</a>
            @endcan
            @can('buat-pesanan')
                <a href="/app/pesanan" class="rounded-[12px] border border-line bg-card p-4 text-base font-medium text-primary">Pesanan</a>
            @endcan
        </div>
        <form method="POST" action="/logout" class="mt-6">
            @csrf
            <button type="submit" class="h-12 w-full rounded-[10px] border border-danger px-3 text-base font-medium text-danger">Keluar</button>
        </form>
    </div>
</x-layouts.app>

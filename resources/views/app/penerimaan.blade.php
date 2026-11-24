<x-layouts.app title="Penerimaan — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-6">
        <div class="flex items-center justify-between">
            <h1 class="font-display text-2xl font-semibold text-primary">Penerimaan</h1>
            @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                <a href="/app/terima" class="h-11 rounded-[10px] bg-primary px-4 text-sm font-semibold leading-[44px] text-white">+ Terima</a>
            @endcan
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($daftar as $r)
                <a href="/app/penerimaan/{{ $r->id }}" class="block rounded-[12px] border border-line bg-card p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm font-semibold text-primary">{{ $r->no_dok }}</span>
                        <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $r->status }}</span>
                    </div>
                    <p class="mt-1 text-base">{{ $r->contract->partner->nama }}</p>
                    <p class="text-sm text-ink-soft">{{ number_format((float) $r->netto_kg, 3, ',', '.') }} kg · {{ $r->location->kode }} · {{ $r->waktu_timbang->format('d M Y H:i') }}</p>
                </a>
            @empty
                <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada penerimaan. Mulai dengan mencatat penerimaan pertama.</p>
            @endforelse
        </div>
        {{ $daftar->links() }}
    </div>
</x-layouts.app>

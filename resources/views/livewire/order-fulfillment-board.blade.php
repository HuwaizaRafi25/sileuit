<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Pemenuhan pesanan</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('aksi') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <h2 class="mt-4 font-display text-lg font-semibold text-primary">Pesanan</h2>
    <div class="mt-2 space-y-3">
        @forelse ($pesanan as $o)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $o->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $o->status }}</span>
                </div>
                @foreach ($o->lines as $l)
                    <p class="mt-1 text-base">{{ $l->commodity->nama }} {{ $l->grade->kode }} · {{ number_format((float) $l->qty_kg, 1, ',', '.') }} kg</p>
                @endforeach
                @if ($o->status === 'ALLOCATED')
                    <button wire:click="pick({{ $o->id }})" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Picking &amp; buat surat jalan</button>
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada pesanan siap proses.</p>
        @endforelse
    </div>

    <h2 class="mt-5 font-display text-lg font-semibold text-primary">Pengiriman</h2>
    <div class="mt-2 space-y-3">
        @forelse ($pengiriman as $s)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $s->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $s->status }}</span>
                </div>
                <p class="mt-1 text-base">Kirim {{ number_format((float) $s->jumlah_dikirim_kg, 1, ',', '.') }} kg · {{ $s->order->no_dok }}</p>
                @if ($s->status === 'MENUNGGU')
                    <button wire:click="kirim({{ $s->id }})" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Mulai kirim</button>
                @elseif ($s->status === 'DIKIRIM')
                    <input wire:model="diterima.{{ $s->id }}" type="number" step="0.001" placeholder="kg diterima" class="mt-3 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                    <button wire:click="terima({{ $s->id }})" class="mt-2 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Konfirmasi terima</button>
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada pengiriman berjalan.</p>
        @endforelse
    </div>
</div>

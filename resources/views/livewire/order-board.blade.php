<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Pesanan &amp; alokasi FEFO</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('alokasi') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Buat pesanan</h2>
        <div class="mt-3 grid gap-3">
            <select wire:model.live="commodity_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— komoditas —</option>
                @foreach ($komoditas as $c) <option value="{{ $c->id }}">{{ $c->nama }}</option> @endforeach
            </select>
            <select wire:model="grade_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— grade —</option>
                @foreach ($grades->where('commodity_id', (int) $commodity_id) as $g) <option value="{{ $g->id }}">{{ $g->kode }}</option> @endforeach
            </select>
            <input wire:model="qty" type="number" step="0.001" placeholder="Jumlah kg" class="h-12 rounded-[10px] border border-line px-3 text-base">
            @error('qty') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <button wire:click="buat" class="h-12 rounded-[10px] bg-primary text-base font-semibold text-white">Buat pesanan</button>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($daftar as $o)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $o->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $o->status }}</span>
                </div>
                @foreach ($o->lines as $line)
                    <p class="mt-1 text-base">{{ $line->commodity->nama }} {{ $line->grade->kode }} · {{ number_format((float) $line->qty_kg, 3, ',', '.') }} kg (terpenuhi {{ number_format((float) $line->qty_terpenuhi, 3, ',', '.') }})</p>
                    @foreach ($line->reservations as $r)
                        <p class="text-sm text-ink-soft">↳ {{ $r->lot->kode_lot }} · {{ number_format((float) $r->qty_kg, 3, ',', '.') }} kg {{ $r->override_kedaluwarsa ? '(override kedaluwarsa)' : '' }}</p>
                    @endforeach
                @endforeach
                @if (in_array($o->status, ['NEW','PARTIAL','BACKORDER']) && auth()->user()->can('alokasi-pesanan'))
                    <div class="mt-3 flex gap-3">
                        <button wire:click="alokasi({{ $o->id }}, false)" class="h-12 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white">Alokasi FEFO</button>
                        @if (auth()->user()->hasRole('KEPALA_GUDANG'))
                            <button wire:click="alokasi({{ $o->id }}, true)" class="h-12 flex-1 rounded-[10px] border border-warning text-base font-medium text-warning">Setujui lot kedaluwarsa</button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada pesanan.</p>
        @endforelse
    </div>
</div>

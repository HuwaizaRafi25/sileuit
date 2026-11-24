<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Transfer antar-gudang</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('kirim') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror
    @error('terima') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Buat transfer dari {{ auth()->user()->activeLocation?->kode }}</h2>
        <div class="mt-3 grid gap-3">
            <select wire:model="ke_lokasi" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— lokasi tujuan —</option>
                @foreach ($lokasi as $l) <option value="{{ $l->id }}">{{ $l->nama }}</option> @endforeach
            </select>
            @error('ke_lokasi') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <select wire:model="lot_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— pilih lot —</option>
                @foreach ($stok as $s)
                    <option value="{{ $s->lot_id }}">{{ $s->lot->kode_lot }} · tersedia {{ number_format($s->tersediaKg(), 3, ',', '.') }} kg</option>
                @endforeach
            </select>
            @error('lot_id') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <input wire:model="kg" type="number" step="0.001" placeholder="Jumlah kg" class="h-12 rounded-[10px] border border-line px-3 text-base">
            @error('kg') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <button wire:click="buat" class="h-12 rounded-[10px] bg-primary text-base font-semibold text-white">Buat transfer</button>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($daftar as $t)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $t->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $t->status }}</span>
                </div>
                <p class="mt-1 text-base">{{ $t->asal->kode }} → {{ $t->tujuan->kode }}</p>
                @foreach ($t->lines as $line)
                    <p class="text-sm text-ink-soft">
                        {{ $line->lot->kode_lot }} · kirim {{ number_format((float) $line->kg_kirim, 3, ',', '.') }} kg
                        @if ($line->kg_terima !== null)
                            · terima {{ number_format((float) $line->kg_terima, 3, ',', '.') }} kg
                            @if ((float) $line->selisih_kg !== 0.0)
                                <span class="text-danger">· selisih {{ number_format((float) $line->selisih_kg, 3, ',', '.') }} kg</span>
                            @endif
                        @endif
                    </p>
                    @if ($t->status === 'SHIPPED' && auth()->user()->can('terima-transfer') && auth()->user()->locationScopes->contains('id', $t->ke_lokasi))
                        <input wire:model="terima.{{ $line->id }}" type="number" step="0.001" placeholder="kg diterima"
                            class="mt-2 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                    @endif
                @endforeach
                @if ($t->status === 'DRAFT')
                    <button wire:click="kirim({{ $t->id }})" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Kirim</button>
                @elseif ($t->status === 'SHIPPED' && auth()->user()->can('terima-transfer') && auth()->user()->locationScopes->contains('id', $t->ke_lokasi))
                    <button wire:click="terimaBarang({{ $t->id }})" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Catat penerimaan</button>
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada transfer.</p>
        @endforelse
    </div>
</div>

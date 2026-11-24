<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Penyesuaian stok</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('proses') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Ajukan penyesuaian</h2>
        <div class="mt-3 grid gap-3">
            <select wire:model.live="tipe" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="PENYESUAIAN">Penyesuaian (opname/susut)</option>
                <option value="SALDO_AWAL">Saldo awal cutover</option>
            </select>
            @if ($tipe === 'PENYESUAIAN')
                <select wire:model="lot_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                    <option value="">— pilih lot —</option>
                    @foreach ($stok as $s) <option value="{{ $s->lot_id }}">{{ $s->lot->kode_lot }} ({{ number_format((float) $s->on_hand_kg, 3, ',', '.') }} kg)</option> @endforeach
                </select>
                @error('lot_id') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @else
                <select wire:model.live="commodity_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                    <option value="">— komoditas —</option>
                    @foreach ($komoditas as $c) <option value="{{ $c->id }}">{{ $c->nama }}</option> @endforeach
                </select>
                <select wire:model="grade_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                    <option value="">— grade —</option>
                    @foreach ($grades->where('commodity_id', (int) $commodity_id) as $g) <option value="{{ $g->id }}">{{ $g->kode }}</option> @endforeach
                </select>
                @error('commodity_id') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @endif
            <input wire:model="qty" type="number" step="0.001" placeholder="kg (negatif = pengurangan)" class="h-12 rounded-[10px] border border-line px-3 text-base">
            @error('qty') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <textarea wire:model="alasan" rows="2" placeholder="Alasan wajib" class="rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
            @error('alasan') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <button wire:click="buat" class="h-12 rounded-[10px] bg-primary text-base font-semibold text-white">Ajukan</button>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($daftar as $a)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $a->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $a->status }}</span>
                </div>
                <p class="mt-1 text-base">{{ $a->tipe }} · {{ number_format((float) $a->qty_kg, 3, ',', '.') }} kg {{ $a->lot?->kode_lot ?? ($a->commodity?->nama.' '.$a->grade?->kode) }}</p>
                <p class="text-sm text-ink-soft">{{ $a->alasan }}</p>
                @if ($a->status === 'MENUNGGU')
                    @php $boleh = $a->tipe === 'SALDO_AWAL' ? auth()->user()->hasRole('PEMILIK') : (auth()->user()->hasRole('KEPALA_GUDANG') && auth()->user()->locationScopes->contains('id', $a->location_id)); @endphp
                    @if ($boleh)
                        <div class="mt-3 flex gap-3">
                            <button wire:click="proses({{ $a->id }}, false)" class="h-12 flex-1 rounded-[10px] border border-danger text-base font-medium text-danger">Tolak</button>
                            <button wire:click="proses({{ $a->id }}, true)" class="h-12 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white">Setujui</button>
                        </div>
                    @endif
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada penyesuaian.</p>
        @endforelse
    </div>
</div>

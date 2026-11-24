<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Kasir — {{ auth()->user()->activeLocation?->nama }}</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('checkout') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Tambah produk</h2>
        <div class="mt-3 grid gap-3">
            <select wire:model.live="commodity_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— komoditas —</option>
                @foreach ($komoditas as $c) <option value="{{ $c->id }}">{{ $c->nama }}</option> @endforeach
            </select>
            <select wire:model="grade_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— grade —</option>
                @foreach ($grades->where('commodity_id', (int) $commodity_id) as $g) <option value="{{ $g->id }}">{{ $g->kode }}</option> @endforeach
            </select>
            <div class="flex gap-3">
                <input wire:model="qty" type="number" step="0.001" placeholder="kg" class="h-12 flex-1 rounded-[10px] border border-line px-3 text-base">
                <input wire:model="harga" type="number" step="1" placeholder="harga/kg" class="h-12 flex-1 rounded-[10px] border border-line px-3 text-base">
            </div>
            @error('qty') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @error('harga') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <button wire:click="tambah" class="h-12 rounded-[10px] bg-primary text-base font-semibold text-white">Tambah ke nota</button>
        </div>
        <p class="mt-3 text-sm text-ink-soft">Stok cabang: @foreach ($stok as $s) {{ $s->lot->kode_lot }} ({{ number_format($s->tersediaKg(), 1, ',', '.') }} kg) · @endforeach</p>
    </div>

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Nota</h2>
        @forelse ($lines as $i => $l)
            <div class="mt-2 flex items-center justify-between border-b border-line pb-2 text-base">
                <span>Kom #{{ $l['commodity_id'] }} G#{{ $l['grade_id'] }} · {{ $l['qty_kg'] }} kg × Rp{{ number_format($l['harga_per_kg'], 0, ',', '.') }}</span>
                <button wire:click="hapus({{ $i }})" class="text-danger">✕</button>
            </div>
        @empty
            <p class="mt-2 text-base text-ink-soft">Pilih produk untuk memulai penjualan.</p>
        @endforelse
        <select wire:model="metode" class="mt-3 h-12 w-full rounded-[10px] border border-line px-3 text-base">
            <option value="TUNAI">Tunai</option><option value="TRANSFER">Transfer</option>
        </select>
        <button wire:click="checkout" wire:loading.attr="disabled" class="mt-3 h-14 w-full rounded-[10px] bg-primary text-base font-semibold text-white disabled:opacity-50">Bayar &amp; cetak nota</button>
    </div>
</div>

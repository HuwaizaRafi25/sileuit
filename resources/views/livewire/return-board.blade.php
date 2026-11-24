<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Retur</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('retur') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Ajukan retur</h2>
        <div class="mt-3 grid gap-3">
            <select wire:model="ref_tipe" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="SHIPMENT">Pengiriman</option><option value="SALE">Nota kasir</option>
            </select>
            <select wire:model="ref_id" class="h-12 rounded-[10px] border border-line px-3 text-base">
                <option value="">— dokumen asal —</option>
                @foreach ($pengiriman as $s) <option value="{{ $s->id }}">{{ $s->no_dok }} ({{ number_format((float) $s->jumlah_dikirim_kg, 1, ',', '.') }} kg)</option> @endforeach
            </select>
            <input wire:model="kg" type="number" step="0.001" placeholder="kg retur" class="h-12 rounded-[10px] border border-line px-3 text-base">
            <textarea wire:model="alasan" rows="2" placeholder="Alasan" class="rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
            <button wire:click="ajukan" class="h-12 rounded-[10px] bg-primary text-base font-semibold text-white">Ajukan retur</button>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($daftar as $r)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $r->no_dok }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $r->status }} {{ $r->hasil ? '· '.$r->hasil : '' }}</span>
                </div>
                <p class="mt-1 text-base">{{ $r->ref_tipe }}#{{ $r->ref_id }} · {{ number_format((float) $r->kg, 1, ',', '.') }} kg</p>
                <p class="text-sm text-ink-soft">{{ $r->alasan }}</p>
                @if ($r->status === 'REQUESTED' && auth()->user()->can('retur-approve'))
                    <div class="mt-3 flex gap-3">
                        <button wire:click="proses({{ $r->id }}, false)" class="h-12 flex-1 rounded-[10px] border border-danger text-base font-medium text-danger">Tolak</button>
                        <button wire:click="proses({{ $r->id }}, true)" class="h-12 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white">Setujui</button>
                    </div>
                @elseif ($r->status === 'APPROVED')
                    <select wire:model="hasil" class="mt-3 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                        <option value="STOK_KEMBALI">Kembali ke stok</option><option value="KARANTINA">Karantina</option><option value="DIHAPUS">Dihapus</option>
                    </select>
                    <button wire:click="terimaBarang({{ $r->id }})" class="mt-2 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Terima barang</button>
                @endif
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada retur.</p>
        @endforelse
    </div>
</div>

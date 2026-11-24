<div class="mx-auto max-w-3xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Invoice &amp; piutang</h1>

    @if ($pesan) <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p> @endif
    @error('terbit') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror
    @error('bayar') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror

    @can('terbit-invoice')
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-lg font-semibold text-primary">Terbitkan invoice</h2>
                <select wire:model="order_id" class="mt-3 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                    <option value="">— pesanan DELIVERED —</option>
                    @foreach ($pesananSiap as $o)
                        <option value="{{ $o->id }}">{{ $o->no_dok }} · {{ $o->partner->nama }}</option>
                    @endforeach
                </select>
                <button wire:click="terbitkan" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Terbitkan</button>
            </div>

            <div class="rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-lg font-semibold text-primary">Catat pembayaran</h2>
                <select wire:model="partner_id" class="mt-3 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                    <option value="">— mitra —</option>
                    @foreach ($mitra as $m) <option value="{{ $m->id }}">{{ $m->nama }}</option> @endforeach
                </select>
                <input wire:model="jumlah" type="number" placeholder="jumlah rupiah" class="mt-2 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                <input wire:model="referensi" type="text" placeholder="referensi bukti" class="mt-2 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                <button wire:click="catatBayar" class="mt-3 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white">Catat &amp; alokasikan</button>
            </div>
        </div>
    @endcan

    <div class="mt-5 rounded-[12px] border border-line bg-card p-5">
        <h2 class="font-display text-lg font-semibold text-primary">Aging piutang</h2>
        <p class="mt-2 text-sm text-ink-soft">Total: Rp{{ number_format($aging['total'], 0, ',', '.') }}</p>
        <div class="mt-2 grid grid-cols-5 gap-2 text-center text-xs">
            @foreach ($aging['buckets'] as $bucket => $nilai)
                <div class="rounded-[8px] bg-cream p-2">
                    <p class="font-semibold">{{ $bucket }}</p>
                    <p>Rp{{ number_format($nilai, 0, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($daftar as $inv)
            <div class="rounded-[12px] border border-line bg-card p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-sm font-semibold text-primary">{{ $inv->no }}</span>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs text-primary">{{ $inv->status() }}</span>
                </div>
                <p class="mt-1 text-base">{{ $inv->partner->nama }} · jatuh tempo {{ $inv->jatuh_tempo->format('d M Y') }}</p>
                <p class="text-sm text-ink-soft">
                    Total Rp{{ number_format($inv->total, 0, ',', '.') }} ·
                    Dibayar Rp{{ number_format($inv->dibayar(), 0, ',', '.') }} ·
                    <span class="font-semibold text-primary">Saldo Rp{{ number_format($inv->saldo(), 0, ',', '.') }}</span>
                </p>
            </div>
        @empty
            <p class="rounded-[12px] border border-line bg-card p-5 text-base text-ink-soft">Belum ada invoice.</p>
        @endforelse
    </div>
</div>

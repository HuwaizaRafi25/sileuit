<div class="mx-auto max-w-xl px-4 py-6">
    <a href="/app/penerimaan" class="text-sm text-primary">← Daftar penerimaan</a>
    <div class="mt-3 rounded-[12px] border border-line bg-card p-5">
        <div class="flex items-center justify-between">
            <h1 class="font-mono text-lg font-semibold text-primary">{{ $receipt->no_dok }}</h1>
            <span class="rounded-[8px] bg-cream px-2 py-1 text-sm text-primary">{{ $receipt->status }}</span>
        </div>
        <dl class="mt-4 space-y-2 text-base">
            <div class="flex justify-between"><dt class="text-ink-soft">Petani</dt><dd>{{ $receipt->contract->partner->nama }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Gudang</dt><dd>{{ $receipt->location->nama }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Netto</dt><dd class="font-semibold">{{ number_format((float) $receipt->netto_kg, 3, ',', '.') }} kg</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Grade</dt><dd>{{ $insp?->grade?->kode ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Harga/kg</dt><dd>{{ $receipt->harga_per_kg ? 'Rp'.number_format($receipt->harga_per_kg, 0, ',', '.') : '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Nilai bayar</dt><dd class="font-semibold">{{ $receipt->nilai_bayar ? 'Rp'.number_format($receipt->nilai_bayar, 0, ',', '.') : '—' }}</dd></div>
            @if ($receipt->selisih_persen !== null)
                <div class="flex justify-between"><dt class="text-ink-soft">Selisih</dt><dd>{{ $receipt->selisih_persen }}%</dd></div>
            @endif
        </dl>
        @if ($insp?->alasan)
            <p class="mt-3 rounded-[10px] bg-warning-bg px-3 py-2 text-sm text-warning">{{ $insp->keputusan }}: {{ $insp->alasan }}</p>
        @endif
        @if ($foto)
            <img src="{{ $foto }}" alt="Bukti inspeksi" class="mt-4 w-full rounded-[10px] border border-line">
        @endif
        @foreach ($receipt->lots as $lot)
            <p class="mt-3 font-mono text-sm text-primary">{{ $lot->kode_lot }} · {{ $lot->status }}</p>
        @endforeach
    </div>

    @if ($pesan)
        <p class="mt-3 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">{{ $pesan }}</p>
    @endif

    @if ($receipt->status === 'MENUNGGU_PERSETUJUAN' && auth()->user()->can('approve', $receipt))
        <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-lg font-semibold text-primary">Persetujuan selisih</h2>
            <textarea wire:model="alasan_setuju" rows="2" placeholder="Alasan wajib" class="mt-3 w-full rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
            @error('alasan_setuju') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            <div class="mt-3 flex gap-3">
                <button wire:click="tolak" class="h-12 flex-1 rounded-[10px] border border-danger text-base font-medium text-danger">Tolak</button>
                <button wire:click="setujui" class="h-12 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white">Setujui</button>
            </div>
        </div>
    @endif

    @if ($receipt->status === 'CONFIRMED' && auth()->user()->can('pay', $receipt))
        <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-lg font-semibold text-primary">Bayar petani</h2>
            <label class="mt-3 mb-1 block text-sm font-medium">Metode</label>
            <select wire:model="metode" class="h-12 w-full rounded-[10px] border border-line px-3 text-base">
                <option value="TUNAI">Tunai</option>
                <option value="TRANSFER">Transfer</option>
            </select>
            <label class="mt-3 mb-1 block text-sm font-medium">Nomor bukti transfer / tanda terima</label>
            <input wire:model="referensi" type="text" class="h-12 w-full rounded-[10px] border border-line px-3 text-base">
            @error('referensi') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            <button wire:click="bayar" wire:loading.attr="disabled" class="mt-4 h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white disabled:opacity-50">
                Catat pembayaran Rp{{ number_format((int) $receipt->nilai_bayar, 0, ',', '.') }}
            </button>
        </div>
    @endif

    @if ($pembayaran)
        <div class="mt-4 rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-lg font-semibold text-primary">Slip pembayaran</h2>
            <p class="mt-2 text-base">{{ $pembayaran->metode }} · Rp{{ number_format($pembayaran->jumlah, 0, ',', '.') }}</p>
            <p class="text-sm text-ink-soft">Bukti: {{ $pembayaran->referensi }} · {{ $pembayaran->created_at->format('d M Y H:i') }}</p>
        </div>
    @endif
</div>

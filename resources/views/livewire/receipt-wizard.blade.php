<div class="mx-auto max-w-xl px-4 py-6 pb-28">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Penerimaan Gabah</h1>
            <p class="text-sm text-ink-soft">{{ auth()->user()->activeLocation?->nama }}</p>
        </div>
        <a href="/app/penerimaan" class="text-sm text-primary">Batal</a>
    </div>

    <div class="mb-6 flex items-center justify-between text-sm">
        @foreach (['Petani', 'Timbang', 'Mutu', 'Konfirmasi'] as $i => $label)
            <div class="flex flex-1 items-center">
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-full border text-xs font-semibold',
                    'border-primary bg-primary text-white' => $step > $i + 1,
                    'border-primary bg-accent text-ink' => $step === $i + 1,
                    'border-line bg-card text-ink-soft' => $step < $i + 1,
                ])>{{ $i + 1 }}</span>
                <span class="ml-2 {{ $step === $i + 1 ? 'font-semibold text-primary' : 'text-ink-soft' }}">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if ($step === 1)
        <div class="rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-xl font-semibold text-primary">Pilih petani</h2>
            @if ($kontrak->isEmpty())
                <p class="mt-3 rounded-[10px] bg-cream px-3 py-3 text-base text-ink-soft">Belum ada petani dengan kontrak aktif di gudang ini. Hubungi Staf Pengadaan.</p>
            @else
                <label class="mt-4 mb-1 block text-sm font-medium">Petani berkontrak aktif</label>
                <select wire:model="contract_id" class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base">
                    <option value="">— pilih —</option>
                    @foreach ($kontrak as $k)
                        <option value="{{ $k->id }}">{{ $k->partner->nama }} · {{ $k->musim }}</option>
                    @endforeach
                </select>
                @error('contract_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                @if ($rencanaList->isNotEmpty())
                    <label class="mt-4 mb-1 block text-sm font-medium">Rencana panen</label>
                    <select wire:model="harvest_plan_id" class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base">
                        <option value="">— tanpa rencana —</option>
                        @foreach ($rencanaList as $p)
                            <option value="{{ $p->id }}">{{ $p->tgl_perkiraan->format('d M Y') }} · {{ number_format((float) $p->kg_perkiraan, 0, ',', '.') }} kg</option>
                        @endforeach
                    </select>
                @endif
            @endif
        </div>
    @elseif ($step === 2)
        <div class="rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-xl font-semibold text-primary">Timbang gabah</h2>
            <div class="mt-4 flex items-end gap-3">
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium">Bruto (kg)</label>
                    <input wire:model.live="bruto" type="number" step="0.001" inputmode="decimal" class="h-14 w-full rounded-[10px] border border-line px-3 text-xl">
                </div>
                <span class="pb-4 text-2xl text-ink-soft">−</span>
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium">Tara (kg)</label>
                    <input wire:model.live="tara" type="number" step="0.001" inputmode="decimal" class="h-14 w-full rounded-[10px] border border-line px-3 text-xl">
                </div>
            </div>
            @error('bruto') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            @error('tara') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

            <div class="mt-4 rounded-[10px] bg-cream p-4">
                <p class="text-sm text-ink-soft">Netto</p>
                <p class="font-display text-3xl font-semibold text-primary">{{ number_format($this->netto(), 3, ',', '.') }} kg</p>
            </div>

            @if ($this->rencanaKg())
                <p class="mt-3 text-sm text-ink-soft">Rencana: {{ number_format($this->rencanaKg(), 0, ',', '.') }} kg · Selisih {{ $this->selisih() }}%</p>
                @if ($this->perluSetuju())
                    <p class="mt-2 rounded-[10px] bg-warning-bg px-3 py-2 text-sm text-warning">Selisih {{ $this->selisih() }}%. Butuh persetujuan Kepala Gudang.</p>
                    <label class="mt-3 mb-1 block text-sm font-medium">Alasan selisih (wajib)</label>
                    <textarea wire:model="selisih_alasan" rows="2" class="w-full rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
                    @error('selisih_alasan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @else
                    <p class="mt-2 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">Dalam toleransi ±1%, diterima otomatis.</p>
                @endif
            @endif
        </div>
    @elseif ($step === 3)
        <div class="rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-xl font-semibold text-primary">Inspeksi mutu</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Kadar air (%)</label>
                    <input wire:model.live="kadar_air" type="number" step="0.1" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Kadar hampa (%)</label>
                    <input wire:model="kadar_hampa" type="number" step="0.1" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Butir patah (%)</label>
                    <input wire:model="butir_patah" type="number" step="0.1" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Varietas</label>
                    <input wire:model="varietas" type="text" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                </div>
            </div>
            @error('kadar_air') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            @error('varietas') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

            @if ($kadar_air !== '' && (float) $kadar_air > 18)
                <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">Di bawah standar minimum. Pilih: Tolak atau Karantina.</p>
                <select wire:model="keputusan" class="mt-2 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                    <option value="TERIMA">— pilih keputusan —</option>
                    <option value="KARANTINA">Karantina</option>
                    <option value="TOLAK">Tolak</option>
                </select>
                <textarea wire:model="alasan" rows="2" placeholder="Alasan wajib, terlihat oleh petani" class="mt-2 w-full rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
                @error('keputusan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @error('alasan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            @endif

            <label class="mt-4 mb-1 block text-sm font-medium">Foto bukti timbangan &amp; sampel (wajib)</label>
            <input wire:model="foto" type="file" accept="image/*" capture="environment" class="w-full rounded-[10px] border border-line px-3 py-3 text-base">
            <div wire:loading wire:target="foto" class="mt-1 text-sm text-ink-soft">Mengunggah…</div>
            @error('foto') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            @if ($foto) <p class="mt-1 text-sm text-success">Foto siap.</p> @endif
        </div>
    @elseif ($step === 4)
        <div class="rounded-[12px] border border-line bg-card p-5">
            <h2 class="font-display text-xl font-semibold text-primary">Konfirmasi</h2>
            <dl class="mt-4 space-y-2 text-base">
                <div class="flex justify-between"><dt class="text-ink-soft">Netto</dt><dd class="font-semibold">{{ number_format($this->netto(), 3, ',', '.') }} kg</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Keputusan</dt><dd class="font-semibold">{{ $keputusan }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Grade</dt><dd class="font-semibold">{{ $grade?->kode ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Harga/kg</dt><dd class="font-semibold">{{ $harga_per_kg ? 'Rp'.number_format($harga_per_kg, 0, ',', '.') : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Nilai bayar</dt><dd class="font-display text-xl font-semibold text-primary">{{ $nilai_bayar ? 'Rp'.number_format($nilai_bayar, 0, ',', '.') : '—' }}</dd></div>
            </dl>
            @if ($grade && ! $harga_per_kg)
                <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">Harga untuk grade ini belum ditetapkan. Hubungi Staf Pengadaan.</p>
            @endif
            @error('konfirmasi') <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
    @else
        <div class="rounded-[12px] border border-line bg-card p-6 text-center">
            @if ($kode_lot)
                <p class="text-sm text-ink-soft">Penerimaanconfirmed</p>
                <p class="mt-1 font-display text-2xl font-semibold text-primary">{{ $kode_lot }}</p>
                <p class="mt-2 text-base">Nilai bayar: Rp{{ number_format((int) $nilai_bayar, 0, ',', '.') }}</p>
            @elseif ($step === 6)
                <p class="font-display text-xl font-semibold text-danger">Penerimaan ditolak</p>
                <p class="mt-2 text-base text-ink-soft">{{ $alasan }}</p>
            @else
                <p class="font-display text-xl font-semibold text-warning">Menunggu persetujuan Kepala Gudang</p>
                <p class="mt-2 text-base text-ink-soft">Stok belum bertambah sampai disetujui.</p>
            @endif
            <a href="/app/penerimaan" class="mt-5 block h-12 w-full rounded-[10px] bg-primary text-base font-semibold leading-[48px] text-white">Selesai</a>
        </div>
    @endif

    @if ($step <= 4)
        <div class="fixed inset-x-0 bottom-0 border-t border-line bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-xl gap-3">
                @if ($step > 1)
                    <button wire:click="back" class="h-14 rounded-[10px] border border-line px-5 text-base">Kembali</button>
                @endif
                @if ($step < 4)
                    <button wire:click="next" wire:loading.attr="disabled" class="h-14 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white disabled:opacity-50">Lanjut →</button>
                @else
                    <button wire:click="confirm" wire:loading.attr="disabled" class="h-14 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white disabled:opacity-50">
                        {{ $this->perluSetuju() ? 'Kirim untuk persetujuan' : 'Konfirmasi dan buat lot' }}
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>

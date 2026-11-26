<div class="mx-auto max-w-xl px-4 py-6 pb-28" x-data="sileuitOfflineWizard(@js($offlineMaster))">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Penerimaan Gabah</h1>
            <p class="text-sm text-ink-soft">{{ auth()->user()->activeLocation?->nama }}</p>
        </div>
        <a href="/app/penerimaan" class="text-sm font-semibold text-primary hover:underline">Batal / Daftar</a>
    </div>

    {{-- Network & IndexedDB Offline Mode Bar --}}
    <div class="mb-4 rounded-[12px] border px-4 py-3 transition"
        :class="isOfflineMode ? 'border-warning/50 bg-warning-bg/60' : 'border-line bg-card'">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="inline-block h-2.5 w-2.5 rounded-full"
                    :class="isOfflineMode ? 'bg-warning' : 'bg-success'"></span>
                <span x-text="isOfflineMode ? 'Mode Offline Aktif (IndexedDB Lokal)' : 'Online — Server Terhubung'"></span>
                <template x-if="outbox.length > 0">
                    <span class="rounded-full bg-cream px-2 py-0.5 text-[11px] font-bold text-primary"
                        x-text="pendingCount + ' Menunggu · ' + outbox.length + ' Total di IndexedDB'"></span>
                </template>
            </div>
            <button type="button" @click="toggleSimulateOffline"
                class="rounded-[8px] border px-2.5 py-1 text-[11px] font-bold transition"
                :class="simulateOffline ? 'border-primary bg-primary text-white' : 'border-line bg-paper text-ink-soft hover:text-primary'">
                <span x-text="simulateOffline ? 'Matikan Simulasi Offline' : 'Simulasi Mode Offline'"></span>
            </button>
        </div>
        <p x-show="isOfflineMode" x-cloak class="mt-1.5 text-[11px] leading-relaxed text-ink-soft">
            Koneksi server tidak aktif. Setiap penerimaan gabah disimpan ke penyimpanan lokal browser (<code class="font-mono">IndexedDB: sileuit_offline_v1</code>) beserta <code class="font-mono">idempotency_key</code> unik dan dapat disinkronkan saat internet kembali pulih.
        </p>
    </div>

    {{-- LIVEWIRE ONLINE WIZARD --}}
    <div x-show="!isOfflineMode">
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
            <div wire:key="wizard-step-1" class="rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-xl font-semibold text-primary">Pilih petani</h2>
                @if ($kontrak->isEmpty())
                    <p class="mt-3 rounded-[10px] bg-cream px-3 py-3 text-base text-ink-soft">Belum ada petani dengan kontrak aktif di gudang ini. Hubungi Staf Pengadaan.</p>
                @else
                    <label class="mt-4 mb-1 block text-sm font-medium">Petani berkontrak aktif</label>
                    <select wire:key="select-contract" wire:model.live="contract_id" class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base">
                        <option value="">— pilih —</option>
                        @foreach ($kontrak as $k)
                            <option value="{{ $k->id }}">{{ $k->partner->nama }} · {{ $k->musim }}</option>
                        @endforeach
                    </select>
                    @error('contract_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                    @if ($rencanaList->isNotEmpty())
                        <label class="mt-4 mb-1 block text-sm font-medium">Rencana panen</label>
                        <select wire:key="select-harvest-plan" wire:model="harvest_plan_id" class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base">
                            <option value="">— tanpa rencana —</option>
                            @foreach ($rencanaList as $p)
                                <option value="{{ $p->id }}">{{ $p->tgl_perkiraan->format('d M Y') }} · {{ number_format((float) $p->kg_perkiraan, 0, ',', '.') }} kg</option>
                            @endforeach
                        </select>
                    @endif
                @endif
            </div>
        @elseif ($step === 2)
            <div wire:key="wizard-step-2" class="rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-xl font-semibold text-primary">Timbang gabah</h2>
                <div class="mt-4 flex items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium">Bruto (kg)</label>
                        <input wire:key="input-bruto" id="input-bruto" name="bruto" wire:model.live="bruto" type="number" step="0.001" inputmode="decimal" class="h-14 w-full rounded-[10px] border border-line px-3 text-xl">
                    </div>
                    <span class="pb-4 text-2xl text-ink-soft">−</span>
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium">Tara (kg)</label>
                        <input wire:key="input-tara" id="input-tara" name="tara" wire:model.live="tara" type="number" step="0.001" inputmode="decimal" class="h-14 w-full rounded-[10px] border border-line px-3 text-xl">
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
                        <textarea wire:key="input-selisih-alasan" wire:model="selisih_alasan" rows="2" class="w-full rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
                        @error('selisih_alasan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @else
                        <p class="mt-2 rounded-[10px] bg-success-bg px-3 py-2 text-sm text-success">Dalam toleransi ±1%, diterima otomatis.</p>
                    @endif
                @endif
            </div>
        @elseif ($step === 3)
            <div wire:key="wizard-step-3" class="rounded-[12px] border border-line bg-card p-5">
                <h2 class="font-display text-xl font-semibold text-primary">Inspeksi mutu</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Kadar air (%)</label>
                        <input wire:key="input-kadar-air" id="input-kadar-air" name="kadar_air" wire:model.live="kadar_air" type="number" step="0.1" inputmode="decimal" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Kadar hampa (%)</label>
                        <input wire:key="input-kadar-hampa" id="input-kadar-hampa" name="kadar_hampa" wire:model="kadar_hampa" type="number" step="0.1" inputmode="decimal" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Butir patah (%)</label>
                        <input wire:key="input-butir-patah" id="input-butir-patah" name="butir_patah" wire:model="butir_patah" type="number" step="0.1" inputmode="decimal" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Varietas</label>
                        <input wire:key="input-varietas" id="input-varietas" name="varietas" wire:model="varietas" type="text" class="h-12 w-full rounded-[10px] border border-line px-3 text-lg">
                    </div>
                </div>
                @error('kadar_air') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @error('kadar_hampa') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @error('butir_patah') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @error('varietas') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                @if ($kadar_air !== '' && (float) $kadar_air > 18)
                    <p class="mt-3 rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">Di bawah standar minimum. Pilih: Tolak atau Karantina.</p>
                    <select wire:key="select-keputusan" wire:model="keputusan" class="mt-2 h-12 w-full rounded-[10px] border border-line px-3 text-base">
                        <option value="TERIMA">— pilih keputusan —</option>
                        <option value="KARANTINA">Karantina</option>
                        <option value="TOLAK">Tolak</option>
                    </select>
                    <textarea wire:key="input-alasan-tolak" wire:model="alasan" rows="2" placeholder="Alasan wajib, terlihat oleh petani" class="mt-2 w-full rounded-[10px] border border-line px-3 py-2 text-base"></textarea>
                    @error('keputusan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @error('alasan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @endif

                <label class="mt-4 mb-1 block text-sm font-medium">Foto bukti timbangan &amp; sampel (wajib)</label>
                <input wire:key="input-foto" id="input-foto" wire:model="foto" type="file" accept="image/*" capture="environment" class="w-full rounded-[10px] border border-line px-3 py-3 text-base">
                <div wire:loading wire:target="foto" class="mt-1 text-sm text-ink-soft">Mengunggah…</div>
                @error('foto') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @if ($foto) <p class="mt-1 text-sm text-success">Foto siap.</p> @endif
            </div>
        @elseif ($step === 4)
            <div wire:key="wizard-step-4" class="rounded-[12px] border border-line bg-card p-5">
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
            <div wire:key="wizard-step-finish-{{ $step }}" class="rounded-[12px] border border-line bg-card p-6 text-center">
                @if ($kode_lot)
                    <p class="text-sm text-ink-soft">Penerimaan dikonfirmasi</p>
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
            <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-paper px-4 py-3">
                <div class="mx-auto flex max-w-xl gap-3">
                    @if ($step > 1)
                        <button type="button" wire:click="back" class="h-14 rounded-[10px] border border-line px-5 text-base">Kembali</button>
                    @endif
                    @if ($step < 4)
                        <button type="button" wire:click="next" wire:loading.attr="disabled" class="h-14 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white disabled:opacity-50">Lanjut →</button>
                    @else
                        <button type="button" wire:click="confirm" wire:loading.attr="disabled" @disabled($grade && ! $harga_per_kg) class="h-14 flex-1 rounded-[10px] bg-primary text-base font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                            {{ $this->perluSetuju() ? 'Kirim untuk persetujuan' : 'Konfirmasi dan buat lot' }}
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- PURE CLIENT-SIDE OFFLINE FORM (INDEXEDDB) --}}
    <div x-show="isOfflineMode" x-cloak class="space-y-4">
        <div class="rounded-[12px] border border-line bg-card p-5 space-y-4">
            <div>
                <h2 class="font-display text-xl font-semibold text-primary">Catat Penerimaan (Mode Offline)</h2>
                <p class="text-xs text-ink-soft">Data dibentuk murni di browser tanpa koneksi ke server. Harga dan penentuan grade resmi tetap dikunci oleh server saat sinkronisasi.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-ink-soft">Petani Berkontrak Aktif <span class="text-danger">*</span></label>
                <select x-model="form.contract_id" @change="form.harvest_plan_id = ''"
                    class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm">
                    <option value="">— Pilih Petani —</option>
                    <template x-for="c in master.contracts" :key="c.id">
                        <option :value="c.id" x-text="c.label"></option>
                    </template>
                </select>
            </div>

            <div x-show="availablePlans.length > 0">
                <label class="mb-1 block text-xs font-semibold text-ink-soft">Rencana Panen</label>
                <select x-model="form.harvest_plan_id" class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm">
                    <option value="">— Tanpa rencana —</option>
                    <template x-for="p in availablePlans" :key="p.id">
                        <option :value="p.id" x-text="p.tgl_label + ' · ' + p.kg_perkiraan + ' kg'"></option>
                    </template>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Bruto (kg) <span class="text-danger">*</span></label>
                    <input x-model="form.bruto_kg" type="number" step="0.001" placeholder="1050"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-base font-mono">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Tara (kg) <span class="text-danger">*</span></label>
                    <input x-model="form.tara_kg" type="number" step="0.001" placeholder="50"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-base font-mono">
                </div>
            </div>

            <div class="flex items-center justify-between rounded-[10px] bg-cream px-4 py-3 text-sm">
                <span class="text-ink-soft">Perkiraan Netto:</span>
                <strong class="font-mono text-lg text-primary" x-text="nettoKg.toLocaleString('id-ID') + ' kg'"></strong>
            </div>

            <div x-show="needsDiscrepancyReason" x-cloak class="rounded-[10px] border border-warning/40 bg-warning-bg/50 p-3 space-y-2">
                <p class="text-xs font-semibold text-warning" x-text="'Selisih ' + selisihPersen + '% dari rencana panen. Saat sinkronisasi akan berstatus MENUNGGU_PERSETUJUAN.'"></p>
                <textarea x-model="form.selisih_alasan" rows="2" placeholder="Alasan selisih (wajib)..."
                    class="w-full rounded-[8px] border border-line bg-card px-3 py-2 text-xs"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Kadar Air (%) <span class="text-danger">*</span></label>
                    <input x-model="form.kadar_air" type="number" step="0.1" placeholder="13.5"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Kadar Hampa (%) <span class="text-danger">*</span></label>
                    <input x-model="form.kadar_hampa" type="number" step="0.1" placeholder="2"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Butir Patah (%)</label>
                    <input x-model="form.butir_patah" type="number" step="0.1" placeholder="3"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm font-mono">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-soft">Varietas <span class="text-danger">*</span></label>
                    <input x-model="form.varietas" type="text" placeholder="Ciherang"
                        class="h-11 w-full rounded-[10px] border border-line bg-paper px-3 text-sm">
                </div>
            </div>

            <div x-show="estimatedGrade" class="rounded-[8px] bg-success-bg px-3 py-2 text-xs font-semibold text-success"
                x-text="'Estimasi Mutu Lokal: Grade ' + (estimatedGrade?.kode || '') + ' (diverifikasi ulang server saat sync)'"></div>

            <div x-show="needsManualDecision" x-cloak class="rounded-[10px] border border-danger/30 bg-danger-bg/50 p-3 space-y-2">
                <p class="text-xs font-semibold text-danger">Kadar air melebihi ambang mutu. Pilih keputusan sementara:</p>
                <select x-model="form.keputusan" class="h-10 w-full rounded-[8px] border border-line bg-card px-2.5 text-xs">
                    <option value="KARANTINA">Karantina</option>
                    <option value="TOLAK">Tolak</option>
                </select>
                <textarea x-model="form.alasan" rows="2" placeholder="Alasan wajib diisi..."
                    class="w-full rounded-[8px] border border-line bg-card px-3 py-2 text-xs"></textarea>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-xs font-semibold text-ink-soft">Foto Bukti Timbang <span class="text-danger">*</span></label>
                    <button type="button" @click="useSamplePhoto" class="text-[11px] font-bold text-primary hover:underline">
                        Pakai Foto Sampel Uji
                    </button>
                </div>
                <input type="file" accept="image/*" capture="environment" @change="handlePhotoChange"
                    class="w-full rounded-[10px] border border-line bg-paper px-3 py-2.5 text-xs">
                <p x-show="form.foto_label" class="mt-1 text-xs font-semibold text-success" x-text="'✓ ' + form.foto_label"></p>
            </div>

            <button type="button" @click="saveOfflineEntry"
                class="h-12 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-xs hover:bg-primary-dark transition">
                Simpan ke Antrean Offline (IndexedDB)
            </button>
        </div>
    </div>

    {{-- FEEDBACK ALERTS --}}
    <div x-show="feedback" x-cloak class="mt-4 rounded-[12px] border border-success/40 bg-success-bg p-3.5 text-xs font-semibold text-success" x-text="feedback"></div>
    <div x-show="errorMsg" x-cloak class="mt-4 rounded-[12px] border border-danger/40 bg-danger-bg p-3.5 text-xs font-semibold text-danger" x-text="errorMsg"></div>

    {{-- INDEXEDDB OUTBOX QUEUE PANEL --}}
    <div x-show="outbox.length > 0 || isOfflineMode" x-cloak class="mt-6 rounded-[14px] border border-line bg-card p-5 shadow-xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line/60 pb-3">
            <div>
                <h3 class="font-display text-base font-bold text-primary">Antrean Offline (IndexedDB)</h3>
                <p class="text-[11px] font-mono text-ink-soft">Store: sileuit_offline_v1 → outbox</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" x-show="!isOfflineMode && pendingCount > 0" @click="syncAll" :disabled="busy"
                    class="rounded-[8px] bg-primary px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-dark disabled:opacity-50">
                    <span x-text="busy ? 'Menyinkronkan…' : 'Sinkronkan Semua (' + pendingCount + ')'"></span>
                </button>
                <button type="button" x-show="outbox.some(i => i.status === 'SYNCED')" @click="cleanSynced"
                    class="rounded-[8px] border border-line bg-paper px-3 py-1.5 text-xs font-semibold text-ink-soft hover:text-ink">
                    Bersihkan Tersinkron
                </button>
            </div>
        </div>

        <template x-if="outbox.length === 0">
            <p class="py-4 text-center text-xs text-ink-soft">Belum ada entri di IndexedDB. Catat penerimaan di atas saat mode offline.</p>
        </template>

        <div class="space-y-2.5">
            <template x-for="item in outbox" :key="item.idempotency_key">
                <div class="rounded-[10px] border border-line/80 bg-paper p-3.5 text-xs space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <strong class="text-sm text-ink" x-text="item.farmer_label"></strong>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                            :class="item.status === 'SYNCED' ? 'bg-success-bg text-success' : (item.status === 'ERROR' ? 'bg-danger-bg text-danger' : 'bg-warning-bg text-warning')"
                            x-text="item.status === 'SYNCED' ? ('TERSINKRON · ' + (item.sync_result?.no_dok || '')) : (item.status === 'ERROR' ? 'GAGAL SINKRON' : 'MENUNGGU SINKRON')"></span>
                    </div>

                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-ink-soft">
                        <span>Netto: <strong class="font-mono text-ink" x-text="item.netto_kg + ' kg'"></strong></span>
                        <span>KA: <strong class="font-mono text-ink" x-text="item.kadar_air + '%'"></strong></span>
                        <span>Varietas: <strong class="text-ink" x-text="item.varietas"></strong></span>
                    </div>

                    <p class="font-mono text-[11px] text-ink-soft break-all">
                        Idempotency Key: <span class="text-ink" x-text="item.idempotency_key"></span>
                    </p>

                    <template x-if="item.status === 'ERROR' && item.sync_result?.message">
                        <p class="rounded-[6px] bg-danger-bg px-2.5 py-1.5 font-semibold text-danger" x-text="item.sync_result.message"></p>
                    </template>

                    <template x-if="item.duplikat_teruji">
                        <p class="rounded-[6px] bg-success-bg px-2.5 py-1.5 font-semibold text-success">
                            ✓ Idempotency Terbukti (TC-14): Pengiriman ulang ditandai duplikat: true — hanya 1 penerimaan tercipta di server.
                        </p>
                    </template>

                    <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                        <template x-if="item.status === 'SYNCED' && item.sync_result?.receipt_id">
                            <a :href="'/app/penerimaan/' + item.sync_result.receipt_id"
                                class="rounded-[7px] border border-primary/40 bg-card px-2.5 py-1 text-[11px] font-semibold text-primary hover:bg-cream">
                                Lihat Dokumen →
                            </a>
                        </template>
                        <button type="button" x-show="!isOfflineMode && item.status !== 'SYNCED'" @click="syncSingle(item.idempotency_key)" :disabled="busy"
                            class="rounded-[7px] bg-primary px-3 py-1 text-[11px] font-bold text-white hover:bg-primary-dark disabled:opacity-50">
                            Sinkronkan Sekarang
                        </button>
                        <button type="button" x-show="!isOfflineMode && item.status === 'SYNCED'" @click="syncSingle(item.idempotency_key)" :disabled="busy"
                            class="rounded-[7px] border border-primary bg-card px-3 py-1 text-[11px] font-bold text-primary hover:bg-cream disabled:opacity-50">
                            Uji Kirim Ulang (TC-14)
                        </button>
                        <button type="button" @click="deleteItem(item.idempotency_key)"
                            class="rounded-[7px] border border-danger/30 px-2.5 py-1 text-[11px] font-semibold text-danger hover:bg-danger-bg">
                            Hapus
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

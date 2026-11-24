<div class="mx-auto max-w-4xl px-4 py-6">
    <a href="/app/beranda" class="text-sm text-primary">← Beranda</a>
    <h1 class="mt-2 font-display text-2xl font-semibold text-primary">Audit trail</h1>
    <div class="mt-3 flex gap-2">
        <input wire:model.live.debounce.300ms="q" type="text" placeholder="cari aksi, entitas, nama" class="h-12 flex-1 rounded-[10px] border border-line px-3 text-base">
        <select wire:model.live="aksi" class="h-12 rounded-[10px] border border-line px-3 text-base">
            <option value="">semua aksi</option>
            @foreach ($aksiList as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach
        </select>
    </div>
    <div class="mt-3 overflow-x-auto rounded-[12px] border border-line bg-card">
        <table class="w-full text-sm">
            <thead class="bg-cream text-left text-ink-soft">
                <tr><th class="p-3">Waktu</th><th class="p-3">Pelaku</th><th class="p-3">Aksi</th><th class="p-3">Entitas</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr class="border-t border-line">
                        <td class="p-3">{{ $l->waktu->format('d M Y H:i') }}</td>
                        <td class="p-3">{{ $l->user?->name ?? 'SYSTEM' }}</td>
                        <td class="p-3 font-mono">{{ $l->aksi }}</td>
                        <td class="p-3">{{ $l->entitas }}#{{ $l->entitas_id }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-ink-soft">Tidak ada log untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</div>

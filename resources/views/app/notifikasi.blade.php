<x-layouts.app title="Pusat Notifikasi — SiLeuit.id">
    <div class="mx-auto max-w-5xl px-4 py-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="/app/beranda" class="text-xs font-semibold text-primary hover:underline">← Kembali ke Beranda</a>
                <h1 class="mt-1 font-display text-2xl font-bold text-primary">Pusat Notifikasi Sistem</h1>
                <p class="text-xs text-ink-soft">Pemberitahuan operasional, persetujuan tertunda, dan peringatan batas stok</p>
            </div>
            <form method="POST" action="/app/notifikasi/baca-semua">
                @csrf
                <button type="submit" class="h-10 rounded-[10px] border border-primary bg-card px-4 text-xs font-semibold text-primary hover:bg-cream transition">
                    ✓ Tandai Semua Telah Dibaca
                </button>
            </form>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($daftar as $n)
                <div class="rounded-[14px] border border-line bg-card p-5 shadow-xs transition {{ $n->dibaca_pada ? 'opacity-60 bg-paper' : 'border-l-4 border-l-primary' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-primary">{{ $n->judul }}</span>
                        <span class="rounded-[6px] bg-cream px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $n->tipe }}</span>
                    </div>
                    <p class="mt-1.5 text-sm text-ink">{{ $n->isi }}</p>
                    <p class="mt-2 text-xs text-ink-soft">{{ $n->created_at->format('d M Y, H:i') }} WIB</p>
                </div>
            @empty
                <div class="rounded-[14px] border border-line bg-card p-10 text-center">
                    <p class="text-base text-ink-soft">Tidak ada notifikasi baru. Semua operasional berjalan normal.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>

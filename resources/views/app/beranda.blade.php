<x-layouts.app title="Beranda — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-8">
        <div class="rounded-[12px] border border-line bg-card p-6">
            <h1 class="font-display text-2xl font-semibold text-primary">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-base text-ink-soft">
                Peran:
                @foreach (auth()->user()->roles as $role)
                    <span class="mr-1 inline-block rounded-[8px] bg-cream px-2 py-0.5 text-sm text-primary">{{ $role->nama }}</span>
                @endforeach
            </p>
            <p class="mt-1 text-base text-ink-soft">
                Lokasi: {{ auth()->user()->activeLocation?->nama ?? 'Semua lokasi' }}
            </p>
            <p class="mt-4 rounded-[10px] bg-cream px-3 py-2 text-sm text-ink-soft">
                Modul operasional (penerimaan, stok, pesanan, kasir) aktif bertahap sesuai fase pembangunan.
            </p>
            <form method="POST" action="/logout" class="mt-6">
                @csrf
                <button type="submit" class="h-12 w-full rounded-[10px] border border-danger px-3 text-base font-medium text-danger">Keluar</button>
            </form>
        </div>
    </div>
</x-layouts.app>

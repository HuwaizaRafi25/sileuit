<x-layouts.app title="Portal Petani — SiLeuit.id">
    <div class="mx-auto max-w-xl px-4 py-8">
        <h1 class="font-display text-2xl font-semibold text-primary">Halo, {{ auth()->user()->name }}</h1>
        <div class="mt-4 space-y-3">
            <div class="rounded-[12px] border border-line bg-card p-5">
                <p class="text-sm text-ink-soft">Penerimaan terakhir</p>
                <p class="mt-1 text-lg font-semibold">Belum ada penerimaan</p>
            </div>
            <div class="rounded-[12px] border border-line bg-card p-5">
                <p class="text-sm text-ink-soft">Grade &amp; harga</p>
                <p class="mt-1 text-lg font-semibold">—</p>
            </div>
            <div class="rounded-[12px] border border-line bg-card p-5">
                <p class="text-sm text-ink-soft">Status bayar</p>
                <p class="mt-1 text-lg font-semibold">—</p>
            </div>
        </div>
        <p class="mt-4 rounded-[10px] bg-cream px-3 py-3 text-base text-ink-soft">
            Hasil timbang dan mutu akan tampil di sini setelah Anda menyerahkan hasil panen.
        </p>
        <form method="POST" action="/logout" class="mt-6">
            @csrf
            <button type="submit" class="h-12 w-full rounded-[10px] border border-line px-3 text-base font-medium text-ink-soft">Keluar</button>
        </form>
    </div>
</x-layouts.app>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2F5A27">
    <link rel="manifest" href="/manifest.json">
    <title>{{ $title ?? 'SiLeuit.id' }}</title>
    <link rel="stylesheet" href="/vendor/aos/aos.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-paper font-sans text-ink">
    @if (auth()->check() && request()->is('app/*'))
        <header class="sticky top-0 z-20 border-b border-line bg-card/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3">
                    <a href="/app/beranda" class="font-display text-lg font-bold text-primary">SiLeuit.id</a>
                    <span class="rounded-[8px] bg-cream px-2 py-0.5 text-xs font-semibold text-primary">
                        {{ auth()->user()->activeLocation?->nama ?? 'Semua Lokasi' }}
                    </span>
                </div>
                <nav class="flex items-center gap-4 text-xs font-semibold">
                    <a href="/app/beranda" class="{{ request()->is('app/beranda') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }}">Beranda</a>
                    @can('create', App\Modules\ProcurementReceiving\Models\Receipt::class)
                        <a href="/app/penerimaan" class="{{ request()->is('app/penerimaan*') || request()->is('app/terima') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }} hidden sm:inline">Penerimaan</a>
                    @endcan
                    @can('lihat-stok')
                        <a href="/app/stok" class="{{ request()->is('app/stok*') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }} hidden sm:inline">Stok</a>
                    @endcan
                    @can('pos-jual')
                        <a href="/app/pos" class="{{ request()->is('app/pos*') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }} hidden sm:inline">Kasir POS</a>
                    @endcan
                    @can('lihat-fulfillment')
                        <a href="/app/pengiriman" class="{{ request()->is('app/pengiriman*') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }} hidden sm:inline">Pengiriman</a>
                    @endcan
                    <a href="/app/cari" class="{{ request()->is('app/cari') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }}">Cari</a>
                    <a href="/app/notifikasi" class="{{ request()->is('app/notifikasi') ? 'text-primary font-bold' : 'text-ink-soft hover:text-primary' }}">Notifikasi</a>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="font-semibold text-danger hover:underline">Keluar</button>
                    </form>
                </nav>
            </div>
        </header>
    @endif
    {{ $slot }}
    <script src="/vendor/aos/aos.js"></script>
    @livewireScripts
</body>
</html>

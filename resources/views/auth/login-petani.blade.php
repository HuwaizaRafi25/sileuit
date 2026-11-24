<x-layouts.app title="Masuk Petani — SiLeuit.id">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-[400px] rounded-[12px] border border-line bg-card p-6">
            <div class="mb-6 text-center">
                <img src="/images/sileuit-emblem.png" alt="" class="mx-auto mb-2 h-8 w-8 object-contain" onerror="this.style.display='none'">
                <h1 class="font-display text-2xl font-semibold text-primary">Masuk Petani Mitra</h1>
                <p class="mt-1 text-sm text-ink-soft">Masukkan nomor HP dan PIN terdaftar Anda.</p>
            </div>
            <form method="POST" action="/login/petani" class="space-y-4">
                @csrf
                <div>
                    <label for="hp" class="mb-1 block text-sm font-medium">Nomor HP</label>
                    <div class="flex h-14 items-center rounded-[10px] border border-line bg-card focus-within:border-primary">
                        <span class="border-r border-line px-3 text-lg text-ink-soft">+62</span>
                        <input id="hp" name="hp" type="tel" inputmode="numeric" value="{{ old('hp') }}" required autofocus placeholder="812xxxxxxx"
                            class="h-full w-full rounded-r-[10px] px-3 text-lg outline-none">
                    </div>
                </div>
                <div>
                    <label for="pin" class="mb-1 block text-sm font-medium">PIN 6 digit</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required
                        class="h-14 w-full rounded-[10px] border border-line bg-card px-3 text-lg tracking-[0.5em] outline-none focus:border-primary">
                </div>
                @error('hp')
                    <p class="rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p>
                @enderror
                <button type="submit" class="h-14 w-full rounded-[10px] bg-primary text-lg font-semibold text-white hover:bg-primary-dark">Masuk</button>
            </form>
            <p class="mt-4 text-center text-sm text-ink-soft">Lupa PIN? Hubungi Admin atau Staf Pengadaan.</p>
            <a href="/login" class="mt-2 block text-center text-sm text-primary">← Kembali ke masuk staf</a>
        </div>
    </div>
</x-layouts.app>

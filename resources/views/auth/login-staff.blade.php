<x-layouts.app title="Masuk — SiLeuit.id">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-[400px] rounded-[12px] border border-line bg-card p-6">
            <div class="mb-6 text-center">
                <img src="/images/sileuit-emblem.png" alt="" class="mx-auto mb-2 h-10 w-10 object-contain" onerror="this.style.display='none'">
                <h1 class="font-display text-2xl font-semibold text-primary">Masuk ke SiLeuit.id</h1>
                <p class="mt-1 text-sm text-ink-soft">Sistem rantai pasok gabah–beras Jawa Barat</p>
            </div>
            <form method="POST" action="/login" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base outline-none focus:border-primary">
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Kata sandi</label>
                    <input id="password" name="password" type="password" required
                        class="h-12 w-full rounded-[10px] border border-line bg-card px-3 text-base outline-none focus:border-primary">
                </div>
                @error('email')
                    <p class="rounded-[10px] bg-danger-bg px-3 py-2 text-sm text-danger">{{ $message }}</p>
                @enderror
                <button type="submit" class="h-12 w-full rounded-[10px] bg-primary text-base font-semibold text-white hover:bg-primary-dark">Masuk</button>
            </form>
            <div class="my-4 border-t border-line"></div>
            <a href="/login/petani" class="block h-12 w-full rounded-[10px] border border-primary px-3 text-center text-base font-medium leading-[46px] text-primary">Saya petani mitra: masuk dengan nomor HP</a>
        </div>
    </div>
</x-layouts.app>

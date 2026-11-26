<x-layouts.app title="Masuk — SiLeuit.id">
    <div class="relative min-h-screen overflow-hidden bg-paper text-ink">
        {{-- Woven bamboo (anyaman bilik) top trim --}}
        <div class="h-2 w-full" style="background-image: repeating-linear-gradient(45deg, #2F5A27 0 12px, #C7A66B 12px 24px, #6E471B 24px 36px, #E3C48A 36px 48px); opacity: 0.85;"></div>

        {{-- Faded textured sepia margin ornaments --}}
        <svg aria-hidden="true" class="pointer-events-none fixed -left-14 bottom-10 z-0 hidden h-72 w-72 text-[#6E471B] opacity-[0.15] lg:block" viewBox="0 0 220 220" fill="none">
            <path d="M40 190 C65 140, 82 95, 92 40" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M92 40 C82 54, 70 64, 58 66 M92 40 C104 54, 114 66, 122 72" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M84 74 C68 82, 54 96, 46 108 M86 92 C102 98, 116 112, 126 124" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M76 112 C58 122, 42 138, 34 152 M78 130 C96 136, 112 150, 122 164" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <ellipse cx="58" cy="66" rx="7" ry="3.2" transform="rotate(-30 58 66)" stroke="currentColor" stroke-width="1.5"/>
            <ellipse cx="122" cy="72" rx="7" ry="3.2" transform="rotate(32 122 72)" stroke="currentColor" stroke-width="1.5"/>
            <ellipse cx="46" cy="108" rx="8" ry="3.4" transform="rotate(-35 46 108)" stroke="currentColor" stroke-width="1.5"/>
            <ellipse cx="126" cy="124" rx="8" ry="3.4" transform="rotate(35 126 124)" stroke="currentColor" stroke-width="1.5"/>
            <path d="M120 190 C138 152, 148 118, 154 80" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-dasharray="4 3"/>
            <ellipse cx="142" cy="104" rx="7" ry="3" transform="rotate(-28 142 104)" stroke="currentColor" stroke-width="1.4"/>
            <ellipse cx="166" cy="116" rx="7" ry="3" transform="rotate(28 166 116)" stroke="currentColor" stroke-width="1.4"/>
        </svg>

        <svg aria-hidden="true" class="pointer-events-none fixed -right-12 top-24 z-0 hidden h-64 w-64 text-[#6E471B] opacity-[0.14] lg:block" viewBox="0 0 200 200" fill="none">
            {{-- Traditional Leuit outline (no cross on roof) --}}
            <path d="M36 92 L100 32 L164 92" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M52 92 L100 46 L148 92" stroke="currentColor" stroke-width="1.4" stroke-dasharray="4 3"/>
            <path d="M58 92 L50 154 L150 154 L142 92 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M62 112 H138 M59 132 H141" stroke="currentColor" stroke-width="1.3" stroke-dasharray="3 3"/>
            <path d="M66 154 V174 M134 154 V174 M54 174 H146" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>

        {{-- Top Editorial Bar --}}
        <header class="relative z-10 mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="/" class="inline-flex items-center gap-3 group">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-cream shadow-sm transition group-hover:bg-primary-dark">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5L12 3l9 7.5" />
                        <path d="M6.5 10.5l-1 8.5h13l-1-8.5" />
                        <path d="M4.5 21h15" />
                        <path d="M8.5 14h7" />
                    </svg>
                </span>
                <div>
                    <span class="font-display text-lg font-bold tracking-tight text-ink">SiLeuit</span>
                    <span class="block text-[10px] font-medium uppercase tracking-[0.18em] text-[#6E471B]">Lumbung Padi Nusantara</span>
                </div>
            </a>

            <a href="/" class="inline-flex items-center gap-1.5 rounded-full border border-line bg-cream/70 px-4 py-2 text-xs font-semibold text-ink-soft transition hover:border-primary/50 hover:bg-cream hover:text-primary">
                <span>←</span>
                <span>Kembali ke Beranda</span>
            </a>
        </header>

        {{-- Main Split Auth Container --}}
        <main class="relative z-10 mx-auto flex max-w-6xl items-center justify-center px-4 pb-12 pt-2 sm:px-6 lg:px-8">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-[22px] border border-line bg-card shadow-[0_24px_60px_-24px_rgba(31,42,28,0.22)] lg:grid-cols-12">

                {{-- Left Nusantara Editorial Panel --}}
                <div class="relative hidden flex-col justify-between overflow-hidden bg-[#1F2A1C] p-8 text-cream lg:col-span-5 lg:flex">
                    <img src="/images/leuit-sunda.jpg" alt="Leuit lumbung padi tradisional Sunda"
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-55">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#182215] via-[#1F2A1C]/70 to-[#1F2A1C]/40"></div>

                    <div class="relative z-10">
                        <span class="inline-flex items-center gap-2 rounded-full border border-tan/40 bg-[#1F2A1C]/70 px-3 py-1 text-[11px] font-semibold tracking-wide text-tan backdrop-blur-xs">
                            <span class="h-1.5 w-1.5 rounded-full bg-tan"></span>
                            Tatanan Lumbung Padi Jawa Barat
                        </span>
                    </div>

                    <div class="relative z-10 space-y-4">
                        <p class="font-display text-2xl font-semibold leading-snug text-paper">
                            “Silih asah, silih asih, silih asuh.”
                        </p>
                        <p class="text-xs leading-relaxed text-cream/85">
                            Setiap timbangan gabah, hasil uji kadar air, hingga pencairan hasil panen dicatat terang agar amanah lumbung terjaga dari sawah hingga ke meja makan.
                        </p>
                        <div class="border-t border-cream/20 pt-4 text-[11px] text-tan/90">
                            Akses operasional internal, gudang, penyaluran, dan mitra dagang resmi.
                        </div>
                    </div>
                </div>

                {{-- Right Form Panel --}}
                <div class="bg-paper/90 p-6 sm:p-9 lg:col-span-7">
                    <div class="mb-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#6E471B]">Gerbang Masuk</p>
                        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-ink sm:text-[28px]">
                            Masuk ke SiLeuit
                        </h1>
                        <p class="mt-1 text-xs leading-relaxed text-ink-soft">
                            Gunakan akun email yang terdaftar untuk mengelola operasional lumbung, gudang, dan penyaluran.
                        </p>
                    </div>

                    {{-- Role Selector Tabs --}}
                    <div class="mb-6 flex rounded-[12px] border border-line bg-cream/60 p-1">
                        <a href="/login"
                            class="flex-1 rounded-[9px] bg-primary py-2.5 text-center text-xs font-bold text-white shadow-xs transition">
                            Pengelola &amp; Mitra
                        </a>
                        <a href="/login/petani"
                            class="flex-1 rounded-[9px] py-2.5 text-center text-xs font-semibold text-ink-soft transition hover:text-primary">
                            Petani Mitra
                        </a>
                    </div>

                    {{-- Login Form --}}
                    <form method="POST" action="/login" class="space-y-4" x-data="{ showPass: false }">
                        @csrf
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold text-ink">Alamat Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                placeholder="nama@sileuit.id"
                                class="h-11 w-full rounded-[10px] border border-line bg-white px-3.5 text-sm text-ink outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-semibold text-ink">Kata Sandi</label>
                            <div class="relative">
                                <input id="password" name="password" :type="showPass ? 'text' : 'password'" required
                                    placeholder="••••••••"
                                    class="h-11 w-full rounded-[10px] border border-line bg-white pl-3.5 pr-11 text-sm text-ink outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <button type="button" @click="showPass = !showPass"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-ink-soft transition hover:text-primary focus:outline-none"
                                    title="Tampilkan / Sembunyikan Kata Sandi">
                                    <svg x-show="!showPass" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPass" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @error('email')
                            <div class="flex items-start gap-2 rounded-[10px] border border-danger/30 bg-danger-bg p-3 text-xs font-semibold text-danger">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1.5 1.5 0 004.09 19h15.82a1.5 1.5 0 001.3-2.14l-7.5-13a1.5 1.5 0 00-2.6 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <button type="submit"
                            class="mt-2 h-11 w-full rounded-[10px] bg-primary text-sm font-bold text-white shadow-sm transition hover:bg-primary-dark active:scale-[0.99]">
                            Masuk ke Sistem
                        </button>
                    </form>

                    @if ($demoRoles !== [])
                        <div class="mt-6 border-t border-line/70 pt-5">
                            <div class="mb-3">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-[#6E471B]">Akses Demo (Lokal Saja)</h2>
                                <p class="mt-1 text-[11px] leading-relaxed text-ink-soft">
                                    Pilih peran untuk masuk tanpa kata sandi. Akses ini hanya tersedia saat aplikasi berjalan di lingkungan lokal.
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($demoRoles as $role => $label)
                                    <form method="POST" action="{{ route('login.demo', ['role' => $role]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="min-h-10 w-full rounded-[10px] border border-line bg-cream/65 px-2.5 py-2 text-left text-[11px] font-semibold leading-tight text-primary transition hover:border-primary/50 hover:bg-cream focus:outline-none focus:ring-2 focus:ring-primary/30">
                                            {{ $label }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Bottom Farmer Gateway --}}
                    <div class="mt-6 flex flex-col items-center justify-between gap-3 border-t border-line/70 pt-5 text-center sm:flex-row sm:text-left">
                        <p class="text-xs text-ink-soft">Petani mitra ingin mengecek hasil timbang panen?</p>
                        <a href="/login/petani"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-[10px] border border-primary/35 bg-cream/70 px-4 py-2 text-xs font-bold text-primary transition hover:bg-cream">
                            <span>Masuk Portal Petani</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-layouts.app>

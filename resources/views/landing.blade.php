@php
    $user = auth()->user();
    $entryUrl = match (true) {
        $user && $user->hasRole('PETANI') => '/portal',
        $user && $user->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN') => '/admin',
        $user !== null => '/app/beranda',
        default => '/login',
    };
@endphp

<x-layouts.app title="SiLeuit.id — Menjaga Amanah Lumbung Padi Nusantara">
    <div class="relative min-h-screen overflow-x-clip bg-paper text-ink selection:bg-cream selection:text-primary">

        <!-- Traditional Sundanese Woven Bamboo (Anyaman Bilik) Top Trim -->
        <div class="h-1.5 w-full opacity-85"
            style="background-image: repeating-linear-gradient(45deg, #2F5A27 0, #2F5A27 6px, #E3C48A 6px, #E3C48A 12px); background-size: 17px 17px;"></div>

        <!-- Quiet Editorial Header (Single Unified Entry Button — No Internal Portal Hints) -->
        <header class="sticky top-0 z-40 border-b border-line/75 bg-paper/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <a href="/" class="group flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-[8px] border border-primary/25 bg-cream/70 text-primary transition-colors group-hover:bg-primary group-hover:text-cream">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 10.5L12 3.5l9 7" />
                            <path d="M6.5 10.5l1 9.5h9l1-9.5" />
                            <path d="M6 20h12" />
                            <path d="M12 13v4" />
                        </svg>
                    </span>
                    <div class="leading-none">
                        <span class="font-display text-lg font-bold tracking-tight text-ink">SiLeuit.id</span>
                        <span class="mt-0.5 block text-[10px] font-medium uppercase tracking-[0.18em] text-ink-soft">Lumbung Padi Digital</span>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-8 text-xs font-semibold tracking-wider uppercase text-ink-soft">
                    <a href="#filosofi" class="hover:text-primary transition-colors">Filosofi Leuit</a>
                    <a href="#babak" class="hover:text-primary transition-colors">Perjalanan Padi</a>
                    <a href="#ketertiban" class="hover:text-primary transition-colors">Ketertiban</a>
                </nav>

                <div>
                    <a href="{{ $entryUrl }}"
                        class="inline-flex h-9 items-center gap-2 rounded-[8px] border border-primary bg-primary px-4.5 text-xs font-semibold tracking-wide text-cream transition-all hover:bg-primary-dark">
                        <span>{{ $user ? 'Dasbor' : 'Masuk' }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- HERO SECTION: Asymmetric Nusantara Print Spread + Faded Sepia Outline Ornaments -->
        <section class="relative pt-12 pb-20 sm:pt-20 sm:pb-28 overflow-hidden">

            <!-- Left Margin Ornament: Rich Textured Sepia Woodcut of Hanging Rice Stalks (Bulir Padi) -->
            <svg aria-hidden="true" class="pointer-events-none hidden xl:block absolute -left-6 top-12 w-64 h-80 text-[#6E471B] opacity-[0.21]"
                viewBox="0 0 240 320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <!-- Primary double-contour arching stalks -->
                <path d="M32 305 C40 220, 68 145, 126 54" stroke-width="2.1" />
                <path d="M38 305 C46 223, 73 150, 130 58" stroke-width="0.9" opacity="0.7" />
                <path d="M56 305 C64 235, 92 172, 160 92" stroke-width="1.9" />
                <path d="M22 278 C36 232, 54 195, 72 170" stroke-width="1.6" />
                <!-- Rich veined grass blades -->
                <path d="M56 218 C28 202, 14 172, 10 144 C24 166, 40 192, 58 210" stroke-width="1.5" fill="currentColor" fill-opacity="0.06" />
                <path d="M66 244 C94 226, 118 206, 138 190 C116 218, 92 238, 68 252" stroke-width="1.5" fill="currentColor" fill-opacity="0.06" />
                <!-- Plump ribbed paddy grains -->
                <g stroke-width="1.5" fill="currentColor" fill-opacity="0.09">
                    <ellipse cx="126" cy="54" rx="4.5" ry="9" transform="rotate(-25 126 54)" />
                    <ellipse cx="112" cy="72" rx="4.5" ry="8.5" transform="rotate(-35 112 72)" />
                    <ellipse cx="100" cy="92" rx="4.5" ry="8.5" transform="rotate(-40 100 92)" />
                    <ellipse cx="89" cy="114" rx="4.5" ry="8" transform="rotate(-30 89 114)" />
                    <ellipse cx="79" cy="136" rx="4" ry="7.5" transform="rotate(-26 79 136)" />
                    <ellipse cx="160" cy="92" rx="4.5" ry="9" transform="rotate(-15 160 92)" />
                    <ellipse cx="146" cy="110" rx="4.5" ry="8.5" transform="rotate(-25 146 110)" />
                    <ellipse cx="133" cy="130" rx="4.5" ry="8.5" transform="rotate(-32 133 130)" />
                    <ellipse cx="121" cy="152" rx="4.5" ry="8" transform="rotate(-28 121 152)" />
                    <ellipse cx="110" cy="172" rx="4" ry="7.5" transform="rotate(-22 110 172)" />
                </g>
                <!-- Awns & fine texture whiskers -->
                <path d="M126 45 L134 30 M112 63 L118 48 M160 83 L170 68 M146 101 L154 88" stroke-width="0.9" />
            </svg>

            <!-- Right Margin Ornament: Textured Sepia Engraving of Traditional Sundanese Leuit (NO Cross on Roof) -->
            <svg aria-hidden="true" class="pointer-events-none hidden lg:block absolute -right-10 -bottom-6 w-88 h-88 text-[#6E471B] opacity-[0.19]"
                viewBox="0 0 340 340" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <!-- Traditional Sundanese Julang Ngapak Roof (Clean Curved Horned Ridge — Zero Cross) -->
                <path d="M44 134 L170 42 L296 134" stroke-width="2.2" />
                <path d="M26 146 L170 54 L314 146" stroke-width="1.8" />
                <!-- Curved Bamboo Gable Horns (Capit Hurang) at apex -->
                <path d="M150 32 Q165 44, 182 54 M190 32 Q175 44, 158 54" stroke-width="1.8" />
                <!-- Layered Ijuk / Rumbia Thatch Hatching Texture -->
                <g stroke-width="0.95" opacity="0.75">
                    <path d="M90 100 L72 136 M112 84 L92 136 M134 68 L114 136 M156 54 L138 136 M170 50 L170 100" />
                    <path d="M186 54 L204 136 M208 68 L228 136 M230 84 L250 136 M252 100 L270 136" />
                </g>
                <!-- Trapezoid Woven Bamboo Leuit Body (Anyaman Bilik Texture) -->
                <path d="M84 136 L96 244 L244 244 L256 136" stroke-width="2.2" fill="currentColor" fill-opacity="0.04" />
                <g stroke-width="1.15">
                    <line x1="87" y1="163" x2="253" y2="163" />
                    <line x1="90" y1="190" x2="250" y2="190" />
                    <line x1="93" y1="217" x2="247" y2="217" />
                    <line x1="128" y1="136" x2="132" y2="244" />
                    <line x1="170" y1="136" x2="170" y2="244" />
                    <line x1="212" y1="136" x2="208" y2="244" />
                </g>
                <!-- Woven diagonal bamboo hatching inside bilik panels -->
                <g stroke-width="0.8" opacity="0.6">
                    <path d="M95 142 L120 158 M120 142 L95 158 M136 168 L164 185 M164 168 L136 185 M176 196 L204 212 M204 196 L176 212 M216 142 L244 158 M244 142 L216 158" />
                    <path d="M98 196 L124 212 M124 196 L98 212 M138 222 L164 238 M216 222 L240 238" />
                </g>
                <!-- Upper Granary Grain Door -->
                <rect x="155" y="102" width="30" height="30" rx="2" stroke-width="1.8" fill="currentColor" fill-opacity="0.08" />
                <line x1="170" y1="102" x2="170" y2="132" stroke-width="1" />
                <!-- Timber Stilts, Geledeg Anti-Pest Wooden Discs & Foundation Stones (Tatapakan) -->
                <line x1="112" y1="244" x2="112" y2="294" stroke-width="2.4" />
                <line x1="228" y1="244" x2="228" y2="294" stroke-width="2.4" />
                <ellipse cx="112" cy="256" rx="16" ry="5" stroke-width="1.6" fill="currentColor" fill-opacity="0.08" />
                <ellipse cx="228" cy="256" rx="16" ry="5" stroke-width="1.6" fill="currentColor" fill-opacity="0.08" />
                <ellipse cx="112" cy="300" rx="22" ry="7" stroke-width="1.8" fill="currentColor" fill-opacity="0.07" />
                <ellipse cx="228" cy="300" rx="22" ry="7" stroke-width="1.8" fill="currentColor" fill-opacity="0.07" />
                <path d="M16 312 Q96 305, 178 310 T326 306" stroke-width="1.4" />
                <path d="M36 320 Q130 315, 290 321" stroke-width="0.9" stroke-dasharray="6 5" />
            </svg>

            <div class="relative z-10 mx-auto max-w-6xl px-5 sm:px-8">
                <div class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12 lg:gap-14">

                    <!-- Left Editorial Prose (6 cols) -->
                    <div class="lg:col-span-6 lg:pt-4 space-y-7">
                        <div data-aos="fade-up" data-aos-delay="0" class="border-l-2 border-primary pl-4">
                            <p class="font-display text-sm italic text-primary sm:text-base">
                                “Mipit kudu amit, ngala kudu ménta, ngukur kudu saadilna.”
                            </p>
                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                                Papatah Sunda · Tentang Kejujuran Panen &amp; Timbangan
                            </p>
                        </div>

                        <h1 data-aos="fade-up" data-aos-delay="80"
                            class="font-display text-4xl font-semibold leading-[1.08] tracking-[-0.02em] text-ink sm:text-5xl lg:text-[3.5rem]">
                            Dari Lumbung Tradisi, Menjaga Jujurnya Padi <span class="italic font-normal text-primary">Nusantara.</span>
                        </h1>

                        <p data-aos="fade-up" data-aos-delay="160"
                            class="text-base leading-[1.75] text-ink-soft sm:text-[17px]">
                            Di tanah Pasundan, leluhur mendirikan <strong>Leuit</strong> bukan sekadar untuk menumpuk ikatan padi, melainkan menjaga amanah pangan kampung agar tak dimakan hama atau hilang hitungan.
                        </p>

                        <p data-aos="fade-up" data-aos-delay="220"
                            class="text-base leading-[1.75] text-ink-soft sm:text-[17px]">
                            <strong>SiLeuit.id</strong> meneruskan watak lumbung itu dalam catatan bersama dari Jawa Barat: memastikan gabah petani ditimbang jujur dan dibayar sesuai mutunya, beras yang lama tersimpan keluar lebih dulu, serta pasokan sampai ke warung tanpa selisih yang menguap.
                        </p>

                        <div data-aos="fade-up" data-aos-delay="280" class="flex flex-wrap items-center gap-5 pt-2">
                            <a href="{{ $entryUrl }}"
                                class="inline-flex h-12 items-center gap-2.5 rounded-[8px] bg-primary px-7 text-sm font-semibold tracking-wide text-cream shadow-sm transition-all hover:bg-primary-dark">
                                <span>{{ $user ? 'Lanjut ke Dasbor' : 'Masuk ke SiLeuit.id' }}</span>
                                <span aria-hidden="true">→</span>
                            </a>
                            <a href="#filosofi"
                                class="inline-flex h-12 items-center border-b border-ink/30 pb-0.5 text-sm font-medium text-ink hover:border-primary hover:text-primary transition-colors">
                                Baca Akar Filosofi ↓
                            </a>
                        </div>
                    </div>

                    <!-- Right Uncluttered Documentary Photograph (6 cols) -->
                    <div class="lg:col-span-6" data-aos="fade-up" data-aos-delay="140">
                        <figure class="space-y-3">
                            <div class="overflow-hidden rounded-[6px] border border-line bg-cream/60 p-2 shadow-sm">
                                <div class="overflow-hidden rounded-[4px] aspect-[4/3]">
                                    <img src="/images/leuit-sunda.jpg"
                                        alt="Deretan bangunan Leuit lumbung padi tradisional beratap rumbia dan berdinding bilik bambu di Tatar Sunda"
                                        class="h-full w-full object-cover transition-transform duration-1000 hover:scale-[1.02]"
                                        loading="eager">
                                </div>
                            </div>
                            <figcaption class="flex items-start justify-between gap-4 border-t border-line/60 pt-2.5 text-xs leading-relaxed text-ink-soft">
                                <span>
                                    <strong class="font-display not-italic text-ink">Fig. 01 — Leuit Sunda.</strong>
                                    Lumbung berpanggung batu dengan dinding anyaman bambu yang menjaga gabah bertahan bertahun-tahun dari lembap dan tikus.
                                </span>
                                <span class="shrink-0 font-mono text-[10px] text-ink-soft/75">Banten · Jawa Barat</span>
                            </figcaption>
                        </figure>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION 1: FILOSOFI & AKAR BUDAYA (Museum Botanical Plate + Faded Rice Terrace Contour Background) -->
        <section id="filosofi" class="relative overflow-hidden border-y border-line bg-cream/45 py-20 sm:py-28">

            <!-- Right Terrain Watermark: Bold Textured Sundanese Terasering Sawah Contours -->
            <svg aria-hidden="true" class="pointer-events-none hidden md:block absolute -right-16 top-6 w-[560px] h-[480px] text-[#6E471B] opacity-[0.17]"
                viewBox="0 0 560 480" fill="none" stroke="currentColor" stroke-linecap="round">
                <!-- Terraced bund outer lines (double thickness for earth embankment texture) -->
                <g stroke-width="1.8">
                    <path d="M30 85 C165 40, 340 135, 545 75" />
                    <path d="M15 130 C175 80, 330 185, 545 125" />
                    <path d="M0 180 C190 125, 320 235, 545 175" />
                    <path d="M0 235 C205 175, 310 290, 545 230" />
                    <path d="M25 295 C220 230, 315 345, 545 290" />
                    <path d="M60 355 C240 290, 345 395, 545 350" />
                </g>
                <!-- Subtle dashed water-reflection ripples & embankment shading -->
                <g stroke-width="0.9" stroke-dasharray="10 7" opacity="0.7">
                    <path d="M55 104 C170 68, 335 154, 520 100" />
                    <path d="M40 154 C180 108, 325 204, 520 150" />
                    <path d="M30 206 C195 156, 315 256, 520 202" />
                    <path d="M55 262 C210 206, 310 310, 520 258" />
                </g>
                <!-- Rice tufts textured along the bund steps -->
                <g stroke-width="1.2">
                    <path d="M360 124 l-3-16 M365 123 v-18 M370 122 l3-15" />
                    <path d="M210 158 l-3-16 M215 157 v-18 M220 158 l3-14" />
                    <path d="M410 232 l-3-16 M415 231 v-18 M420 231 l3-15" />
                    <path d="M265 270 l-3-16 M270 269 v-18 M275 270 l3-14" />
                </g>
            </svg>

            <div class="relative z-10 mx-auto max-w-6xl px-5 sm:px-8">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-16">

                    <!-- Left: Heritage 1887 Oryza sativa Botanical Engraving Plate -->
                    <div class="lg:col-span-5" data-aos="fade-right" data-aos-delay="60">
                        <figure class="rounded-[6px] border border-line bg-paper p-6 sm:p-8 shadow-xs">
                            <div class="flex items-center justify-between border-b border-line/60 pb-3 text-[10px] font-mono uppercase tracking-[0.18em] text-ink-soft">
                                <span>Arsip Botani · Padi</span>
                                <span>Oryza sativa L.</span>
                            </div>
                            <div class="py-5">
                                <img src="/images/ilustrasi-padi-kohler.jpg"
                                    alt="Ilustrasi botani klasik tanaman padi Oryza sativa dari akar hingga bulir gabah"
                                    class="mx-auto max-h-[420px] w-auto object-contain mix-blend-multiply"
                                    loading="lazy">
                            </div>
                            <figcaption class="border-t border-line/60 pt-3 text-xs leading-relaxed text-ink-soft">
                                Anatomi sebutir gabah: kadar air, keutuhan butir, dan kebersihan kulit menjadi penentu alami mutu beras sejak dahulu kala.
                            </figcaption>
                        </figure>
                    </div>

                    <!-- Right: Deep Cultural Parallels -->
                    <div class="lg:col-span-7 space-y-6">
                        <div data-aos="fade-up" data-aos-delay="0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-warning">Mengapa Harus Seperti Leuit</p>
                            <h2 class="mt-2 font-display text-3xl font-semibold leading-tight text-ink sm:text-4xl">
                                Kepercayaan Desa Runtuh Bila Timbangan dan Harga Gelap.
                            </h2>
                        </div>

                        <div class="divide-y divide-line border-t border-b border-line">
                            <div class="py-6" data-aos="fade-up" data-aos-delay="80">
                                <h3 class="font-display text-lg font-bold text-primary">1. Takaran yang Ditimbang Bersama</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                                    Sebelum panen dibawa ke gudang, perkiraan hasilnya sudah dicatat sejak awal musim. Bila hasil timbang menyusut jauh dari perkiraan, barang tidak bisa langsung disahkan petugas—harus diperiksa dan disetujui lebih dulu oleh Kepala Gudang dengan alasan tertulis.
                                </p>
                            </div>

                            <div class="py-6" data-aos="fade-up" data-aos-delay="140">
                                <h3 class="font-display text-lg font-bold text-primary">2. Mutu Objektif, Bukan Tawar-Menawar Sepihak</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                                    Harga beli gabah tidak ditentukan bisik-bisik di pos timbang. Angka kadar air otomatis menetapkan kelas Mutu A, B, atau C beserta harga resminya. Jika gabah masih basah di atas 18%, penolakan tertulis tampil terang di layar ponsel petani agar dapat dijemur kembali.
                                </p>
                            </div>

                            <div class="py-6" data-aos="fade-up" data-aos-delay="200">
                                <h3 class="font-display text-lg font-bold text-primary">3. Ikatan Lama Keluar Lebih Dulu</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                                    Sebagaimana orang tua mengambil ikatan padi paling lama di sudut leuit, sistem menandai setiap karung dengan tanggal simpan. Stok yang paling dekat batas simpannya otomatis didahulukan saat keluar, sehingga tidak ada beras yang membusuk diam-diam.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION 2: PERJALANAN PADI (Sunrise Harvest Spread + 4 Ruled Chapters + Sack & Balance Scale Side Ornaments) -->
        <section id="babak" class="relative overflow-hidden py-20 sm:py-28">

            <!-- Left Margin Ornament: Bold Cross-Hatched Burlap Grain Sacks (Karung Beras) -->
            <svg aria-hidden="true" class="pointer-events-none hidden xl:block absolute -left-8 bottom-20 w-72 h-72 text-[#6E471B] opacity-[0.20]"
                viewBox="0 0 260 260" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <!-- Primary Grain Sack -->
                <path d="M54 106 C36 130, 32 182, 44 214 C62 224, 114 224, 132 214 C142 182, 136 130, 118 106" stroke-width="2.1" fill="currentColor" fill-opacity="0.05" />
                <path d="M70 86 C76 98, 96 98, 102 86 C108 74, 98 64, 86 68 C74 64, 64 74, 70 86 Z" stroke-width="1.8" />
                <path d="M62 106 Q86 112, 110 106" stroke-width="1.8" />
                <!-- Burlap woven crosshatch texture inside Sack 1 -->
                <g stroke-width="0.9" opacity="0.7">
                    <path d="M54 134 Q86 140, 118 134 M50 158 Q86 164, 122 158 M52 182 Q86 188, 120 182" />
                    <path d="M72 120 v82 M86 118 v86 M100 120 v82" stroke-dasharray="3 4" />
                </g>
                <!-- Secondary Sack Behind -->
                <path d="M134 120 C150 138, 154 182, 144 210 C158 220, 200 220, 214 208 C222 180, 216 138, 198 118" stroke-width="1.9" fill="currentColor" fill-opacity="0.04" />
                <path d="M150 100 C156 112, 174 112, 180 100 C184 90, 174 82, 165 86 C154 82, 146 90, 150 100 Z" stroke-width="1.7" />
                <path d="M146 118 Q166 124, 188 118" stroke-width="1.7" />
                <path d="M150 156 Q178 162, 206 156 M148 180 Q178 186, 208 180" stroke-width="0.9" opacity="0.65" />
                <!-- Floor Line & Scattered Grains -->
                <path d="M16 228 Q125 223, 244 229" stroke-width="1.4" />
                <ellipse cx="152" cy="236" rx="4.5" ry="2.2" stroke-width="1.2" fill="currentColor" fill-opacity="0.15" />
                <ellipse cx="170" cy="238" rx="4.5" ry="2.2" stroke-width="1.2" fill="currentColor" fill-opacity="0.15" />
                <ellipse cx="192" cy="235" rx="4" ry="2" stroke-width="1.2" />
            </svg>

            <!-- Right Margin Ornament: Textured Woodcut Balance Beam Scale (Timbangan Dacin) -->
            <svg aria-hidden="true" class="pointer-events-none hidden xl:block absolute -right-6 top-[44%] w-72 h-72 text-[#6E471B] opacity-[0.20]"
                viewBox="0 0 260 260" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <line x1="130" y1="28" x2="130" y2="58" stroke-width="2" />
                <circle cx="130" cy="20" r="7" stroke-width="2" />
                <!-- Main Beam with Double Wood-Grain Line -->
                <path d="M36 62 L224 52" stroke-width="2.6" />
                <path d="M40 66 L220 56" stroke-width="0.9" opacity="0.6" />
                <!-- Left Woven Tray Basket & Strings -->
                <line x1="54" y1="62" x2="34" y2="122" stroke-width="1.4" />
                <line x1="54" y1="62" x2="54" y2="122" stroke-width="1.1" />
                <line x1="54" y1="62" x2="74" y2="122" stroke-width="1.4" />
                <path d="M26 122 C28 144, 80 144, 82 122 Z" stroke-width="1.9" fill="currentColor" fill-opacity="0.06" />
                <path d="M34 132 Q54 138, 74 132" stroke-width="0.9" />
                <!-- Right Iron Counterweight (Bandul) with Hatching -->
                <line x1="184" y1="54" x2="184" y2="100" stroke-width="1.6" />
                <path d="M172 100 L196 100 L202 128 L166 128 Z" stroke-width="2" fill="currentColor" fill-opacity="0.08" />
                <path d="M172 108 L196 108 M169 118 L199 118" stroke-width="0.9" />
                <!-- Calibration Notches -->
                <path d="M84 53 v7 M98 52 v7 M112 51 v7 M146 50 v7 M160 49 v7 M198 47 v7" stroke-width="1.3" />
            </svg>

            <div class="relative z-10 mx-auto max-w-6xl px-5 sm:px-8">

                <div class="max-w-2xl" data-aos="fade-up">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-warning">Laku Satu Siklus Penuh</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold leading-tight text-ink sm:text-4xl">
                        Empat Babak Perjalanan Padi, Tanpa Mata Rantai yang Putus.
                    </h2>
                </div>

                <!-- Panoramic Indonesian Rice Harvest Photograph -->
                <figure class="mt-10 space-y-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="overflow-hidden rounded-[6px] border border-line bg-cream/50 p-2 shadow-sm">
                        <div class="overflow-hidden rounded-[4px] aspect-[16/9] sm:aspect-[21/9]">
                            <img src="/images/panen-sawah.jpg"
                                alt="Petani Indonesia memanen padi menguning di pagi hari berlatar perbukitan dan pohon kelapa"
                                class="h-full w-full object-cover object-center"
                                loading="lazy">
                        </div>
                    </div>
                    <figcaption class="flex flex-wrap items-center justify-between gap-2 text-xs text-ink-soft">
                        <span><strong class="text-ink">Fig. 02 — Panen di Tanah Jawa.</strong> Setiap ikatan yang dipotong memiliki jalur catatannya sendiri sampai menjadi beras di tangan pembeli.</span>
                        <span class="font-mono text-[10px]">Kontrak Musim → Pos Timbang → Gudang → Cabang</span>
                    </figcaption>
                </figure>

                <!-- 4 Editorial Ledger Chapters -->
                <div class="mt-16 border-t border-line">

                    <!-- Babak I -->
                    <div class="grid grid-cols-1 gap-4 border-b border-line py-9 md:grid-cols-12 md:gap-8" data-aos="fade-up" data-aos-delay="0">
                        <div class="md:col-span-2">
                            <span class="font-display text-3xl font-semibold text-primary">Babak I</span>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-ink-soft">Awal Musim &amp; Panen</p>
                        </div>
                        <div class="md:col-span-4">
                            <h3 class="font-display text-xl font-bold text-ink">Kesepakatan Tanam, Timbang Jujur, &amp; Bukti Bayar Terbuka</h3>
                        </div>
                        <div class="md:col-span-6">
                            <p class="text-sm leading-[1.8] text-ink-soft">
                                Sebelum sabit menyentuh batang padi, rencana panen sudah disepakati bersama pemilik lahan. Saat panen datang ke pos timbang gudang, berat bersih dicatat, kadar air diuji, dan harga terkunci otomatis. Keuangan mencatat pelunasan lengkap dengan foto struk transfer agar petani bisa membuktikannya kapan saja lewat ponsel.
                            </p>
                        </div>
                    </div>

                    <!-- Babak II -->
                    <div class="grid grid-cols-1 gap-4 border-b border-line py-9 md:grid-cols-12 md:gap-8" data-aos="fade-up" data-aos-delay="60">
                        <div class="md:col-span-2">
                            <span class="font-display text-3xl font-semibold text-primary">Babak II</span>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-ink-soft">Gudang &amp; Pengolahan</p>
                        </div>
                        <div class="md:col-span-4">
                            <h3 class="font-display text-xl font-bold text-ink">Dari Gabah Menjadi Beras Siap Edar &amp; Pindah Antargudang</h3>
                        </div>
                        <div class="md:col-span-6">
                            <p class="text-sm leading-[1.8] text-ink-soft">
                                Gabah yang dikirim ke penggilingan dicatat keluar secara resmi, lalu beras hasilnya masuk kembali sebagai stok siap jual bertanggal baru. Ketika stok dipindah dari satu gudang ke gudang lain, muatan yang berkurang di jalan dicatat sebagai selisih terbuka untuk diselidiki—tidak pernah lenyap dari buku.
                            </p>
                        </div>
                    </div>

                    <!-- Babak III -->
                    <div class="grid grid-cols-1 gap-4 border-b border-line py-9 md:grid-cols-12 md:gap-8" data-aos="fade-up" data-aos-delay="120">
                        <div class="md:col-span-2">
                            <span class="font-display text-3xl font-semibold text-primary">Babak III</span>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-ink-soft">Armada &amp; Retur</p>
                        </div>
                        <div class="md:col-span-4">
                            <h3 class="font-display text-xl font-bold text-ink">Kurir Membawa Surat Jalan, Barang Rusak Masuk Karantina</h3>
                        </div>
                        <div class="md:col-span-6">
                            <p class="text-sm leading-[1.8] text-ink-soft">
                                Pesanan ke toko cabang maupun warung langganan disiapkan mendahulukan karung paling tua. Kurir membawa Surat Jalan tercetak dan mencatat berat yang benar-benar diterima pelanggan. Bila ada kemasan robek atau lembap, pengajuan pengembalian divalidasi terhadap kiriman aslinya dan dipisahkan ke karantina gudang.
                            </p>
                        </div>
                    </div>

                    <!-- Babak IV -->
                    <div class="grid grid-cols-1 gap-4 border-b border-line py-9 md:grid-cols-12 md:gap-8" data-aos="fade-up" data-aos-delay="180">
                        <div class="md:col-span-2">
                            <span class="font-display text-3xl font-semibold text-primary">Babak IV</span>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-ink-soft">Cabang &amp; Warung</p>
                        </div>
                        <div class="md:col-span-4">
                            <h3 class="font-display text-xl font-bold text-ink">Kasir Menjaga HET, Buku Piutang Mengingat Tagihan Tertua</h3>
                        </div>
                        <div class="md:col-span-6">
                            <p class="text-sm leading-[1.8] text-ink-soft">
                                Di cabang ritel, kasir tidak bisa menjual melebihi stok yang tersisa dan langsung diingatkan atau diblokir bila harga melebihi Harga Eceran Tertinggi (HET). Sementara untuk warung mitra yang berlangganan tempo, batas kredit dijaga ketat dan pembayaran cicilan otomatis melunasi faktur yang jatuh tempo paling awal.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION 3: DUA FOTO SAKSI LAPANGAN (Menampi Gabah & Karung Beras Tersimpan) -->
        <section id="ketertiban" class="relative overflow-hidden border-t border-line bg-cream/35 py-20 sm:py-28">

            <!-- Center-Right Textured Rice Crest Seal -->
            <svg aria-hidden="true" class="pointer-events-none hidden lg:block absolute right-6 top-10 w-52 h-52 text-[#6E471B] opacity-[0.18]"
                viewBox="0 0 180 180" fill="none" stroke="currentColor">
                <circle cx="90" cy="90" r="70" stroke-width="1.6" stroke-dasharray="6 4" />
                <circle cx="90" cy="90" r="62" stroke-width="0.9" opacity="0.6" />
                <path d="M90 144 C90 100, 90 64, 90 34" stroke-width="2" />
                <g stroke-width="1.4" fill="currentColor" fill-opacity="0.09">
                    <ellipse cx="77" cy="56" rx="5.5" ry="11" transform="rotate(-30 77 56)" />
                    <ellipse cx="103" cy="56" rx="5.5" ry="11" transform="rotate(30 103 56)" />
                    <ellipse cx="73" cy="82" rx="5.5" ry="12" transform="rotate(-38 73 82)" />
                    <ellipse cx="107" cy="82" rx="5.5" ry="12" transform="rotate(38 107 82)" />
                    <ellipse cx="77" cy="108" rx="5.5" ry="11" transform="rotate(-32 77 108)" />
                    <ellipse cx="103" cy="108" rx="5.5" ry="11" transform="rotate(32 103 108)" />
                </g>
            </svg>

            <div class="relative z-10 mx-auto max-w-6xl px-5 sm:px-8">

                <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                    <!-- Photo 1: Real Traditional Farmers Winnowing Paddy with Nyiru -->
                    <div class="lg:col-span-6" data-aos="fade-up" data-aos-delay="0">
                        <figure class="space-y-4">
                            <div class="overflow-hidden rounded-[6px] border border-line bg-paper p-2 shadow-xs">
                                <div class="overflow-hidden rounded-[4px] aspect-[4/3]">
                                    <img src="/images/menampi-padi.jpg"
                                        alt="Petani Indonesia menampi gabah menggunakan nyiru bambu di pematang sawah untuk memilah butir hampa dan bernas"
                                        class="h-full w-full object-cover"
                                        loading="lazy">
                                </div>
                            </div>
                            <figcaption class="space-y-2 pt-1">
                                <h3 class="font-display text-xl font-bold text-ink">Tetap Bekerja Ketika Sinyal Desa Hilang</h3>
                                <p class="text-sm leading-relaxed text-ink-soft">
                                    Sebagaimana petani memilah gabah bernas dan hampa dengan nyiru di sawah, pos timbang tetap mencatat kiriman secara luring ketika jaringan pedesaan padam dan menyelaraskan ulang begitu sinyal kembali—tanpa menggandakan stok atau tagihan.
                                </p>
                            </figcaption>
                        </figure>
                    </div>

                    <!-- Photo 2: Real Tied Grain Sacks Stored Cleanly in Warehouse -->
                    <div class="lg:col-span-6" data-aos="fade-up" data-aos-delay="120">
                        <figure class="space-y-4">
                            <div class="overflow-hidden rounded-[6px] border border-line bg-paper p-2 shadow-xs">
                                <div class="overflow-hidden rounded-[4px] aspect-[4/3]">
                                    <img src="/images/karung-beras.jpg"
                                        alt="Tumpukan karung goni gabah dan beras terikat rapi tersimpan di dalam gudang"
                                        class="h-full w-full object-cover"
                                        loading="lazy">
                                </div>
                            </div>
                            <figcaption class="space-y-2 pt-1">
                                <h3 class="font-display text-xl font-bold text-ink">Buku Besar yang Tak Bisa Dihapus Diam-Diam</h3>
                                <p class="text-sm leading-relaxed text-ink-soft">
                                    Setiap karung masuk, susut pengeringan, hingga percobaan membuka catatan milik gudang lain terekam berurutan dan tidak dapat dihapus. Jika ada kekeliruan, perbaikannya ditulis dengan catatan pembalik resmi yang beralasan jelas.
                                </p>
                            </figcaption>
                        </figure>
                    </div>
                </div>

                <!-- Summary of Who Works Inside (Plain Human Columns) -->
                <div class="mt-20 border-t border-line pt-14" data-aos="fade-up" data-aos-delay="60">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <div>
                            <p class="font-mono text-xs uppercase tracking-widest text-warning">01 · Sisi Hulu</p>
                            <h4 class="mt-2 font-display text-lg font-bold text-ink">Petani &amp; Penjaga Pos Timbang</h4>
                            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                                Petani memantau kelas mutu, alasan penolakan, dan bukti transfer langsung di ponsel. Petugas dan Kepala Gudang menimbang serta menjaga batas susut panen.
                            </p>
                        </div>
                        <div>
                            <p class="font-mono text-xs uppercase tracking-widest text-warning">02 · Sisi Jalan &amp; Toko</p>
                            <h4 class="mt-2 font-display text-lg font-bold text-ink">Kurir, Kasir, &amp; Warung Mitra</h4>
                            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                                Kurir memastikan kiriman sampai sesuai Surat Jalan. Kasir melayani penjualan harian dengan batas harga wajar dan stok yang tak pernah tercatat minus.
                            </p>
                        </div>
                        <div>
                            <p class="font-mono text-xs uppercase tracking-widest text-warning">03 · Sisi Penjaga Amanah</p>
                            <h4 class="mt-2 font-display text-lg font-bold text-ink">Keuangan &amp; Pemilik Lumbung</h4>
                            <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                                Keuangan menerbitkan faktur dan menertibkan umur piutang. Pemilik memantau seluruh gudang, menyetujui harga resmi, serta memeriksa jejak audit.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- UNIFIED CLOSING HORIZON + MAXIMIZED RICE FIELD FOOTER (Real Sundanese Paddy Terrace Background + Outline Silhouette) -->
        <div class="relative overflow-hidden border-t border-line text-cream">
            <!-- Real West Java Tasikmalaya Terraced Rice Field Background -->
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style="background-image: url('/images/sawah-tasik.jpg');"></div>

            <!-- Deep Earthy Forest & Twilight Scrim -->
            <div class="absolute inset-0 bg-gradient-to-b from-primary-dark/88 via-[#182B15]/90 to-[#111E0F]/96"></div>

            <!-- Rich Textured Sepia/Gold Horizon Outline of Hills, Terraced Sawah & Distant Leuit -->
            <svg aria-hidden="true" preserveAspectRatio="none"
                class="pointer-events-none absolute bottom-0 inset-x-0 w-full h-64 text-[#E3C48A] opacity-[0.22]"
                viewBox="0 0 1440 240" fill="none" stroke="currentColor">
                <!-- Rolling West Java hills with double contours -->
                <path d="M0 165 Q240 100, 480 155 T960 132 T1440 158" stroke-width="1.8" />
                <path d="M0 174 Q240 112, 480 164 T960 142 T1440 167" stroke-width="0.9" stroke-dasharray="10 6" />
                <path d="M0 192 Q310 140, 620 185 T1180 165 T1440 190" stroke-width="1.6" />
                <path d="M0 218 Q400 180, 820 212 T1440 205" stroke-width="1.4" />
                <!-- Distant Leuit Granary Silhouettes (Zero Cross, Textured Bamboo & Stilts) -->
                <g stroke-width="1.6" fill="currentColor" fill-opacity="0.06">
                    <path d="M206 146 L232 122 L258 146 M215 146 L218 170 L246 170 L249 146" />
                    <path d="M225 117 Q232 122, 239 117" stroke-width="1.1" />
                    <path d="M262 150 L282 130 L302 150 M268 150 L271 171 L293 171 L296 150" />
                    <path d="M1136 142 L1164 118 L1192 142 M1146 142 L1149 169 L1179 169 L1182 142" />
                    <path d="M1156 113 Q1164 118, 1172 113" stroke-width="1.1" />
                </g>
            </svg>

            <!-- Closing Horizon Callout -->
            <section class="relative z-10 py-24 sm:py-28">
                <div class="mx-auto max-w-3xl px-5 text-center sm:px-8">
                    <p data-aos="fade-up" data-aos-delay="0"
                        class="font-display text-sm italic text-tan">
                        “Lumbung yang lestari adalah lumbung yang tahu ke mana perginya setiap ikatan padi.”
                    </p>

                    <h2 data-aos="fade-up" data-aos-delay="80"
                        class="mt-4 font-display text-3xl font-semibold leading-tight text-paper sm:text-5xl">
                        Setiap Karung Terlacak. Setiap Timbangan Terjaga.
                    </h2>

                    <p data-aos="fade-up" data-aos-delay="160"
                        class="mx-auto mt-5 max-w-xl text-sm leading-relaxed text-cream/85 sm:text-base">
                        Masuk ke ruang kerja SiLeuit.id menggunakan akun yang telah diberikan oleh pengelola lumbung atau koperasi Anda.
                    </p>

                    <div data-aos="fade-up" data-aos-delay="220" class="mt-9">
                        <a href="{{ $entryUrl }}"
                            class="inline-flex h-12 items-center gap-2.5 rounded-[8px] bg-cream px-8 text-sm font-bold tracking-wide text-primary-dark shadow-lg transition-all hover:bg-tan">
                            <span>{{ $user ? 'Buka Ruang Kerja' : 'Masuk ke SiLeuit.id' }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Maximized Rich Agrarian Footer Over Paddy Terrace Backdrop -->
            <footer class="relative z-10 border-t border-white/15 bg-black/20 backdrop-blur-[2px]">
                <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8">
                    <div class="grid grid-cols-1 gap-10 md:grid-cols-12">

                        <!-- Col 1: Brand & Root Story (5 cols) -->
                        <div class="md:col-span-5 space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-[8px] border border-tan/40 bg-white/10 text-tan">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 10.5L12 3.5l9 7" />
                                        <path d="M6.5 10.5l1 9.5h9l1-9.5" />
                                        <path d="M6 20h12" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-display text-xl font-bold text-paper">SiLeuit.id</p>
                                    <p class="text-[10px] font-medium uppercase tracking-[0.18em] text-tan">Lumbung Padi Digital Nusantara</p>
                                </div>
                            </div>
                            <p class="max-w-sm text-xs leading-relaxed text-cream/75">
                                Terinspirasi oleh kearifan masyarakat Sunda dalam merawat ketahanan pangan kampung. Menghubungkan petani, pengelola gudang, kurir, hingga warung rakyat dalam satu buku catatan yang jujur dan tahan waktu.
                            </p>
                        </div>

                        <!-- Col 2: Navigasi Laman (3 cols) -->
                        <div class="md:col-span-3 space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-tan">Jelajah Cerita</p>
                            <ul class="space-y-2.5 text-xs text-cream/80">
                                <li><a href="#filosofi" class="hover:text-paper transition-colors">Filosofi Leuit Sunda</a></li>
                                <li><a href="#babak" class="hover:text-paper transition-colors">Empat Babak Perjalanan Padi</a></li>
                                <li><a href="#ketertiban" class="hover:text-paper transition-colors">Ketertiban &amp; Ketahanan Sinyal</a></li>
                                <li><a href="{{ $entryUrl }}" class="font-semibold text-tan hover:underline">{{ $user ? 'Buka Dasbor →' : 'Masuk Sistem →' }}</a></li>
                            </ul>
                        </div>

                        <!-- Col 3: Empat Pegangan Lumbung (4 cols) -->
                        <div class="md:col-span-4 space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-tan">Pegangan Utama SiLeuit</p>
                            <div class="grid grid-cols-2 gap-2.5 text-[11px] text-cream/80">
                                <div class="rounded-[6px] border border-white/10 bg-white/5 px-3 py-2">
                                    <span class="block font-semibold text-paper">Timbang Adil</span>
                                    <span>Berpatokan rencana panen</span>
                                </div>
                                <div class="rounded-[6px] border border-white/10 bg-white/5 px-3 py-2">
                                    <span class="block font-semibold text-paper">Mutu Jelas</span>
                                    <span>Harga otomatis A · B · C</span>
                                </div>
                                <div class="rounded-[6px] border border-white/10 bg-white/5 px-3 py-2">
                                    <span class="block font-semibold text-paper">Tua Duluan</span>
                                    <span>Cegah beras menumpuk</span>
                                </div>
                                <div class="rounded-[6px] border border-white/10 bg-white/5 px-3 py-2">
                                    <span class="block font-semibold text-paper">Jejak Utuh</span>
                                    <span>Catatan tak bisa dihapus</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Colophon & Heritage Bottom Strip -->
                    <div class="mt-12 flex flex-col items-start justify-between gap-3 border-t border-white/15 pt-6 text-[11px] text-cream/65 sm:flex-row sm:items-center">
                        <p>SiLeuit.id · Tata Niaga Gabah &amp; Beras Jawa Barat · Indonesia</p>
                        <p class="font-display italic text-tan/90">“Silih asah, silih asih, silih asuh dina ngajaga pangan.”</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</x-layouts.app>

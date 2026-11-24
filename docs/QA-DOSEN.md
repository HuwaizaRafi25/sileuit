# Q&A DOSEN — SiLeuit.id
Dokumen pertahanan presentasi: definisi istilah + alasan pilihan teknologi.
Bertambah setiap fase. Bahasa sengaja lugas agar bisa diucapkan langsung.

## Fase 0 — Stack & Fondasi

**1. Apa itu SiLeuit.id?**
Platform web rantai pasok gabah–beras: dari petani mitra ke 3 gudang Jawa Barat,
ke cabang, retail B2B, dan konsumen. Intinya ketertelusuran per lot, transparansi
harga & mutu, stok anti-negatif, dan piutang tertib.

**2. Kenapa Laravel, bukan Next.js atau Express?**
Laravel memberi satu ekosistem lengkap (ORM, auth, validasi, queue, transaksi DB)
sehingga tim 5 orang fokus ke logika bisnis, bukan merakit infrastruktur.
SPA terpisah berarti API ganda + JWT yang menambah permukaan serangan (F.7).

**3. Apa itu modular monolith? Kenapa bukan microservices?**
Satu aplikasi yang bisa di-deploy sekali, tapi kodenya dipecah per modul domain
(IdentityAccess, ProcurementReceiving, InventoryLedger, dst.) yang berkomunikasi
via domain event. Microservices butuh distributed transaction & tracing —
tidak realistis untuk skala dan waktu kami (F.7).

**4. Kenapa MySQL, bukan PostgreSQL atau MongoDB?**
Stok dan uang butuh transaksi ACID + kunci baris (SELECT ... FOR UPDATE);
InnoDB MySQL 8 menyediakannya. MongoDB tidak punya transaksi multi-tabel native
yang setara. PostgreSQL sama mampu, tapi tim memutuskan MySQL (R-03) karena
lebih umum di hosting UMKM dan sudah dikuasai. Fitur Postgres di model data
diterjemahkan ke padanan MySQL (lihat DEVIATION-LOG DEV-001).

**5. Apa itu Redis? Kenapa dipakai? Bedanya dengan MySQL?**
Redis = penyimpan data in-memory (RAM) berstruktur key-value, sangat cepat.
Kami pakai untuk cache, antrean job (alert, pengingat), dan sesi login —
bukan penyimpan data utama. Data transaksi tetap di MySQL yang tahan lama (disk).
Kalau Redis mati, data bisnis tidak hilang.

**6. Apa itu Docker? Kenapa Redis/MinIO di Docker tapi PHP/MySQL di Laragon?**
Docker menjalankan layanan dalam kontainer terisolasi dengan versi terjamin.
Redis tidak punya build native Windows dan MinIO lebih praktis dikontainerkan;
PHP & MySQL sudah tersedia native di Laragon, jadi tidak perlu dibungkus.
Hasilnya: lingkungan dev seragam antar-laptop tim tanpa instalasi manual berat.

**7. Apa itu MinIO / S3? Kenapa foto tidak masuk database?**
MinIO = object storage kompatibel-API Amazon S3 yang jalan lokal. Foto timbangan,
sampel mutu, dan POD disimpan sebagai file (dikompres ke WebP <300KB), diakses
via presigned URL 15 menit. Menyimpan blob di database membuat DB bloat dan
backup lambat (F.6.2).

**8. Apa itu Filament? Kenapa v4, bukan v5?**
Filament = pembangun panel admin di atas Laravel+Livewire: tabel, form, filter,
aksi massal jadi cepat (~70% lebih sedikit kode CRUD). v4 dipilih karena dokumen
memutuskan v4 dan v4.9+ sudah mendukung Laravel 13; v5 hanya menambah dukungan
Livewire 4 tanpa perubahan fungsional — tidak ada manfaat mendesak untuk pindah.

**9. Apa itu Livewire? Kenapa bukan React/Vue?**
Livewire menulis komponen interaktif dalam PHP; state di-render di server dan
dikirim sebagai HTML partial. Tidak perlu API terpisah dan token JWT di browser,
lebih ringan untuk HP Android murah petani, dan sesi tetap di backend (F.7).

**10. Apa itu PWA?**
Progressive Web App: web yang bisa dipasang di homescreen dan tetap berfungsi
terbatas saat offline via Service Worker (cache aset) + IndexedDB (antrean
penerimaan). Sinkronisasi memakai idempotency key agar tidak dobel (F.8).

**11. Apa itu Composer, npm, dan Vite?**
Composer = manajer dependensi PHP; npm = manajer dependensi JavaScript;
Vite = bundler aset frontend (CSS/JS) dengan build cepat. composer.lock dan
package-lock.json mengunci versi persis agar semua anggota tim identik.

**12. Apa itu Pest dan CI (GitHub Actions)?**
Pest = kerangka tes di atas PHPUnit dengan sintaks ringkas. CI = pipeline yang
otomatis menjalankan lint (Pint), build, dan seluruh tes setiap push —
build gagal tidak boleh masuk staging (J.2, kriteria masuk uji Bagian I).

**13. Kenapa Argon2id untuk password?**
Algoritma hashing password modern yang memory-hard: mahal di GPU/ASIC sehingga
tahan brute force. Disyaratkan H.2; fallback bcrypt cost 12 diizinkan dokumen.

**14. Apa itu APP_KEY dan enkripsi AES-256?**
APP_KEY = kunci rahasia aplikasi Laravel untuk enkripsi simetris. Nomor HP dan
rekening mitra dienkripsi per-kolom di database (H.4): jika dump DB bocor,
data pribadi tetap tidak terbaca tanpa kunci server.

**15. Apa itu /healthz?**
Endpoint pemantau kesehatan ringan tanpa sesi: memeriksa database, Redis,
storage writable, dan sisa disk; mengembalikan 200 (healthy) atau 503 (degraded)
dalam format JSON terstruktur (J.4.1). Dipakai uptime monitor NFR-02.

**16. Kenapa sesi idle 30 menit?**
Perangkat gudang/kasir sering ditinggal di pos timbang. Sesi otomatis putus
setelah 30 menit tanpa aktivitas agar tidak disalahgunakan (NFR-04, H.3.2).
Di Laravel ini diatur lewat SESSION_LIFETIME=30.

**17. Apa itu idempotency key? (dipakai mulai Fase 2)**
Kunci unik per transaksi (UUID). Permintaan dengan kunci sama hanya diproses
sekali — klik ganda, retry jaringan, atau webhook ulang tidak menghasilkan
pembayaran/penerimaan dobel (BR-07, IR-02).

## Fase 1 — Akses, Master, dan Harga

**1. Apa itu RBAC dan Location Scoping?**
RBAC = hak akses berdasarkan peran (10 peran kami). Location scoping = lapisan kedua:
pengguna cakupan (G) hanya melihat baris berlokasi tugasnya, dipaksa di server lewat
global Eloquent scope + middleware, bukan sekadar menyembunyikan tombol (H.1).

**2. Apa itu IDOR dan bagaimana kami mencegahnya?**
Insecure Direct Object Reference: mengganti ID di URL/API untuk membaca data orang lain.
Dicegah tiga lapis: policy peran, filter lokasi di query, filter data sendiri (partner_id).
Percobaan gagal dijawab 403 dan otomatis tercatat di audit_logs (TC-08/25/26).

**3. Kenapa audit log append-only sampai level database?**
Karena kalau hanya kode aplikasi yang melarang, admin database tetap bisa menghapus jejak.
Kami pasang trigger MySQL BEFORE UPDATE/DELETE yang menolak (SIGNAL), sesuai IR-07/H.5.

**4. Kenapa nomor HP dienkripsi tapi ada kolom hp_hash?**
AES-256 (H.4) menghasilkan ciphertext berbeda tiap enkripsi, sehingga tidak bisa di-UNIQUE
atau dicari. hp_hash = SHA-256 deterministik untuk constraint unik & pencarian login,
tanpa membocorkan nomor asli saat dump DB bocor.

**5. Bagaimana IR-06 (periode harga tak tumpang tindih) di MySQL?**
PostgreSQL punya exclusion constraint; MySQL tidak. Kami tegakkan di hook saving model
dalam transaksi: tolak bila ada item grade sama dengan rentang tanggal beririsan (DEV-001).

**6. Apa itu PriceResolver dan kenapa harga tidak boleh diketik manual?**
Service yang mengambil harga dari daftar bertanggal yang sudah disetujui Pemilik dan
berlaku pada tanggal timbang (BR-01). Tidak ada → exception → konfirmasi diblokir (TC-05).
Ini menutup risiko petugas menaikkan harga ilegal (H.2).

**7. Kenapa rate limiter 5 percobaan / 15 menit?**
Mitigasi brute force (H.2, OWASP ASVS L1). Kunci per IP, decay 15 menit, pesan sesuai
SCR-01. Diselaraskan ke 15 menit (DEV-005).

**8. Apa itu Gate/Policy di Laravel?**
Gate = definisi kemampuan terpusat (mis. approve-price hanya PEMILIK); Policy = otorisasi
per model. Filament resource memakai canAccess() sehingga 403 terjadi di server, bukan UI.

**9. Kenapa seeder data sintetis, bukan data asli?**
Brief mewajibkan data uji sintetis (BT-06). Baseline DS-LOK/USR/TANI/HARGA/MITRA dari
Bagian I dibuat persis agar angka test case cocok dengan tampilan.

**10. Apa itu modal CRUD (simple resource) di Filament?**
Master data dikelola lewat tabel + formulir modal tanpa halaman terpisah — lebih cepat
dibangun dan cukup untuk data induk; transaksi kompleks (penerimaan) memakai wizard Livewire.

## Fase 2 — Hulu, Stok, dan Pembayaran

**1. Apa itu ledger append-only dan kenapa saldo tidak disimpan begitu saja?**
Ledger = buku mutasi yang hanya bisa ditambah. Saldo (StockBalance) adalah cache turunan;
kalau ragu, ledger yang menang dan saldo bisa dihitung ulang. Trigger MySQL menolak UPDATE/DELETE.

**2. Apa itu pessimistic row locking dan kenapa dipakai di sini?**
SELECT ... FOR UPDATE mengunci baris saldo sampai transaksi selesai. Dua kasir yang menjual
lot terakhir bersamaan akan antre, bukan sama-sama membaca saldo lama. Ini standar perbankan (F.7).

**3. Kenapa semua aksi dibungkus DB::transaction?**
IR-09: posting stok, lot, dan pembayaran harus atomik. Kalau satu langkah gagal, semuanya batal —
tidak ada lot tanpa ledger atau pembayaran tanpa alokasi.

**4. Apa itu idempotency key dan bagaimana TC-06 lulus?**
Kunci unik per permintaan. Kunci sama → pembayaran yang sama dikembalikan, tidak dibuat baru.
Kunci berbeda untuk penerimaan yang sama → ditolak karena status sudah PAID dan UNIQUE receipt_id.

**5. Bagaimana grade ditentukan otomatis?**
DetermineGradeAction memilih grade dengan ambang kadar_air_max terkecil yang masih memenuhi
(A ≤14, B ≤16, C ≤18). Di atas 18 → tidak ada grade → wajib Tolak/Karantina dengan alasan.

**6. Kenapa foto tidak disimpan di database?**
Foto dikompres ke WebP <300KB lalu diunggah ke object storage; database hanya menyimpan path.
Akses lewat presigned URL 15 menit agar tidak bisa di-scrape publik (F.6.2).

**7. Apa bedanya global scope dan policy?**
Global scope menyaring baris yang boleh dilihat (daftar/pencarian). Policy memutuskan boleh/tidak
sebuah aksi pada baris tertentu. Detail sengaja dibuka tanpa scope lalu di-policy agar penolakan
berupa 403 tercatat di audit, bukan 404 senyap.

**8. Kenapa dokumen final tidak bisa diedit?**
BR-13: koreksi lewat dokumen pembalik, bukan menimpa bukti. Trigger receipts_lock_final menolak
perubahan kolom isi saat status CONFIRMED/PAID/REJECTED.

## Fase 3 — FEFO, Transfer, dan Penyesuaian

**1. Apa itu FEFO dan bedanya dengan FIFO?**
FIFO = First In First Out (masuk paling awal keluar dulu). FEFO = First Expired First Out
(batas simpan paling awal keluar dulu). Untuk pangan, FEFO lebih tepat karena dua lot bisa
punya umur simpan berbeda. Kami urutkan batas_simpan lalu tgl_terima (BR-04).

**2. Apa itu stok in-transit dan kenapa transfer dua tahap?**
Saat SHIPPED, stok keluar dari gudang asal tetapi belum masuk tujuan; jumlahnya dibaca dari
TransferLine berstatus SHIPPED. Ini mencegah barang "hilang" di jalan: kalau tujuan menerima
lebih sedikit, selisihnya tercatat sebagai DISCREPANCY, bukan menguap (BR-15, TC-09).

**3. Apa bedanya on-hand, reserved, dan tersedia?**
On-hand = fisik di lokasi. Reserved = sudah dipesan tapi belum keluar. Tersedia = on-hand − reserved.
Reservasi TIDAK menulis ledger karena belum mengubah fisik; ia hanya menaikkan reserved_kg
dengan kunci baris, sehingga dua pesanan tidak bisa memakai kilogram yang sama (IR-13).

**4. Kenapa lot kedaluwarsa tidak otomatis dihapus?**
Barangnya masih ada secara fisik dan harus tetap bisa diaudit. Statusnya jadi KEDALUWARSA,
diblokir dari alokasi normal, dan hanya bisa dipakai dengan persetujuan Kepala Gudang yang
tercatat di audit (BR-04, TC-27).

**5. Kenapa koreksi stok lewat penyesuaian, bukan edit saldo?**
Ledger append-only (BR-03). Mengedit saldo akan memutus jejak. Penyesuaian adalah dokumen
beralasan + bertanda tangan yang menulis mutasi baru, sehingga saldo lama tetap bisa direkonstruksi.

**6. Apa itu saldo awal cutover dan kenapa lewat dokumen resmi?**
Saat go-live, stok fisik hasil opname dimasukkan lewat STOCK_ADJUSTMENT_INITIAL yang disetujui
Pemilik, bukan UPDATE database. Dengan begitu kilogram pertama pun punya jejak audit (J.3.2, RK-06).

**7. Bagaimana sistem mencegah transfer melebihi stok?**
ShipTransferAction memanggil StockLedgerWriter di dalam transaksi; saldo dikunci
SELECT ... FOR UPDATE, hasil pengurangan dihitung, dan bila negatif seluruh transaksi dibatalkan
sehingga tidak ada mutasi yang tersimpan (TC-28).

## Fase 4 — Pemenuhan, Retur, POS, dan Webhook

**1. Kapan stok keluar untuk pesanan?**
Saat pengiriman DIKIRIM (pesanan SHIPPED), sesuai Q-D1/BR-15. Reservasi dilepas dan on-hand
dikurangi dalam satu transaksi terkunci; saat DELIVERED barang tidak keluar lagi.

**2. Apa itu bukti terima (POD) dan kenapa penting?**
Proof of Delivery = bukti penerima menghitung barang (foto/tanda tangan/kode) beserta jumlah
diterima. Selisih dengan jumlah dikirim dicatat sebagai kekurangan_kg dan membuka alur retur/kredit.

**3. Kenapa retur wajib merujuk dokumen asal?**
BR-10/IR-12: tanpa referensi, retur bisa fiktif. Sistem menolak retur tanpa ref, dan membatasi
jumlah retur agar total tidak melebihi kuantitas terkirim dikurangi retur sebelumnya (TC-12, 23, 24).

**4. Bagaimana sistem mencegah kasir menjual melebihi stok?**
Baris saldo dikunci dengan SELECT ... FOR UPDATE, lalu CHECK on_hand >= 0 di database. Jika jumlah
melebihi tersedia, transaksi dibatalkan utuh (BR-09, TC-07). Dua kasir menjual lot terakhir bersamaan:
yang mengunci lebih dulu berhasil, yang kedua menunggu lalu melihat stok sudah 0 → ditolak (TC-20).

**5. Apa itu HET dan bagaimana ditegakkan?**
Harga Eceran Tertinggi = batas harga jual beras ke konsumen (regulasi). Sistem membandingkan harga
kasir dengan HET aktif per komoditas; mode BLOKIR menolak, mode PERINGATAN meloloskan dengan tanda
(BR-11, TC-16, RK-07).

**6. Bagaimana webhook pembayaran dibuat aman dan anti-duplikat?**
Server menghitung ulang HMAC-SHA512 dari raw body memakai secret; signature tidak cocok → 401.
Event diproses idempoten: event_id jadi idempotency_key unik, webhook kedua diabaikan (TC-13).

## Fase 5 — Invoice, Piutang, Dashboard, Audit

**1. Kapan invoice terbit?**
Setelah pesanan DELIVERED (Q-D4). Sistem menarik baris pesanan yang sudah terpenuhi
dan menghitung total dari HET aktif per komoditas (atau harga placeholder bila HET belum
diatur). Invoice tidak bisa terbit dua kali untuk pesanan yang sama (TC-15).

**2. Bagaimana alokasi pembayaran ke beberapa invoice?**
BR-08: pembayaran dialokasikan ke invoice tertua lebih dulu (FIFO berdasarkan tgl_invoice).
Keuangan boleh memilih urutan manual; bila tidak, sistem memakai urutan naik.
TC-22 membuktikan: bayar Rp6 juta untuk dua invoice (Rp5jt & Rp8jt) melunasi INV-001 dan
memotong Rp1 juta dari INV-002.

**3. Bagaimana batas kredit ditegakkan?**
Saat terbit invoice, sistem menjumlahkan saldo piutang mitra + total invoice baru; bila
melebihi batas_kredit, permintaan ditolak kecuali Keuangan memilih "paksa" (TC-15). Setiap
permintaan paksa tercatat di audit.

**4. Kenapa pembayaran > saldo ditolak?**
MVP bukan sistem akuntansi penuh (A-17/TC-21). Mencatat kelebihan bayar sebagai deposit
akan memerlukan modul kredit/refund tersendiri; ditunda ke rilis berikutnya.

**5. Bagaimana status invoice diturunkan?**
Status = fungsi(total, dibayar, jatuh_tempo): ISSUED bila belum dibayar dan belum jatuh
tempo; PARTIAL bila sebagian; PAID bila lunas; OVERDUE bila sudah lewat jatuh tempo.
Tidak disimpan di kolom, jadi tidak bisa dimanipulasi (IR-11).

**6. Apa isi dashboard per peran?**
Kartu metrik yang relevan: stok on-hand, lot mendekati kedaluwarsa (80%), penerimaan
hari ini, pesanan aktif, piutang berjalan, persetujuan menunggu. Setiap kartu adalah
tautan ke layar detailnya. Petugas gudang melihat kartu operasional, Keuangan melihat
piutang & aging, Pemilik melihat semuanya.

**7. Bagaimana audit log ditampilkan?**
SCR-13: tabel append-only dengan pencarian (aksi/entitas/nama pelaku) dan filter aksi.
Hanya Pemilik/Admin yang boleh membuka; peran lain menerima 403. Semua kejadian penting
(PENERIMAAN_KONFIRMASI, PEMBAYARAN_PETANI, PEMBAYARAN_INVOICE, AKSES_DITOLAK, dsb.)
sudah tercatat dari Fase 1-4.

# DEVIATION LOG — SiLeuit.id

Semua penyimpangan implementasi dari dokumen A–L, disetujui pemilik proyek
(Muhammad Huwaiza Rafi) via "ACC" pada 24 Nov 2026. Setiap entri memuat
kalimat patch siap tempel untuk dokumen terkait agar klarifikasi ke dosen minimal.

| ID | Tanggal | Penyimpangan | Alasan | Dampak dokumen | Status |
|---|---|---|---|---|---|
| DEV-001 | 24 Nov 2026 | Fitur PostgreSQL di E (IR-06 exclusion, IR-14 num_nonnulls, IR-15 partial unique index, jsonb/timestamptz/inet) diterjemahkan ke padanan MySQL 8: CHECK + generated column + unique nullable + trigger | Stack final MySQL 8 (R-03/Q-10); desain & IR tidak berubah, hanya dialek DB | E.5 (kolom "Ditegakkan oleh") | ACC |
| DEV-002 | 24 Nov 2026 | Enum Receipt: tambah DIAJUKAN & MENUNGGU_PERSETUJUAN; hapus PARTIAL_ACCEPTED | Alur offline (G/F.8) & BR-05 butuh status tsb; PARTIAL_ACCEPTED tidak dipakai alur mana pun | B.4, E.4.1 | ACC |
| DEV-003 | 24 Nov 2026 | Jenis mutasi baru TERIMA_CABANG: pesanan ke cabang sendiri masuk ledger lokasi tujuan saat DELIVERED | Q-D1 baru memutuskan sisi keluar (SHIPPED); sisi masuk cabang belum dimodelkan | E.4.1, L Q-D1 | ACC |
| DEV-004 | 24 Nov 2026 | Intake MVP = gabah kering (GKG) + beras; kadar air >18% tolak/karantina; grade MVP ditentukan kadar air (A-20), parameter lain dicatat | Ambang A-20 adalah standar gabah kering; GKP basah (20–25%) akan selalu tertolak; pengeringan di luar MVP (D-02) | A-03, Q-03 | ACC |
| DEV-005 | 24 Nov 2026 | Lockout login 15 menit (bukan 30) | G SCR-01 menyebut 15 menit; H.2 menyebut 30 — disamakan ke teks UI | H.2 | ACC |
| DEV-006 | 24 Nov 2026 | Petani: HP+PIN saja; reset PIN via Admin/Pengadaan; OTP SMS/WA & 2FA Keuangan/Pemilik → Rilis 1.1 | OTP butuh provider berbayar; di H statusnya opsional | H.3.1, G SCR-10 | ACC |
| DEV-007 | 24 Nov 2026 | HET default BLOKIR (bisa diubah ke mode peringatan per komoditas); stok minimum configurable, default 0 (nonaktif) | RK-07 menyebut blokir; FR-07 belum punya angka | BR-11, FR-07 | ACC |
| DEV-008 | 24 Nov 2026 | FR-18 (offline PWA) dibangun di Fase 6; FR-12 cukup webhook HMAC + simulator, tanpa akun gateway asli | FR-18 inti nilai jual (TC-14); Q-06/K.1: katalog B2C = Rilis 1.1 | K.1 | ACC |
| DEV-009 | 24 Nov 2026 | UI Stitch tim: adopsi palet/tipografi/layout; TOLAK landing publik, katalog, lacak-lot publik, self-register, link OTP, sensor silo | Katalog = Rilis 1.1 (Q-06); akun dibuat Admin (FR-01); IoT & giling di luar MVP | — (keputusan desain, selaras dokumen) | ACC |
| DEV-010 | 24 Nov 2026 | Pin versi: Laravel 13.x + Filament ^4.9 + Livewire 3 + PHP 8.6 lokal (min 8.3), CI PHP 8.5 | Filament v4 mendukung Laravel 13 sejak 4.9.0; TIDAK ada deviasi dari R-03 | — (konfirmasi) | ACC |
| DEV-011 | 25 Nov 2026 | Pest 5 + PHPUnit 13 (bukan v4) | Kompatibilitas PHP 8.6; dokumen hanya mewajibkan "PHPUnit/Pest" | — (catatan versi) | ACC |
| DEV-012 | 25 Nov 2026 | MinIO dev diganti SeaweedFS (S3-compatible), endpoint/bucket tetap | Image MinIO tidak lagi tersedia gratis; hanya dev lokal, produksi tetap S3/B2 | F.5 dev | ACC |
| DEV-013 | 25 Nov 2026 | Kolom hp/no_rekening terenkripsi + kolom hp_hash SHA-256 untuk uniqueness & lookup | Enkripsi AES (H.4) membuat UNIQUE pada ciphertext tidak mungkin; hash deterministik menjaga IR-02 | E.4.2 (mekanisme) | ACC |

## Patch kalimat untuk dokumen (tempel sebelum submit)

- **H.2** (Huwaiza): ganti "Account lockout sementara selama 30 menit" →
  "Account lockout sementara selama 15 menit (selaras dengan pesan UI SCR-01)."
- **K.7 butir 2** (Huwaiza): ganti "(TC-01 s.d. TC-12)" →
  "(19 test case P1; daftar lengkap di Bagian I)."
- **K.2 minggu 8** (Huwaiza): ganti "Eksekusi 18 skenario uji" →
  "Eksekusi 28 skenario uji (19 P1, 9 P2)."
- **B.4 / E.4.1** (minta Fatih/Raflisya'id): enum Receipt menjadi
  "DRAFT, DIAJUKAN, INSPECTED, MENUNGGU_PERSETUJUAN, CONFIRMED, PAID, REJECTED".
- **E.4.1** (minta Raflisya'id): tambah TERIMA_CABANG pada jenis_mutasi;
  catatan Q-D1: "keluar saat SHIPPED; untuk tujuan cabang sendiri, masuk saat DELIVERED".
- **Bagian I** (minta Viseila): label lokal A-16/A-17/A-18 → A-20/A-21/A-22 sesuai L final.

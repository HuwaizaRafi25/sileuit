# SiLeuit.id

Platform web rantai pasok gabah–beras Jawa Barat: petani mitra → gudang → cabang →
retail B2B & konsumen. Ketertelusuran per lot, stok anti-negatif, FEFO, piutang tertib.
Mata kuliah Rekayasa Perangkat Lunak — POLMAN Bandung. Spesifikasi: dokumen A–L.

## Stack
Laravel 13 (modular monolith) · Filament v4 (backoffice) · Blade + Livewire 3 + PWA (lapangan)
MySQL 8 (InnoDB) · Redis 7 (cache/queue/sesi) · MinIO (S3 dev) · Mailpit (mail dev)
Pest + Pint · GitHub Actions · Docker (layanan dev saja)

## Menjalankan (dev, Windows + Laragon)
1. Laragon: Start All (MySQL 8.0.30 native).
2. `docker compose up -d` (Redis :6379, MinIO :9000/:9001, Mailpit :1025/:8025).
3. `php artisan serve` → http://localhost:8000
4. `npm run dev` (opsional, hot reload aset).

## Akun & layanan dev (JANGAN dipakai produksi)
- Panel admin: http://localhost:8000/admin — admin@sileuit.id / Sileuit#2026
- MinIO console: http://localhost:9001 — minioadmin / minioadmin (bucket: sileuit-dev)
- Mailpit: http://localhost:8025
- Health: http://localhost:8000/healthz

## Akun dev (seed Fase 1)
- Staf: {kode}@sileuit.id / Sileuit#2026 — kode: pemilik-1, adm-1, pengadaan-1, keu-1, pg-g1, pg-g2, kg-g1, kg-g2, ksr-c1, ksr-c2, kur-1
- Petani: +628111111001 (P-001) / +628111111002 (P-002), PIN 123456 → /login/petani
- Mitra B2B: retail-contoh-satu@mitra.sileuit.id / Sileuit#2026

## Struktur
`app/Modules/*` = domain (F.3) · `app/Infrastructure/*` = lintas domain
`docs/DEVIATION-LOG.md` = penyimpangan dari dokumen + patch kalimat
`docs/QA-DOSEN.md` = definisi & alasan teknologi untuk presentasi

## Tes
`php artisan test` (DB: sileuit_test) · `vendor/bin/pint --test`

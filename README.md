# SKUAD Learning Hub

SKUAD Learning Hub adalah platform pembelajaran, administrasi, penilaian, dan portofolio digital untuk program Siswa Kreatif Update Digital di SMP IT Mentari Ilmu Jatisari.

## Stack

- Laravel 12 dan PHP 8.2+
- MySQL/MariaDB
- Blade
- Bootstrap 5 dan Bootstrap Icons
- JavaScript murni
- Vite

## Dokumentasi

- `AGENTS.md` berisi aturan permanen pengembangan.
- `docs/BLUEPRINT_SKUAD_LEARNING_HUB_PREMIUM_V2.md` berisi blueprint produk.
- `docs/PROMPT_CODEX_SKUAD_LEARNING_HUB_PREMIUM_V2.md` berisi fase implementasi.
- `docs/DEVELOPMENT.md` berisi panduan development lokal.

## Mulai Cepat

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd install
php artisan migrate
npm.cmd run build
php artisan test
```

Lihat `docs/DEVELOPMENT.md` untuk konfigurasi database dan alur kerja lengkap.

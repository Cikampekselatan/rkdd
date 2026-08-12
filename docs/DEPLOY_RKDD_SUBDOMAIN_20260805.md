# Deploy RKDD Subdomain - 2026-08-05

Target asumsi:

```env
APP_URL=https://rkdd.digicomciksel.com
GOOGLE_REDIRECT_URI=https://rkdd.digicomciksel.com/auth/google/callback
```

Jangan commit atau unggah `.env` dari lokal jika berisi credential development. Perbaiki `.env` langsung di hosting/cPanel agar nilai database produksi tetap aman.

## `.env` Hosting

Pastikan baris ini ada dan tidak kosong:

```env
APP_NAME="SKUAD Learning Hub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rkdd.digicomciksel.com

GOOGLE_CLIENT_ID=isi-dengan-client-id-google
GOOGLE_CLIENT_SECRET=isi-dengan-client-secret-google
GOOGLE_REDIRECT_URI=https://rkdd.digicomciksel.com/auth/google/callback

STUDENT_REGISTRATION_GOOGLE_ONLY=true
STUDENT_ALLOWED_EMAIL_DOMAINS=gmail.com
STUDENT_REGISTRATION_REQUIRE_JOIN_CODE=true
STUDENT_AUTO_ACTIVATE_AFTER_ONBOARDING=true
```

## Google Cloud Console

Tambahkan Authorized redirect URI berikut pada OAuth Client yang sama:

```text
https://rkdd.digicomciksel.com/auth/google/callback
```

Nilainya harus sama persis dengan `GOOGLE_REDIRECT_URI`, termasuk `https`, subdomain, dan path.

## Setelah Upload

Jalankan dari folder aplikasi Laravel di hosting:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika ada migration baru dari paket deploy:

```bash
php artisan migrate --force
```

## Smoke Test

1. Buka `https://rkdd.digicomciksel.com/login`.
2. Klik tombol Google siswa.
3. Pastikan URL Google tidak lagi menampilkan `Missing required parameter: client_id`.
4. Login dengan Gmail siswa.
5. Pastikan siswa baru masuk status onboarding/kode pendaftaran.

# Development SKUAD Learning Hub

## Prasyarat

- PHP 8.2 atau lebih baru.
- Composer 2.
- Node.js dan npm.
- MySQL 8 atau MariaDB yang kompatibel.

## Instalasi Lokal

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd install
```

Buat database `skuad_learning_hub` melalui MySQL, lalu sesuaikan kredensial `DB_*` di `.env`. Seluruh tabel aplikasi harus dibuat melalui migration.

```powershell
php artisan migrate
npm.cmd run build
php artisan test
```

## Menjalankan Development Server

Gunakan dua terminal:

```powershell
php artisan serve
```

```powershell
npm.cmd run dev
```

Aplikasi tersedia di `http://127.0.0.1:8000`.

Halaman katalog komponen tersedia pada `http://127.0.0.1:8000/design-system` di environment `local`. Halaman tersebut sengaja mengembalikan 404 di luar environment development/testing.

## Struktur Route

Route publik berada di `routes/web.php` dan autentikasi di `routes/auth.php`. Area role dipisahkan ke file `super-admin.php`, `admin.php`, `teacher.php`, `coach.php`, `student.php`, dan `principal.php`.

Route role pada Fase 1 hanya placeholder. Middleware autentikasi, role, dan policy ditambahkan pada fase yang telah ditentukan di prompt pengembangan.

## Login Staff Development

Login lokal hanya tersedia untuk role staff: `super-admin`, `admin`, `teacher`, `coach`, dan `principal`. Role `student` tidak dapat memakai login lokal.

Isi kredensial development pada `.env`:

```env
DEV_SUPER_ADMIN_NAME="SKUAD Super Admin"
DEV_SUPER_ADMIN_EMAIL=admin@skuad.local
DEV_SUPER_ADMIN_PASSWORD="ganti-dengan-password-kuat"
```

Aktifkan MySQL/MariaDB, buat database yang tercantum pada `DB_DATABASE`, lalu jalankan:

```powershell
php artisan migrate
php artisan db:seed
```

Seeder super-admin hanya berjalan pada environment `local` dan `testing`. Registrasi lokal publik sengaja tidak disediakan.

## Google OAuth Siswa

Buat OAuth 2.0 Client pada Google Cloud Console dan tambahkan redirect URI berikut:

```text
http://127.0.0.1:8000/auth/google/callback
```

Isi konfigurasi lokal:

```env
STUDENT_REGISTRATION_GOOGLE_ONLY=true
STUDENT_ALLOWED_EMAIL_DOMAINS=gmail.com
STUDENT_REGISTRATION_REQUIRE_JOIN_CODE=true
STUDENT_AUTO_ACTIVATE_AFTER_ONBOARDING=true

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Beberapa domain dapat dipisahkan dengan koma, misalnya `gmail.com,siswa.sekolah.id`. Domain dibandingkan secara tepat dan tidak menerima subdomain secara otomatis.

Akun Google baru dibuat dengan status `onboarding`, password `null`, dan belum memiliki role `student`. Access token maupun refresh token Google tidak disimpan oleh aplikasi.

## Kode Pendaftaran

Admin dan super-admin mengelola kode melalui `/admin/registration-codes`. Kode dibuat otomatis dengan entropy tinggi, ditampilkan satu kali, lalu hanya hash HMAC yang disimpan.

```env
REGISTRATION_CODE_PREFIX=SKUAD
REGISTRATION_CODE_LENGTH=20
REGISTRATION_CODE_HASH_KEY="${APP_KEY}"
```

Akun Google berstatus onboarding memvalidasi kode melalui `/onboarding/registration-code`. Validasi dibatasi lima percobaan per menit dan belum menambah `used_count`; penggunaan baru dihitung saat finalisasi onboarding dalam transaction pada Fase 6.

Kolom `academic_year_id` dan `class_id` kini terhubung ke master tahun ajaran dan kelas melalui foreign key. Form kode pendaftaran hanya menerima kombinasi tahun ajaran dan kelas yang tersedia.

## Wizard Onboarding Siswa

Setelah kode tervalidasi, siswa membuka `/onboarding/wizard/identity` dan menyelesaikan lima langkah:

1. Identitas.
2. Orang tua/wali.
3. Perangkat dan internet.
4. Minat dan kemampuan awal.
5. Persetujuan.

Setiap langkah disimpan sebagai draft. Finalisasi mengunci user dan kode pendaftaran dalam database transaction, memeriksa ulang masa aktif serta batas kode, membuat class membership, memberikan role `student`, mengaktifkan akun, dan menambah `used_count` tepat satu kali.

Action finalisasi bersifat idempotent. Request ulang tidak menambah penggunaan kode atau membuat membership dan role ganda.

## Master Data

Admin dan super-admin mengelola master data melalui route berikut:

- `/admin/academic-years` untuk tahun ajaran dengan tepat satu record aktif.
- `/admin/classes` untuk kelas, tingkat, kapasitas, dan wali kelas.
- `/admin/staff` untuk akun lokal admin, guru, coach, dan kepala sekolah.
- `/admin/students` untuk pencarian, filter, detail siswa 360 derajat, suspend, activate, dan arsip.

Guru dan coach dapat melihat daftar serta detail siswa, tetapi perubahan status dan pengarsipan hanya tersedia untuk admin dan super-admin. Penghapusan user, profil siswa, profil pengajar, tahun ajaran, dan kelas menggunakan soft delete bila record perlu dipertahankan untuk histori.

Seeder master data membuat tahun ajaran `2026/2027` beserta kelas `7A`, `8A`, dan `9A` secara idempotent pada environment lokal/testing:

```powershell
php artisan db:seed --class=MasterDataSeeder
```

## Dashboard Siswa

Siswa aktif membuka dashboard pribadi melalui `/student`. Halaman hanya mengambil profil, kelas, tahun ajaran, dan minat milik user yang sedang terautentikasi.

Ringkasan pertemuan, tugas, revisi, nilai, kehadiran, portofolio, dan pengumuman menggunakan nilai nyata. Sebelum tabel modul terkait tersedia pada fase berikutnya, dashboard menampilkan nilai `0` dan empty state, bukan data demo.

Bottom navigation mobile menyediakan akses cepat ke beranda, lanjut belajar, tugas, portofolio, dan profil. Guest, akun non-siswa, serta siswa berstatus suspended tidak dapat mengakses dashboard.

## Pembelajaran 30 Pertemuan

Pembina/guru mengelola kurikulum melalui `/teacher/learning`. Fitur yang tersedia:

- CRUD 15 modul dan 30 pertemuan.
- Judul hasil seeder dapat diedit, dihapus, atau diganti dengan judul manual oleh guru.
- Nomor pertemuan yang dihapus dapat digunakan kembali tanpa membawa materi lama.
- Seeder menghormati pertemuan yang sengaja dihapus dan tidak membuatnya kembali.
- Durasi default 90 menit dan pembagian otomatis 15 pertemuan per semester.
- Tujuan pembelajaran berbentuk JSON dari input satu tujuan per baris.
- Materi bertipe teks, gambar, video, dokumen, tautan, audio, atau presentasi.
- Preview draf dan publikasi manual setelah minimal satu materi tersedia.
- Publikasi otomatis untuk status `scheduled` ketika waktu sudah tiba dan materi tersedia.
- Pencatatan `published_at` dan `published_by` pada setiap publikasi.

Siswa membuka materi melalui `/student/learning`. Hanya pertemuan berstatus `published`, `ongoing`, atau `completed` dari tahun ajaran kelas siswa yang dapat diakses. Membuka pertemuan serta menyelesaikan materi, latihan, dan refleksi menyimpan progress secara terpisah untuk setiap siswa.

Seeder kurikulum membuat 15 modul dan 30 pertemuan draf secara idempotent:

```powershell
php artisan db:seed --class=LearningCurriculumSeeder
```

Blueprint tidak menyediakan daftar judul pertemuan secara eksplisit. Judul seeder diturunkan dari tema yang disebutkan blueprint: etika digital, desain, fotografi, videografi, presentasi, AI, produktivitas, data, coding, web, kolaborasi, kewirausahaan, produksi konten, dan proyek akhir.

Jalankan scheduler Laravel setiap menit agar publikasi terjadwal diproses:

```powershell
php artisan schedule:run
```

Untuk development, command dapat diperiksa langsung:

```powershell
php artisan learning:publish-scheduled
```

## Document Center Google Drive

Document Center tersedia di `/documents` untuk staff dan `/student/documents` untuk siswa. Aplikasi hanya menyimpan URL dan metadata; tidak menggunakan Google Drive API serta tidak mengubah izin sharing file.

Admin dan guru dapat secara manual:

- Menambah, mengubah, dan menghapus panduan atau dokumen sesuai kebutuhan.
- Memilih kategori, audience, tahun ajaran, semester, urutan, dan status pin.
- Menyimpan draf, publish, archive, preview, membuka tab Drive, dan menyalin tautan.
- Menambahkan kembali dokumen dengan judul yang sama setelah dokumen lama dihapus.

URL Google Drive/Docs divalidasi dan file ID diparsing untuk membentuk preview URL. Format file, dokumen, spreadsheet, presentasi, folder, serta URL `open?id=` didukung.

Audience diterapkan sebagai berikut:

- `staff_only`: admin, guru, dan super-admin pengelola.
- `teacher_coach`: guru dan coach.
- `all_staff`: seluruh staff.
- `students`: siswa serta staff pendamping.
- `internal_public`: seluruh pengguna yang sudah login.

Siswa hanya melihat dokumen published untuk audience siswa/internal dan tahun ajarannya. Setiap create, update, publish, archive, pin, dan delete dicatat pada histori perubahan.

Google Drive harus dibagikan sebagai Viewer kepada audience yang sesuai. Jika sharing Drive menolak akses, aplikasi menampilkan pesan agar pengelola memeriksa izin file.

## Quality Check

Jalankan sebelum commit:

```powershell
vendor\bin\pint
npm.cmd run build
php artisan test
```

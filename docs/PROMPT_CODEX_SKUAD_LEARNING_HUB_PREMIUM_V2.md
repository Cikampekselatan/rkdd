# PROMPT CODEX TERSTRUKTUR — SKUAD LEARNING HUB PREMIUM V2

> Jalankan satu fase, periksa, test, commit, lalu lanjut. Jangan menjalankan beberapa fase sekaligus.

# PROMPT INDUK — Tempel di Awal Setiap Fase

```text
Baca AGENTS.md dan docs/BLUEPRINT_SKUAD_LEARNING_HUB_PREMIUM_V2.md.

Stack yang dikunci:
- Laravel 12
- PHP
- MySQL
- Blade
- HTML5
- CSS3
- JavaScript murni
- Bootstrap 5
- Bootstrap Icons
- Vite

Dilarang menggunakan React, Vue, Inertia, Livewire, Tailwind, atau SPA terpisah.
Dilarang menaikkan Laravel.
Dilarang membuat fitur di luar fase.
Dilarang mengubah file tidak terkait.
Dilarang memasang package tanpa alasan dan persetujuan.
Dilarang membuat tabel melalui phpMyAdmin.

Sebelum coding:
1. Audit repository.
2. Nyatakan scope.
3. Buat rencana singkat.
4. Daftar file yang akan dibuat/diubah.
5. Daftar migration dan test.

Setelah coding:
1. Jalankan formatter.
2. Jalankan npm build.
3. Jalankan test fase dan test relevan.
4. Laporkan file, migration, command, hasil, dan keterbatasan.
5. Berhenti dan menunggu instruksi.
```

---

# Fase 0 — Audit Lingkungan

```text
Periksa php -v, composer --version, node -v, npm -v, mysql --version, extension PHP, isi repository, dan status Git.

Laravel target wajib 12.x.
Jangan membuat aplikasi atau mengubah database.

Laporkan versi, extension kurang, kesiapan Composer/MySQL, command instalasi, risiko, dan keputusan go/no-go.
Berhenti.
```

# Fase 1 — Inisialisasi Laravel 12

```text
Buat SKUAD Learning Hub menggunakan Laravel 12, Blade, MySQL, Bootstrap 5, JavaScript murni, dan Vite.

Konfigurasi:
APP_NAME="SKUAD Learning Hub"
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

Buat layout dasar, landing page, login placeholder, route file terpisah per role, dan dokumentasi development.
Verifikasi php artisan --version = 12.x.
Jalankan npm build dan test bawaan.
Jangan membuat fitur bisnis.
```

# Fase 2 — Design System Premium

```text
Bangun design tokens, typography, button, form, badge, card, stat card, premium table, empty state, skeleton, toast, modal konfirmasi, offcanvas filter, floating/collapsible sidebar desktop, offcanvas sidebar tablet, bottom navigation mobile, sticky topbar, breadcrumb, page header, avatar, dan notification dropdown.

Buat halaman /design-system untuk super-admin development.
Uji 375, 430, 768, 1024, 1366, 1440, dan 1920 px.
Jangan membuat database bisnis.
```

# Fase 3 — Role dan Staff Authentication

```text
Buat role super-admin, admin, teacher, coach, student, principal menggunakan tabel roles dan role_user tanpa package role eksternal.

Buat local login staff, nonaktifkan local public registration, seeder super-admin development, middleware role, policies dasar, dan tests.
Student Google login belum dibuat.
```

# Fase 4 — Google OAuth Siswa

```text
Pasang Laravel Socialite resmi dan implementasikan redirect/callback Google.

Buat config env Google, field google_id, validasi domain gmail.com dari config, halaman error domain, dan audit login.

User Google baru berstatus onboarding, belum mendapat role student.
Jangan simpan access token jika tidak diperlukan.
Test callback dengan mock/fake.
```

# Fase 5 — Kode Pendaftaran

```text
Buat registration_codes dengan tahun ajaran, kelas opsional, kode entropy tinggi/hash, max_uses, used_count, starts_at, expires_at, is_active, created_by.

Buat CRUD admin, Form Request, Policy, rate limit, halaman validasi kode, dan tests: valid, salah, expired, nonaktif, limit tercapai.

Setelah valid, simpan onboarding state dan arahkan ke wizard. Role student belum diberikan.
```

# Fase 6 — Wizard Onboarding

```text
Buat wizard 5 langkah: identitas, wali, perangkat/internet, minat/kemampuan, persetujuan.

Gunakan Blade, Bootstrap, JavaScript murni, server validation, progress stepper, draft per langkah, back/next.

Buat student_profiles, student_onboarding_responses, class_students, Form Requests, dan Action finalisasi.

Finalisasi dalam DB transaction: validasi kode, profil, class membership, role student, status active, used_count, completed_at, redirect dashboard.
Harus idempotent dan tidak membuat duplikasi.
Test semua langkah dan akses lintas user.
```

# Fase 7 — Master Data

```text
Buat academic_years, classes, teacher_profiles, student_profiles, class_students.

Fitur: satu tahun aktif, CRUD kelas/staff, daftar siswa, filter status/minat/onboarding, search, pagination, suspend/activate, detail siswa 360° skeleton, soft delete, factories, seeders, policies, tests.

Desktop data table; mobile card list; filter offcanvas.
```

# Fase 8 — Dashboard Siswa

```text
Buat dashboard pribadi siswa dengan hero, progress 30 pertemuan, tugas aktif, revisi, nilai, kehadiran, portofolio, lanjut belajar, nilai terbaru, pengumuman, dan empty states.

Tidak boleh menampilkan data siswa lain. Gunakan data nyata dan nilai 0 bila modul belum ada.
Aktifkan bottom navigation mobile.
Test otorisasi.
```

# Fase 9 — Pembelajaran 30 Pertemuan

```text
Buat learning_modules, learning_sessions, learning_materials, student_learning_progress.

Ketentuan: 15 modul, 30 pertemuan, 90 menit, 15 per semester, session_number unik, objectives JSON, tipe materi text/image/video/document/link/audio/presentation, urutan, status draft/scheduled/published/ongoing/completed/archived.

Seeder judul 30 pertemuan dari blueprint.
Guru CRUD/publish/preview; siswa hanya published; progress tersimpan.
Test policy dan progress.
```

# Fase 10 — Document Center Google Drive

```text
Buat document_resources dengan kategori silabus, modul, asesmen, rpp, kurikulum, alat_dan_bahan, buku_teori, form_administrasi, panduan, lainnya.

Buat audience staff_only, teacher_coach, all_staff, students, internal_public.

Fitur: CRUD, validasi URL, parser Drive ID, preview URL, pin, order, publish, archive, search/filter, card/table premium, preview modal, open tab, state access denied.

Aplikasi hanya menyimpan URL, tidak menggunakan Drive API.
Siswa hanya melihat audience yang diizinkan.
Test policies/audience.
```

# Fase 11 — Presensi Siswa

```text
Buat attendance_sessions dan attendance_records dengan status present, late, sick, permitted, absent.

Guru membuka sesi, input massal, tombol besar mobile, catatan, close session, edit ber-audit, rekap per pertemuan/siswa, persentase.
Siswa melihat histori pribadi.
Buat unique constraint dan tests.
```

# Fase 12 — Absen Pengajar dan Catatan Penting

```text
Buat teacher_activity_logs: tanggal, materi, kegiatan, penugasan, tanda tangan, draft/submitted/verified/rejected, verifikasi, penolakan, A4, bulanan, audit.

Buat important_notes: tanggal, catatan, penyelesaian, prioritas, status, paraf coach/pembina, timeline mobile, filter, A4, audit.

Tanda tangan private dan diakses melalui Policy.
Test workflow.
```

# Fase 13 — Tugas dan Submission

```text
Buat assignments, submissions, submission_versions, submission_files.

Jenis text/document/image/video_link/external_link/mixed/reflection.
Status draft/submitted/late/under_review/revision_requested/resubmitted/graded.

Guru mengatur jadwal, file, size, late, revision, max revisions.
Siswa draft, upload, submit, histori versi, revisi.

Private storage, MIME/size validation, random physical filename, download policy, cleanup orphan.
Test Storage fake.
```

# Fase 14 — Rubrik dan Nilai

```text
Buat rubrics, criteria, levels, submission_scores, grades.

Level 1–4. Rubrik reusable, bobot, auto calculation, split grading desktop, tab mobile, feedback, private note, revision request, publish, histori, audit, remedial.

Nilai hanya terlihat setelah publish.
Test calculation dan policy.
```

# Fase 15 — Portofolio

```text
Buat portfolio_items: karya graded/mandiri, thumbnail, deskripsi, refleksi, sumber, deklarasi AI, versi awal/final, featured, visibility, approval, filter, premium page, print view.

File private sampai visibility/approval valid.
Test kebocoran akses.
```

# Fase 16 — Pengumuman dan Forum

```text
Buat announcements, discussion_topics, discussion_posts.

Audience, priority, publish/expire, read status, forum kelas/pertemuan, balasan satu tingkat, pin, close, hide, report, moderasi, database notification.

Tidak ada direct message dan websocket.
Test cross-class dan moderasi.
```

# Fase 17 — Dashboard Pembina dan Kepala Sekolah

```text
Buat dashboard data nyata.

Pembina: siswa aktif, pendaftaran, onboarding, pertemuan, tugas belum dinilai, revisi, kehadiran, catatan, absen pengajar, siswa perlu perhatian, grafik.

Kepala sekolah: 30 pertemuan, absen pengajar, kehadiran, nilai, catatan, dokumen, laporan ringkas.

Optimalkan query dan hindari N+1. Test scope.
```

# Fase 18 — Laporan

```text
Buat laporan siswa, onboarding, kehadiran, absen pengajar, tugas, keterlambatan, nilai, remedial, portofolio, catatan, pertemuan, dokumen.

Filter tahun, kelas, semester, tanggal. Print A4.
PDF/Excel hanya jika package dijelaskan dan disetujui.
Data wajib mengikuti Policy.
```

# Fase 19 — Responsive UI Polish

```text
Audit seluruh halaman pada 375×812, 430×932, 768×1024, 1024×768, 1366×768, 1440×900, 1920×1080.

Perbaiki overflow, navbar, sidebar, bottom nav, card, table, wizard, modal, offcanvas, sticky action, typography, spacing, loading, empty, error, success, touch target, focus, contrast, keyboard.

Mobile: table menjadi card, filter offcanvas, sticky submit, tidak ada hover-only action.
Jangan mengubah business workflow.
```

# Fase 20 — Security Hardening

```text
Audit OAuth state, domain, kode, rate limit, role escalation, policies, cross-user/class, IDOR, CSRF, XSS, mass assignment, upload, storage, grade, signature, document audience, portfolio visibility, audit, session, APP_DEBUG.

Tambahkan tests unauthorized, invalid, duplicate, expired, suspended, private file, unpublished grade/material, wrong audience, cross-student submission.
Jangan menonaktifkan test.
```

# Fase 21 — Deployment

```text
Buat .env.example, docs/DEPLOYMENT.md, PHP extension checklist, Composer check, document root /public, storage, migration, safe seeding, cache, npm build, permission, HTTPS, Google redirect production, backup, rollback, smoke test, scheduler/queue bila digunakan.

Laravel tetap 12.x. Jangan migrate:fresh/db:wipe. APP_DEBUG=false.
```

# Format Laporan Wajib

```text
## Fase Diselesaikan
## Scope
## File Baru
## File Diubah
## Migration
## Model/Controller/Request/Policy
## Test Baru
## Command
## Hasil Test
## Hasil Build
## Kendala
## Keterbatasan
## Rekomendasi Fase Berikutnya
STOP — menunggu instruksi.
```

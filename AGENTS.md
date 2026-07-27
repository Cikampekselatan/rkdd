# AGENTS.md — SKUAD Learning Hub

## Identitas

SKUAD Learning Hub untuk SMP IT Mentari Ilmu Jatisari. Pembina: Andi Apriandi, S.T. Struktur: 15 modul, 30 pertemuan, 90 menit.

## Stack Dikunci

- PHP
- Laravel 12
- MySQL
- Blade
- HTML5
- CSS3
- JavaScript murni
- Bootstrap 5
- Bootstrap Icons
- Vite

Dilarang menggunakan Laravel 13, React, Vue, Inertia, Livewire, Tailwind, SPA terpisah, atau database lain tanpa persetujuan.

## Wajib Dibaca

1. `AGENTS.md`
2. `docs/BLUEPRINT_SKUAD_LEARNING_HUB_PREMIUM_V2.md`
3. Fase aktif di `docs/PROMPT_CODEX_SKUAD_LEARNING_HUB_PREMIUM_V2.md`

## Kontrol Scope

Sebelum coding: audit repository, scope, rencana, file, migration, test.

Saat coding:

- Jangan menambah fitur di luar fase.
- Jangan mengubah modul tidak terkait.
- Jangan refactor besar tanpa permintaan.
- Jangan memasang package tanpa persetujuan.
- Jangan membuat tabel di phpMyAdmin.
- Jangan mengubah credential production.
- Jangan menjalankan command destruktif.

Setelah coding: formatter, build, test, laporan, lalu berhenti.

## Arsitektur

- Modular monolith.
- Thin controller.
- Form Request.
- Policy.
- Action/Service untuk workflow.
- PHP Enum untuk status.
- Eloquent.
- DB transaction untuk multi-tabel.
- Named routes.
- Pagination.
- Cegah N+1.
- Soft delete bila perlu.
- Audit log untuk nilai, role, tanda tangan, kehadiran, publikasi.

## Authentication

Siswa:

- Google OAuth wajib.
- Domain default gmail.com.
- Status awal onboarding.
- Kode pendaftaran wajib.
- Role student hanya setelah finalisasi.
- Aktivasi otomatis setelah onboarding lengkap.

Staff:

- Akun dibuat admin.
- Local login.
- Registrasi local publik dilarang.

## UI

- Super premium tetapi tenang.
- Mobile-first.
- Bootstrap 5.
- Design tokens SKUAD.
- Desktop floating sidebar.
- Tablet offcanvas.
- Mobile bottom navigation.
- Touch target minimal 44 px.
- Tidak ada hover-only action.
- Tabel menjadi card bila perlu.
- Form panjang menjadi wizard.
- Semua halaman: loading, empty, error, success, disabled.

## Security

- Password hashed.
- Google token tidak disimpan jika tak perlu.
- File siswa/tanda tangan private.
- Download via controller+Policy.
- Validasi MIME/size.
- Cegah IDOR.
- Siswa hanya melihat data sendiri.
- Guru hanya kelas berwenang.
- Nilai setelah publish.
- Dokumen sesuai audience.
- Portofolio sesuai visibility/approval.
- CSRF aktif.
- Rate limit OAuth/kode.
- APP_DEBUG=false production.

## Testing

Setiap fitur menguji authorized, unauthorized, validation, success, persistence, duplicate, edge cases, cross-user/class. Gunakan factories, seeders, RefreshDatabase, Storage fake.

Jangan menghapus test agar suite lulus.

## Laporan Akhir

Scope, file baru/diubah, migration, tests, commands, hasil, build, keterbatasan, fase berikutnya. Berhenti.

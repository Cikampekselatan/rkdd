# Blueprint Pengembangan RKDD Multi-Program Platform

Dokumen ini menjadi rujukan utama untuk pengembangan lanjutan aplikasi dari **SKUAD Learning Hub** menjadi **Ruang Komunitas Digital Desa (RKDD) Cikampek Selatan**. Tujuannya agar pengerjaan tidak melebar, tidak mengulang keputusan lama, dan tetap menjaga fitur yang sudah dibangun tetap berjalan.

## 1. Visi Produk

RKDD Cikampek Selatan adalah satu platform digital untuk mengelola berbagai kegiatan, ekstrakurikuler, pelatihan, komunitas, dan program pemberdayaan digital dalam satu aplikasi.

SKUAD tidak dihapus. SKUAD menjadi salah satu program awal/default di dalam RKDD.

Contoh program/kegiatan:

- SKUAD
- Konten Kreator
- Jurnalis Digital
- Affiliate
- Fotografi
- Videografi
- Pelatihan UMKM Digital
- Pelatihan Warga
- Komunitas Digital Desa
- Program lain yang dibuat manual oleh Super Admin

Semua program memakai alur fitur yang sama seperti sistem SKUAD yang sudah dibangun.

## 2. Prinsip Scope

Pengembangan tidak boleh melebar ke luar prinsip berikut:

1. Satu platform, banyak program.
2. Tidak memakai subdomain pada tahap ini.
3. Super Admin mengelola seluruh platform dan semua program.
4. Setiap program memiliki data, peserta, staff, materi, presensi, tugas, nilai, diskusi, laporan, dan showcase sendiri.
5. Fitur yang sudah ada tidak dibuang, tetapi dinaikkan menjadi berbasis `program`.
6. Branding utama berubah menjadi RKDD Cikampek Selatan.
7. SKUAD tetap tersedia sebagai program default agar data lama tidak hilang.
8. Desain tiap program boleh berbeda warna/karakter, tetapi alur fitur tetap sama.
9. Deploy dilakukan setelah migrasi data, pengujian, dan konfigurasi produksi siap.

## 3. Istilah Baru

| Istilah Lama | Istilah Baru RKDD |
|---|---|
| SKUAD Learning Hub | RKDD Cikampek Selatan |
| Siswa | Peserta/Peserta Didik, mengikuti konteks program |
| Guru/Pembina | Pembina/Penanggung Jawab |
| Instruktur/Coach | Instruktur/Coach/Fasilitator |
| Kelas SKUAD | Kelompok/Angkatan |
| Tahun Ajaran | Periode Program |
| Ekstrakurikuler | Program/Kegiatan |
| Dashboard Publik | Beranda RKDD |
| Hasil Terbaik Publik | Showcase Karya |

Catatan:

- Perubahan istilah harus bertahap. Jangan mengganti semua sekaligus sebelum `program_id` dan migrasi data aman.
- Untuk program berbasis sekolah, istilah peserta boleh tetap **Siswa**.
- Untuk program komunitas, desa, mitra, atau pelatihan umum, istilah peserta dapat menjadi **Peserta**, **Peserta Didik**, atau istilah lain yang ditentukan Super Admin.
- Istilah harus dibaca dari konfigurasi program/batch, bukan dibuat statis di seluruh aplikasi.

## 3.1 Konteks Program, Lembaga, dan Batch

RKDD tidak hanya untuk komunitas warga. Platform harus mendukung kegiatan berbasis sekolah, desa, komunitas, organisasi, UMKM, dan mitra pelatihan.

Prinsip data:

- satu nama program dapat dijalankan di banyak lembaga berbeda;
- lembaga dapat berupa sekolah, komunitas, desa/RKDD, organisasi, atau mitra;
- program yang sama boleh memiliki beberapa batch/periode pelaksanaan;
- setiap batch/periode dapat memiliki nama lembaga, tahun ajaran/periode, tema visual, dan istilah peserta sendiri;
- data peserta, staff, absensi, tugas, asesmen, dokumentasi, pengumuman, dan laporan harus terikat pada konteks program/batch agar tidak tercampur.

Contoh:

| Program | Peruntukan | Lembaga | Periode/Batch | Sebutan Peserta |
|---|---|---|---|---|
| SKUAD | Sekolah | SMP/MTs tertentu | Tahun Ajaran 2026/2027 | Siswa |
| SKUAD | Sekolah | Sekolah lain | Tahun Ajaran 2026/2027 | Siswa |
| Konten Kreator | Komunitas | RKDD Cikampek Selatan | Batch 1 | Peserta Didik |
| Affiliate UMKM | Komunitas/UMKM | Komunitas UMKM Desa | Batch Juli 2026 | Peserta |

## 4. Role dan Hak Akses

### 4.1 Super Admin

Mengelola seluruh platform.

Wewenang:

- membuat program/kegiatan baru;
- mengedit program;
- menonaktifkan program;
- menghapus program jika belum memiliki data penting;
- mengatur warna/tema program;
- mengatur logo/banner program;
- mengelola semua user lintas program;
- melihat semua dashboard dan laporan;
- mengatur pengelola program;
- mengatur konfigurasi platform;
- melakukan backup/export;
- mengakses checklist deploy.

### 4.2 Admin Program

Mengelola administrasi pada program tertentu.

Wewenang:

- mengelola peserta program;
- mengelola kelompok/angkatan;
- mengelola kode pendaftaran;
- melihat laporan program;
- melihat dokumentasi, catatan, dan presensi yang sudah diverifikasi;
- tidak mengubah konfigurasi global platform.

### 4.3 Pembina/Penanggung Jawab

Memantau dan memverifikasi alur pembinaan/program.

Wewenang:

- melihat dashboard program;
- melihat pembelajaran, tugas, nilai, asesmen, portofolio;
- menandatangani/verifikasi absen pengajar dari Instruktur/Coach;
- menandatangani/paraf catatan penting dari Instruktur/Coach;
- melihat laporan program;
- tidak membuat absen pengajar dan catatan penting.

### 4.4 Instruktur/Coach/Fasilitator

Pelaksana pembelajaran dan kegiatan.

Wewenang:

- membuat materi/pertemuan;
- membuka presensi peserta;
- menampilkan QR presensi;
- memberi tugas;
- menilai tugas;
- membuat rubrik;
- membuat asesmen berkala;
- mengelola portofolio peserta;
- membuat pengumuman;
- mengelola diskusi;
- membuat absen pengajar;
- membuat catatan penting;
- membuat dokumentasi kegiatan.

### 4.5 Peserta

Mengikuti program.

Wewenang:

- melihat program yang diikuti;
- mengakses materi;
- scan QR presensi;
- mengerjakan tugas;
- melihat nilai;
- melihat asesmen;
- membuat/menampilkan portofolio;
- mengikuti diskusi;
- menerima notifikasi;
- mengunggah foto profil.

## 5. Data Induk Baru yang Wajib Ada

### 5.1 Program/Kegiatan

Entitas utama baru: `Program`.

Kolom konseptual:

- `id`
- `name`
- `slug`
- `type`
- `description`
- `short_description`
- `logo_path`
- `banner_path`
- `primary_color`
- `secondary_color`
- `accent_color`
- `icon`
- `status`
- `is_featured`
- `created_by`
- `updated_by`

Jenis program:

- Ekstrakurikuler
- Pelatihan
- Komunitas
- Workshop
- Kursus
- Inkubasi
- Event

Status:

- draft
- aktif
- nonaktif
- arsip

### 5.2 Keanggotaan Program Staff

Super Admin bisa menugaskan staff ke program tertentu.

Contoh:

- user A menjadi Instruktur di SKUAD;
- user A juga bisa menjadi Instruktur di Konten Kreator;
- user B menjadi Pembina di Jurnalis Digital.

Konsep tabel:

- `program_user`
- `program_id`
- `user_id`
- `role_in_program`
- `is_active`

### 5.3 Peserta Program

Peserta bisa mengikuti satu atau lebih program.

Konsep tabel:

- `program_participants`
- `program_id`
- `user_id`
- `period_id`
- `group_id`
- `status`
- `joined_at`
- `left_at`
- `exit_reason`

## 6. Data Existing yang Perlu Dinaikkan ke Program

Semua fitur utama harus diberi relasi ke `program_id`.

Data yang harus diprogramkan:

- periode/tahun ajaran;
- kelompok/kelas;
- kode pendaftaran;
- profil peserta;
- pertemuan;
- modul pembelajaran;
- materi;
- presensi;
- tugas;
- pertanyaan tugas;
- submission;
- rubrik;
- nilai;
- remedial;
- asesmen bulanan/berkala;
- portofolio;
- kelompok proyek;
- pengumuman;
- diskusi;
- dokumen;
- showcase karya;
- dokumentasi kegiatan;
- absen pengajar;
- catatan penting;
- laporan.

Aturan penting:

- Data lama SKUAD harus otomatis dimasukkan ke program default `SKUAD`.
- Jangan menghapus data lama.
- Migrasi harus idempotent.
- Setelah migrasi, semua query dashboard dan laporan harus terfilter berdasarkan program aktif yang dipilih.

## 7. Alur Utama Platform

### 7.1 Alur Super Admin

1. Login ke dashboard RKDD.
2. Melihat ringkasan semua program.
3. Membuat program baru.
4. Mengatur warna, logo, banner, dan karakter program.
5. Menugaskan Admin Program, Pembina, dan Instruktur.
6. Membuka periode program.
7. Membuat/menyetujui kode pendaftaran.
8. Memantau laporan lintas program.

### 7.2 Alur Admin Program

1. Login.
2. Memilih program yang dikelola.
3. Mengelola peserta.
4. Mengelola kelompok/angkatan.
5. Mengelola kode pendaftaran.
6. Melihat laporan administrasi.
7. Mencetak laporan.

### 7.3 Alur Instruktur/Coach/Fasilitator

1. Login.
2. Memilih program.
3. Membuat pertemuan/materi.
4. Membuka presensi peserta.
5. Menampilkan QR presensi di layar kelas/ruang pelatihan.
6. Membuat tugas.
7. Menilai tugas.
8. Membuat asesmen berkala.
9. Membuat dokumentasi kegiatan.
10. Membuat absen pengajar.
11. Membuat catatan penting.
12. Mengirim untuk ditandatangani Pembina/Penanggung Jawab.

### 7.4 Alur Pembina/Penanggung Jawab

1. Login.
2. Memilih program.
3. Memantau dashboard pembinaan.
4. Melihat materi, tugas, nilai, asesmen, diskusi, dan portofolio.
5. Menandatangani absen pengajar dari Instruktur.
6. Memaraf catatan penting.
7. Melihat laporan.

### 7.5 Alur Peserta

1. Membuka beranda RKDD.
2. Memilih program.
3. Mendaftar dengan kode program.
4. Mengisi form profil.
5. Login menggunakan Google.
6. Mengikuti materi.
7. Scan QR presensi.
8. Mengerjakan tugas.
9. Melihat nilai dan asesmen.
10. Mengunggah portofolio.
11. Ikut diskusi.
12. Menerima notifikasi.

## 8. Beranda Utama RKDD

Beranda utama bukan lagi menjelaskan SKUAD saja, tetapi menjelaskan RKDD sebagai rumah kegiatan digital.

### 8.1 Identitas Beranda

Nama:

**Ruang Komunitas Digital Desa Cikampek Selatan**

Tagline:

**Belajar digital, berkarya, dan membangun peluang kreatif dari desa.**

### 8.2 Section Beranda

1. Hero premium
   - judul besar;
   - deskripsi kuat;
   - CTA Gabung Program;
   - CTA Lihat Showcase;
   - CTA Masuk Dashboard.

2. Program aktif
   - card SKUAD, Konten Kreator, Jurnalis Digital, Affiliate, dan lainnya;
   - warna card mengikuti tema program.

3. Statistik dampak
   - jumlah peserta aktif;
   - jumlah program berjalan;
   - jumlah karya;
   - jumlah pertemuan;
   - jumlah dokumentasi.

4. Showcase karya terbaik
   - karya minggu ini;
   - karya bulan ini;
   - foto/video/link karya.

5. Jadwal kegiatan
   - program;
   - tanggal;
   - lokasi;
   - instruktur.

6. Dokumentasi kegiatan
   - foto kegiatan;
   - link video;
   - album.

7. Cerita dampak/testimoni
   - peserta;
   - instruktur;
   - mitra.

8. Mitra/kolaborator
   - desa;
   - sekolah;
   - komunitas;
   - sponsor;
   - UMKM.

9. CTA akhir
   - Gabung Program;
   - Hubungi RKDD;
   - Lihat Program.

### 8.3 Karakter UI Beranda

Rasa desain:

- super premium;
- profesional;
- modern;
- komunitas digital;
- tidak terlalu sekolah;
- tetap hangat dan lokal;
- cocok untuk desa, sekolah, komunitas, UMKM, dan mitra.

Gaya visual:

- gradient gelap premium;
- glass card;
- warna aksen per program;
- animasi ringan;
- statistik besar;
- showcase visual;
- layout responsive mobile-first.

## 9. Tema Warna Per Program

Super Admin dapat memilih warna program.

Contoh preset:

| Program | Primary | Secondary | Accent | Karakter |
|---|---|---|---|---|
| SKUAD | Teal | Navy | Cyan | edukatif, teknologi |
| Konten Kreator | Purple | Pink | Orange | kreatif, energik |
| Jurnalis Digital | Navy | White | Gold | profesional, literasi |
| Affiliate | Emerald | Black | Lime | bisnis, performa |
| UMKM Digital | Orange | Cream | Brown | lokal, ekonomi |
| Fotografi | Black | Silver | Amber | visual, artistik |
| Videografi | Red | Black | Violet | sinematik |

Aturan:

- warna program harus tersimpan di database;
- dashboard program membaca warna aktif;
- tombol, badge, hero, dan aksen mengikuti warna program;
- warna harus tetap memenuhi kontras aksesibilitas.

## 10. Roadmap Pengerjaan Tahap Demi Tahap

### Fase 0 — Persiapan dan Pembekuan Scope

Tujuan:

- memastikan semua pihak memahami perubahan dari SKUAD menjadi RKDD multi-program.

Checklist:

- baca blueprint SKUAD lama;
- baca blueprint RKDD ini;
- catat fitur yang sudah selesai;
- catat fitur yang belum stabil;
- pastikan full test aplikasi saat ini hijau;
- backup database lokal;
- backup `.env`;
- pastikan tidak mengubah fitur besar sebelum struktur `Program` siap.

Keluaran:

- aplikasi existing aman;
- dokumen rujukan siap;
- keputusan istilah disetujui.

### Fase 1 — Branding Platform RKDD

Tujuan:

- mengganti identitas publik dari SKUAD-only menjadi RKDD.

Checklist:

- ubah nama aplikasi publik menjadi RKDD Cikampek Selatan;
- ubah landing page menjadi beranda RKDD;
- SKUAD tetap disebut sebagai salah satu program;
- ubah meta title, tagline, dan copywriting utama;
- jangan ubah struktur database dulu;
- pastikan login staff/siswa tetap berjalan.

Keluaran:

- beranda utama RKDD premium;
- SKUAD masih berjalan seperti sebelumnya.

### Fase 2 — Master Data Program

Tujuan:

- menambahkan kemampuan Super Admin membuat dan mengelola program/kegiatan beserta konteks lembaga dan batch/periode pelaksanaannya.

Checklist:

- buat tabel `programs`;
- buat tabel/struktur master lembaga atau penyelenggara;
- buat tabel/struktur batch/periode program;
- buat model Program;
- buat model Lembaga/Penyelenggara;
- buat model Batch/Periode Program;
- buat CRUD Program untuk Super Admin;
- buat CRUD Lembaga/Penyelenggara untuk Super Admin;
- buat CRUD Batch/Periode Program untuk Super Admin;
- field minimal program: nama, slug, tipe, deskripsi, warna, logo, banner, status;
- field minimal lembaga: nama, tipe lembaga, alamat/keterangan, status;
- field minimal batch/periode: program, lembaga, nama batch/periode, tahun ajaran/periode, peruntukan, sebutan peserta, status;
- validasi slug unik;
- program tidak boleh dihapus jika sudah punya data;
- buat program default `SKUAD`;
- buat lembaga default sesuai data SKUAD existing;
- buat batch/periode default untuk data SKUAD existing;
- tambahkan menu Program di Super Admin.

Keluaran:

- Super Admin bisa membuat, mengedit, menonaktifkan, dan menghapus program kosong.
- Super Admin bisa menjalankan program yang sama di sekolah/lembaga berbeda tanpa data bercampur.
- UI dapat menampilkan istilah peserta sesuai konteks, misalnya Siswa untuk sekolah dan Peserta Didik untuk komunitas.

### Fase 3 — Tema Visual Per Program

Tujuan:

- setiap program punya karakter desain sendiri.

Checklist:

- simpan warna utama, warna sekunder, warna aksen;
- buat preview tema di form program;
- dashboard membaca tema program aktif;
- buat preset warna;
- pastikan kontras teks aman;
- logo/banner bisa diupload dan dikompres.

Keluaran:

- program SKUAD, Konten Kreator, Jurnalis, dll dapat punya warna berbeda.

### Fase 4 — Program Context Switcher

Tujuan:

- user staff dapat memilih program aktif dari dashboard.

Checklist:

- buat komponen pemilih program aktif;
- simpan program aktif di session;
- Super Admin bisa melihat semua program;
- Admin Program hanya melihat program yang dikelola;
- Pembina/Instruktur hanya melihat program yang ditugaskan;
- Peserta hanya melihat program yang diikuti;
- fallback ke program default SKUAD jika belum ada pilihan.

Keluaran:

- semua dashboard punya konteks program aktif.

### Fase 5 — Migrasi Data Lama ke Program SKUAD

Tujuan:

- semua data SKUAD lama masuk ke program default.

Checklist:

- tambahkan `program_id` pada tabel utama;
- isi `program_id` existing dengan program SKUAD;
- buat migrasi idempotent;
- update factory dan seeder;
- update query dasar;
- pastikan data lama tidak hilang.

Keluaran:

- data lama tetap muncul;
- semua data existing punya `program_id`.

### Fase 6 — Filter Semua Fitur Berdasarkan Program Aktif

Tujuan:

- data antar program tidak bercampur.

Checklist fitur:

- peserta;
- kelompok/angkatan;
- periode;
- kode pendaftaran;
- pembelajaran;
- presensi;
- tugas;
- rubrik;
- nilai;
- asesmen;
- portofolio;
- pengumuman;
- diskusi;
- dokumen;
- showcase;
- dokumentasi;
- absen pengajar;
- catatan penting;
- laporan.

Keluaran:

- user hanya melihat data program yang dipilih/diizinkan.

### Fase 7 — Perubahan Istilah UI Bertahap

Tujuan:

- UI tidak lagi terlalu sekolah/SKUAD-only.

Checklist:

- Siswa menjadi Peserta;
- Kelas SKUAD menjadi Kelompok/Angkatan;
- Tahun Ajaran menjadi Periode Program;
- Guru/Pembina menjadi Pembina/Penanggung Jawab;
- Instruktur/Coach tetap dipakai dengan tambahan Fasilitator;
- Hasil Terbaik Publik menjadi Showcase Karya;
- Dashboard publik menjadi Beranda RKDD.

Keluaran:

- UI lebih umum untuk ekstrakurikuler, pelatihan, komunitas, dan kegiatan desa.

### Fase 8 — Pendaftaran Peserta Multi-Program

Tujuan:

- peserta bisa mendaftar ke program tertentu.

Checklist:

- kode pendaftaran terkait program;
- form profil menyesuaikan program;
- peserta memilih/memasukkan kode program;
- Google login tetap setelah form awal;
- peserta bisa ikut lebih dari satu program;
- peserta tidak bisa melihat program yang belum diikuti.

Keluaran:

- alur pendaftaran multi-program berjalan.

### Fase 9 — Dashboard Per Program

Tujuan:

- dashboard tetap sama alurnya, tetapi berbasis program.

Checklist:

- dashboard Super Admin lintas program;
- dashboard Admin Program;
- dashboard Pembina/Penanggung Jawab;
- dashboard Instruktur/Coach/Fasilitator;
- dashboard Peserta;
- semua KPI terfilter program;
- warna dashboard mengikuti tema program.

Keluaran:

- setiap program punya dashboard operasional yang sama.

### Fase 10 — Pembelajaran dan Tugas Multi-Program

Tujuan:

- materi, pertemuan, tugas, rubrik, dan submission berjalan per program.

Checklist:

- modul/pertemuan terkait program;
- tugas terkait program;
- pertanyaan tugas tetap lengkap;
- rubrik terkait program;
- nilai masuk ke peserta program;
- notifikasi tetap berjalan.

Keluaran:

- alur belajar semua program sama seperti SKUAD.

### Fase 11 — Presensi Multi-Program

Tujuan:

- QR presensi dan daftar hadir berjalan untuk semua program.

Checklist:

- sesi presensi terkait program;
- QR tetap hanya untuk peserta aktif program;
- staff bisa membuka link QR hanya sebagai preview/informasi;
- peserta scan dari layar instruktur;
- izin/sakit tetap tercatat;
- export CSV;
- cetak/PDF premium;
- matriks pertemuan dinamis sesuai program aktif, bukan angka tetap 30;
- jumlah kolom matriks mengikuti daftar pertemuan yang dibuat pada program tersebut;
- laporan kehadiran per program.

Keluaran:

- presensi semua program rapi dan bisa dicetak.

### Fase 12 — Asesmen, Nilai, Portofolio, dan Showcase

Tujuan:

- semua hasil belajar dan karya peserta terpisah per program.

Checklist:

- asesmen berkala terkait program;
- nilai terkait program;
- portofolio terkait program;
- karya unggulan per program;
- showcase beranda RKDD bisa menampilkan lintas program;
- filter showcase per program.

Keluaran:

- karya dan nilai peserta tidak bercampur antar program.

### Fase 13 — Diskusi, Pengumuman, dan Notifikasi

Tujuan:

- komunikasi berjalan per program.

Checklist:

- diskusi terkait program;
- pengumuman terkait program;
- notifikasi tugas, nilai, diskusi, presensi, catatan, portofolio tetap berjalan;
- notifikasi lintas program tidak bocor;
- bell notification tetap global, tetapi setiap item jelas programnya.

Keluaran:

- komunikasi semua program aktif dan aman.

Status implementasi:

- selesai;
- policy pengumuman, topik diskusi, dan moderasi pesan sudah mengunci akses direct URL ke program aktif;
- validasi form pengumuman dan diskusi menolak kelas/pertemuan dari program lain;
- topik diskusi siswa multi-program memakai membership program aktif;
- penerima notifikasi pengumuman peserta disaring berdasarkan program;
- notifikasi pengumuman dan balasan diskusi membawa label konteks program di bell global;
- regresi dikunci oleh `Phase13CommunicationProgramIsolationTest`.

### Fase 14 — Dokumen dan Dokumentasi Kegiatan

Tujuan:

- dokumen staff/peserta dan dokumentasi kegiatan berjalan per program.

Checklist:

- dokumen punya program_id;
- audience dokumen tetap berjalan;
- dokumentasi kegiatan per program;
- foto dikompres;
- video menggunakan URL;
- admin/kepala/pembina bisa melihat sesuai role.

Keluaran:

- dokumentasi dan dokumen program tertata.

Status implementasi:

- selesai;
- Document Center sudah menyimpan dan memfilter dokumen berdasarkan program aktif;
- akses direct URL untuk lihat, edit, publish, pin, arsip, dan hapus dokumen lintas program ditolak;
- audience dokumen staff/peserta tetap berjalan, termasuk larangan dokumen RPP/kurikulum/silabus/form administrasi dipublikasikan ke peserta;
- pustaka dokumen peserta multi-program memakai membership program aktif dan tahun ajaran program aktif;
- dokumentasi kegiatan tersimpan per program aktif;
- akses direct URL dokumentasi kegiatan lintas program ditolak untuk staff, admin, kepala, dan pembina;
- foto dokumentasi tetap dikompres maksimal 500 KB, video tetap URL;
- regresi dikunci oleh `Phase14DocumentProgramIsolationTest`.

### Fase 15 — Laporan Multi-Program

Tujuan:

- laporan dapat dilihat per program dan lintas program sesuai role.

Checklist:

- Super Admin melihat lintas program;
- Admin Program melihat programnya;
- Pembina/Instruktur melihat programnya;
- Kepala/Pimpinan melihat program yang diberi akses;
- export CSV;
- cetak/PDF premium;
- matriks presensi;
- laporan asesmen;
- laporan nilai;
- laporan dokumentasi;
- laporan catatan penting.

Keluaran:

- laporan siap untuk sekolah, desa, komunitas, dan mitra.

Status implementasi:

- selesai;
- Super Admin dapat melihat laporan lintas program atau memfilter satu program tertentu;
- admin, guru/pembina, instruktur/coach, dan kepala/pimpinan dikunci ke program aktif;
- request filter laporan ke program lain untuk non-Super Admin ditolak;
- filter kelas divalidasi agar sesuai tahun ajaran dan program laporan;
- semua laporan umum punya export CSV;
- tampilan cetak/PDF berbasis print A4 tetap tersedia;
- matriks presensi mengikuti program aktif atau program yang dipilih Super Admin;
- laporan asesmen bulanan ditambahkan ke pusat laporan;
- laporan dokumentasi kegiatan ditambahkan ke pusat laporan;
- laporan nilai, remedial, catatan penting, dokumen, pertemuan, presensi, tugas, keterlambatan, onboarding, dan peserta tetap berjalan;
- regresi dikunci oleh `Phase15MultiProgramReportsTest`.

### Fase 16 — Keamanan dan Hak Akses

Tujuan:

- data antar program tidak bocor.

Checklist:

- policy semua model memeriksa program;
- route middleware aman;
- user tidak bisa akses program tanpa izin;
- peserta tidak bisa akses data peserta lain;
- Super Admin tetap bisa lintas program;
- audit log tetap berjalan;
- CSRF dan security headers tetap aktif;
- file privat tetap aman.

Keluaran:

- aplikasi aman untuk multi-program.

Status implementasi:

- selesai pada fase 16;
- policy nilai/submission, presensi, catatan penting, log aktivitas pengajar, kode pendaftaran, kelas, dan master siswa diperketat agar mengikuti konteks program aktif;
- guru, pembina, dan instruktur/coach tidak dapat membuka, mengubah, menandatangani, mengunduh, atau menyelesaikan data dari program lain melalui akses URL langsung;
- peserta hanya dapat melihat data miliknya sendiri dalam program aktif;
- Super Admin tetap disiapkan sebagai peran lintas-program;
- regresi keamanan dikunci oleh `Phase16SecurityProgramBoundaryTest`.

### Fase 17 — Pengujian Menyeluruh

Tujuan:

- memastikan migrasi besar tidak merusak fitur lama.

Checklist:

- unit test;
- feature test;
- policy test;
- route test;
- role boundary test;
- test migrasi data lama ke SKUAD;
- test multi-program data isolation;
- test pendaftaran multi-program;
- test export/print;
- test notifikasi;
- test upload/compress;
- test QR presensi.

Keluaran:

- seluruh test hijau.

Status implementasi:

- selesai pada fase 17;
- seluruh checklist pengujian dipetakan dalam `docs/PHASE17_TESTING_REPORT.md`;
- verifikasi terakhir: `php artisan test` hijau dengan 231 test dan 1741 assertion;
- build produksi Vite berhasil;
- cache Blade berhasil dibuat;
- tidak ada migration baru pada fase ini.

### Fase 18 — Polishing UI Premium

Tujuan:

- memberi kesan profesional, modern, dan layak untuk publik;
- menjadikan beranda sebagai wajah resmi gerakan/program RKDD Cikampek Selatan, bukan sekadar tampilan aplikasi;
- membangun kesan pertama bahwa RKDD adalah ruang belajar, berkarya, berbagi ilmu, dan kolaborasi digital untuk sekolah, komunitas, warga, dan mitra.

Checklist:

- beranda RKDD super premium dengan copywriting yang hangat, kuat, dan mengundang;
- narasi utama beranda menonjolkan program RKDD, bukan fitur teknis aplikasi;
- contoh arah copy hero: "Ruang tumbuh generasi digital Cikampek Selatan";
- CTA utama mengarah ke domain utama `http://digicomciksel.com`;
- CTA internal menuju daftar program, Ruang Ilmu, dan Karya Terbaik;
- karusel foto kegiatan RKDD yang dapat dikelola Super Admin;
- setiap item karusel memiliki foto, judul, deskripsi pendek, urutan tampil, dan status publish/archive;
- video profil RKDD dari URL yang dapat dikelola Super Admin;
- section program unggulan RKDD dengan card premium dan warna sesuai karakter program;
- section "Ruang Ilmu" sebagai ruang bacaan dan video tutorial bermanfaat untuk siswa/peserta;
- Ruang Ilmu berisi thumbnail, judul, deskripsi, kategori, tipe konten, dan URL;
- tipe konten Ruang Ilmu minimal: eBook/bacaan, artikel, panduan, dan video tutorial;
- hanya Super Admin yang dapat membuat, mengedit, menghapus, publish, dan archive konten Ruang Ilmu;
- halaman publik Ruang Ilmu dapat dibuka dari beranda;
- section "Karya Terbaik" sebagai showcase karya siswa/peserta lintas program;
- halaman Karya Terbaik menampilkan karya pilihan mingguan, bulanan, pilihan instruktur, dan pilihan Super Admin;
- instruktur/coach dapat mengusulkan atau mengisi karya terbaik dari programnya;
- Super Admin dapat mengkurasi karya terbaik lintas program dan menentukan yang tampil di beranda;
- setiap karya terbaik menampilkan judul, nama siswa/peserta, program, deskripsi proses, alasan dipilih, media/thumbnail, dan URL karya;
- section "Mengapa RKDD?" menjelaskan manfaat RKDD: praktik nyata, pendampingan, dokumentasi proses, karya publik, dan kolaborasi;
- section alur bergabung: pilih program, dapatkan kode, lengkapi profil, belajar, presensi, kumpulkan karya, tampil di showcase;
- statistik dampak publik: program aktif, peserta aktif, karya terkumpul, pertemuan berjalan, dan jumlah konten Ruang Ilmu;
- jejak kegiatan terbaru dari dokumentasi kegiatan yang dipublikasikan;
- CTA kolaborasi untuk sekolah, komunitas, warga, UMKM, atau lembaga mitra;
- dashboard responsive;
- card program premium;
- warna per program konsisten;
- empty state rapi;
- halaman error rapi;
- print/PDF premium;
- mobile layout nyaman;
- profil/foto semua user rapi;
- copywriting tidak kaku.

Keluaran:

- aplikasi siap diperlihatkan ke publik/mitra;
- beranda RKDD terasa seperti website profesional gerakan digital desa;
- pengunjung memahami program RKDD tanpa harus login;
- pengunjung dapat masuk ke Ruang Ilmu, melihat Karya Terbaik, mengenal program, menonton video profil, dan menuju domain utama `http://digicomciksel.com`.

Status implementasi:

- selesai pada fase 18;
- beranda publik RKDD diganti menjadi narasi program/gerakan RKDD, bukan sekadar aplikasi;
- CTA utama mengarah ke `http://digicomciksel.com`;
- halaman publik `Ruang Ilmu` tersedia di route `knowledge.index` (`/ruang-ilmu`);
- halaman publik `Karya Terbaik` tersedia di route `best-works.index` (`/karya-terbaik`);
- Super Admin dapat mengelola karusel foto kegiatan RKDD;
- Super Admin dapat mengelola konten Ruang Ilmu berupa eBook/bacaan, artikel, panduan, dan video tutorial dari URL;
- Super Admin dapat mengelola video profil RKDD dari URL;
- Showcase karya terbaik tetap dapat diisi oleh instruktur/coach dan Super Admin melalui fitur `Showcase Karya`;
- gaya visual premium ditambahkan untuk hero, karusel, video profil, Ruang Ilmu, dan halaman karya terbaik;
- migration lokal fase 18 berhasil dijalankan;
- regresi dikunci oleh `Phase18PublicHomeExperienceTest`;
- verifikasi terakhir: `php artisan test` hijau dengan 235 test dan 1764 assertion, `npm.cmd run build` berhasil, dan `php artisan view:cache` berhasil.

### Fase 19 — Persiapan Deploy

Tujuan:

- menyiapkan aplikasi untuk server produksi.

Checklist:

- tentukan domain utama;
- siapkan hosting/VPS;
- siapkan PHP sesuai Laravel;
- siapkan MySQL/MariaDB;
- siapkan SSL;
- siapkan storage link;
- siapkan `.env.production`;
- ubah APP_ENV=production;
- ubah APP_DEBUG=false;
- konfigurasi mail;
- konfigurasi Google OAuth redirect;
- konfigurasi backup database;
- konfigurasi scheduler Laravel;
- konfigurasi queue jika dipakai;
- konfigurasi file upload;
- build asset frontend;
- cache config, route, view;
- migrate production;
- seed role dan Super Admin;
- test login;
- test upload;
- test QR presensi;
- test export/PDF.

Keluaran:

- aplikasi siap online.

### Fase 20 — Deploy ke Web

Tujuan:

- menjalankan RKDD di domain produksi.

Checklist teknis:

- upload/pull source code ke server;
- install composer dependencies;
- install/build npm asset jika di server;
- setup database production;
- copy `.env.production` menjadi `.env`;
- generate APP_KEY jika server baru;
- migrate database;
- seed role dan Super Admin;
- jalankan storage link;
- set permission storage/bootstrap cache;
- jalankan optimize;
- set cron scheduler;
- set queue worker jika diperlukan;
- pasang SSL;
- uji domain publik;
- uji login staff;
- uji pendaftaran peserta;
- uji dashboard;
- uji upload foto;
- uji export CSV/PDF;
- uji notifikasi;
- uji QR presensi dari ponsel.

Keluaran:

- RKDD Cikampek Selatan online dan siap dipakai.

### Fase 21 — Post-Deploy Monitoring

Tujuan:

- memastikan aplikasi stabil setelah dipakai pengguna asli.

Checklist:

- pantau error log Laravel;
- pantau storage penuh/tidak;
- pantau database backup;
- pantau performa halaman dashboard;
- cek user onboarding;
- cek QR presensi;
- cek email/notification;
- cek laporan PDF/CSV;
- siapkan SOP reset password/staff;
- siapkan SOP backup dan restore.

Keluaran:

- aplikasi stabil digunakan harian.

## 11. Aturan Teknis Penting untuk Pengembangan Berikutnya

1. Jangan membuat fitur baru yang tidak terkait multi-program sebelum struktur program stabil.
2. Setiap fitur baru wajib mempertimbangkan `program_id`.
3. Setiap query dashboard/laporan wajib difilter program.
4. Setiap policy wajib memeriksa role dan program.
5. Super Admin boleh lintas program, role lain tidak.
6. Data lama harus tetap aman di program default SKUAD.
7. Setiap fase harus ditutup dengan test.
8. Setiap perubahan besar harus menjalankan:
   - `php artisan test`
   - `npm.cmd run build`
   - `php artisan view:cache`
9. Jangan deploy jika test gagal.
10. Jangan menghapus data produksi tanpa backup.

## 12. Definisi Selesai

Satu fase dianggap selesai jika:

- fitur sesuai checklist;
- UI dapat diakses role yang tepat;
- data tidak bocor antar program;
- test terkait lulus;
- tidak ada error Blade;
- tidak ada error build frontend;
- perubahan dicatat dalam ringkasan kerja.

## 13. Catatan Implementasi Awal

Urutan paling aman:

1. Selesaikan dan stabilkan aplikasi SKUAD existing.
2. Buat Program model dan CRUD Super Admin.
3. Buat program default SKUAD.
4. Migrasikan data lama ke SKUAD.
5. Baru ubah query menjadi berbasis program.
6. Setelah itu ubah UI/istilah secara bertahap.
7. Terakhir poles beranda RKDD dan deploy.

Jangan memulai dari mengganti semua teks/UI terlebih dahulu, karena risiko data dan query lebih besar. Fondasi `Program` harus menjadi prioritas.

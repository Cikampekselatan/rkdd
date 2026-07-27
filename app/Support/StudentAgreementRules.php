<?php

namespace App\Support;

class StudentAgreementRules
{
    /**
     * @return array<string, array{icon: string, title: string, summary: string, sections: array<int, array{heading: string, body: string}>}>
     */
    public static function all(): array
    {
        return [
            'agree_rules' => [
                'icon' => 'bi-journal-check',
                'title' => 'Tata tertib dan etika komunikasi',
                'summary' => 'Pedoman sikap belajar, komunikasi, kehadiran, dan kerja sama selama mengikuti SKUAD.',
                'sections' => [
                    ['heading' => 'Sikap belajar', 'body' => 'Siswa mengikuti kegiatan dengan jujur, disiplin, menghargai waktu, dan siap menerima arahan pembina/guru.'],
                    ['heading' => 'Etika komunikasi', 'body' => 'Siswa menggunakan bahasa yang sopan di kelas, forum, komentar, pesan, dan ruang diskusi digital. Tidak merendahkan, mengejek, menyebarkan gosip, atau memancing konflik.'],
                    ['heading' => 'Kerja kelompok', 'body' => 'Siswa membagi tugas secara adil, menghargai kontribusi teman, dan menyelesaikan konflik dengan bantuan guru/instruktur/coach bila diperlukan.'],
                    ['heading' => 'Keamanan kelas', 'body' => 'Siswa tidak membagikan akses akun, kode pendaftaran, materi internal, atau data teman kepada pihak yang tidak berkepentingan.'],
                ],
            ],
            'agree_privacy' => [
                'icon' => 'bi-shield-lock',
                'title' => 'Penggunaan dan perlindungan data pribadi',
                'summary' => 'Penjelasan tentang data profil siswa, penggunaan pembelajaran, dan batas akses data.',
                'sections' => [
                    ['heading' => 'Data yang digunakan', 'body' => 'SKUAD menyimpan data profil dasar, kelas, wali, perangkat, minat, presensi, tugas, nilai, refleksi, dan portofolio untuk kebutuhan pembelajaran.'],
                    ['heading' => 'Tujuan penggunaan', 'body' => 'Data digunakan untuk pendampingan siswa, pengelolaan kelas, laporan perkembangan, keamanan akun, dan peningkatan kualitas program.'],
                    ['heading' => 'Batas akses', 'body' => 'Siswa hanya melihat datanya sendiri. Guru, instruktur/coach, admin, dan kepala sekolah hanya melihat data sesuai peran dan kebutuhan pendampingan.'],
                    ['heading' => 'Perlindungan', 'body' => 'Data sensitif tidak ditampilkan publik. Publikasi karya hanya dilakukan sesuai pengaturan visibilitas dan persetujuan yang berlaku.'],
                ],
            ],
            'agree_ai_policy' => [
                'icon' => 'bi-stars',
                'title' => 'Aturan penggunaan AI secara jujur dan bertanggung jawab',
                'summary' => 'Pedoman memakai AI sebagai alat bantu, bukan pengganti proses berpikir dan kejujuran karya.',
                'sections' => [
                    ['heading' => 'AI sebagai alat bantu', 'body' => 'Siswa boleh menggunakan AI untuk mencari ide, menyusun rencana, memperbaiki bahasa, membuat contoh, atau mengecek pekerjaan sesuai arahan guru.'],
                    ['heading' => 'Kejujuran karya', 'body' => 'Siswa wajib menyebutkan bila menggunakan AI pada tugas atau portofolio, termasuk alat yang dipakai dan bagian mana yang dibantu AI.'],
                    ['heading' => 'Verifikasi', 'body' => 'Hasil AI harus diperiksa ulang. Siswa tetap bertanggung jawab atas kebenaran informasi, sumber, bahasa, dan keputusan akhir karya.'],
                    ['heading' => 'Larangan', 'body' => 'AI tidak boleh digunakan untuk menyontek, memalsukan proses, membuat karya yang melanggar hak cipta, menyebarkan hoaks, atau membuat konten yang merugikan orang lain.'],
                ],
            ],
            'agree_publication_policy' => [
                'icon' => 'bi-images',
                'title' => 'Aturan publikasi karya dan portofolio',
                'summary' => 'Pedoman karya siswa yang boleh tampil di showcase, portofolio, dan dashboard publik.',
                'sections' => [
                    ['heading' => 'Karya yang layak tampil', 'body' => 'Karya yang dipublikasikan harus aman, sopan, orisinal atau memiliki sumber yang jelas, serta tidak memuat data pribadi sensitif.'],
                    ['heading' => 'Kurasi guru/instruktur/coach', 'body' => 'Guru atau instruktur/coach dapat menyetujui, menolak, memberi catatan revisi, atau memilih karya terbaik untuk tampil publik.'],
                    ['heading' => 'Kredit dan sumber', 'body' => 'Siswa harus mencantumkan sumber gambar, audio, video, data, template, referensi, dan bantuan AI bila digunakan.'],
                    ['heading' => 'Perubahan visibilitas', 'body' => 'Karya dapat diturunkan dari tampilan publik jika melanggar aturan, memerlukan revisi, atau ada alasan keamanan/privasi.'],
                ],
            ],
        ];
    }
}

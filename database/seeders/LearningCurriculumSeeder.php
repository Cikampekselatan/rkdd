<?php

namespace Database\Seeders;

use App\Enums\LearningSessionStatus;
use App\Models\AcademicYear;
use App\Models\LearningModule;
use App\Models\LearningSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LearningCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MasterDataSeeder::class);
        $academicYear = AcademicYear::query()->where('is_active', true)->firstOrFail();

        foreach ($this->curriculum() as $moduleIndex => $moduleData) {
            $moduleNumber = $moduleIndex + 1;
            $module = LearningModule::withTrashed()->firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'module_number' => $moduleNumber],
                [
                    'title' => $moduleData['title'],
                    'slug' => Str::slug($moduleData['title']),
                    'description' => $moduleData['description'],
                    'sort_order' => $moduleNumber,
                    'is_active' => true,
                ],
            );

            if ($module->trashed()) {
                continue;
            }

            foreach ($moduleData['sessions'] as $sessionIndex => $sessionTitle) {
                $sessionNumber = (($moduleNumber - 1) * 2) + $sessionIndex + 1;
                LearningSession::withTrashed()->firstOrCreate(
                    ['academic_year_id' => $academicYear->id, 'session_number' => $sessionNumber],
                    [
                        'learning_module_id' => $module->id,
                        'semester' => $sessionNumber <= 15 ? 1 : 2,
                        'title' => $sessionTitle,
                        'slug' => Str::slug($sessionTitle),
                        'duration_minutes' => 90,
                        'objectives' => [
                            "Memahami konsep utama {$sessionTitle}.",
                            "Menerapkan {$sessionTitle} melalui aktivitas praktik.",
                        ],
                        'status' => LearningSessionStatus::Draft,
                    ],
                );
            }
        }
    }

    /** @return list<array{title: string, description: string, sessions: list<string>}> */
    private function curriculum(): array
    {
        return [
            ['title' => 'Fondasi Warga Digital', 'description' => 'Orientasi, etika, keamanan, dan jejak digital.', 'sessions' => ['Orientasi SKUAD dan Etika Digital', 'Keamanan Akun dan Jejak Digital']],
            ['title' => 'Desain Visual', 'description' => 'Prinsip desain untuk komunikasi visual yang efektif.', 'sessions' => ['Prinsip Desain dan Komposisi', 'Poster Digital dengan Canva']],
            ['title' => 'Fotografi', 'description' => 'Dasar teknis dan storytelling melalui foto.', 'sessions' => ['Dasar Fotografi dan Komposisi', 'Praktik Foto Cerita Sekolah']],
            ['title' => 'Videografi', 'description' => 'Merancang dan memproduksi video pendek.', 'sessions' => ['Perencanaan Video dan Storyboard', 'Produksi Video Pendek']],
            ['title' => 'Presentasi Efektif', 'description' => 'Menyusun pesan dan membawakan presentasi yang meyakinkan.', 'sessions' => ['Struktur Presentasi yang Meyakinkan', 'Desain dan Praktik Presentasi']],
            ['title' => 'Literasi Kecerdasan Artifisial', 'description' => 'Menggunakan AI secara kritis, etis, dan bertanggung jawab.', 'sessions' => ['Mengenal AI secara Kritis', 'Prompting, Etika, dan Verifikasi AI']],
            ['title' => 'Produktivitas Digital', 'description' => 'Kolaborasi dokumen dan pengelolaan informasi.', 'sessions' => ['Dokumen Digital Kolaboratif', 'Spreadsheet untuk Produktivitas']],
            ['title' => 'Data dan Infografis', 'description' => 'Membaca, mengolah, dan menyajikan data.', 'sessions' => ['Membaca Data dan Membuat Grafik', 'Infografis Berbasis Data']],
            ['title' => 'Dasar Coding', 'description' => 'Berpikir komputasional dan logika pemrograman.', 'sessions' => ['Berpikir Komputasional', 'Logika Coding dan Algoritma Sederhana']],
            ['title' => 'Web Kreatif', 'description' => 'Mengenal struktur dan tampilan halaman web.', 'sessions' => ['Mengenal HTML dan Struktur Web', 'Mendesain Halaman Web Sederhana']],
            ['title' => 'Kolaborasi Proyek', 'description' => 'Mengelola peran, komunikasi, dan proses kerja tim.', 'sessions' => ['Kerja Tim dan Manajemen Proyek', 'Kolaborasi Digital yang Aman']],
            ['title' => 'Kewirausahaan Digital', 'description' => 'Mengubah ide kreatif menjadi nilai dan solusi.', 'sessions' => ['Menemukan Ide dan Masalah', 'Branding dan Promosi Digital']],
            ['title' => 'Produksi Konten', 'description' => 'Merancang konten yang konsisten dan bertanggung jawab.', 'sessions' => ['Strategi Konten Digital', 'Produksi Kampanye Mini']],
            ['title' => 'Perancangan Proyek Akhir', 'description' => 'Menentukan masalah, solusi, dan rencana karya akhir.', 'sessions' => ['Eksplorasi Ide Proyek Akhir', 'Prototipe dan Uji Coba']],
            ['title' => 'Pameran Karya', 'description' => 'Menyelesaikan, mempresentasikan, dan merefleksikan karya.', 'sessions' => ['Finalisasi Proyek dan Portofolio', 'Pameran Karya dan Refleksi']],
        ];
    }
}

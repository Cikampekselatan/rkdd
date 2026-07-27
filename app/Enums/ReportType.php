<?php

namespace App\Enums;

enum ReportType: string
{
    case Students = 'students';
    case Onboarding = 'onboarding';
    case Attendance = 'attendance';
    case TeacherLogs = 'teacher-logs';
    case Assignments = 'assignments';
    case Lateness = 'lateness';
    case Grades = 'grades';
    case MonthlyAssessments = 'monthly-assessments';
    case Remedial = 'remedial';
    case Portfolio = 'portfolio';
    case ActivityDocumentations = 'activity-documentations';
    case Notes = 'notes';
    case Sessions = 'sessions';
    case Documents = 'documents';

    public function label(): string
    {
        return match ($this) {
            self::Students => 'Siswa', self::Onboarding => 'Onboarding', self::Attendance => 'Kehadiran', self::TeacherLogs => 'Absen Pengajar', self::Assignments => 'Tugas', self::Lateness => 'Keterlambatan', self::Grades => 'Nilai', self::MonthlyAssessments => 'Asesmen Bulanan', self::Remedial => 'Remedial', self::Portfolio => 'Portofolio', self::ActivityDocumentations => 'Dokumentasi Kegiatan', self::Notes => 'Catatan Penting', self::Sessions => 'Pertemuan', self::Documents => 'Dokumen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Students => 'Identitas, kelas, status, dan keanggotaan siswa.', self::Onboarding => 'Progress pendaftaran dan kelengkapan onboarding.', self::Attendance => 'Rekap kehadiran siswa per sesi dan kelas.', self::TeacherLogs => 'Aktivitas dan verifikasi absen pengajar.', self::Assignments => 'Daftar tugas, tenggat, dan jumlah pengumpulan.', self::Lateness => 'Pengumpulan tugas yang melewati tenggat.', self::Grades => 'Nilai published dan level pencapaian siswa.', self::MonthlyAssessments => 'Asesmen berkala per bulan, semester, dan program.', self::Remedial => 'Status, tenggat, dan penyelesaian remedial.', self::Portfolio => 'Karya, visibilitas, approval, dan featured.', self::ActivityDocumentations => 'Dokumentasi foto, URL, dan video kegiatan.', self::Notes => 'Catatan penting dan status penyelesaiannya.', self::Sessions => 'Progress pertemuan dan status publikasi.', self::Documents => 'Dokumen aktif sesuai audience dan semester.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Students => 'bi-people', self::Onboarding => 'bi-person-check', self::Attendance => 'bi-calendar2-check', self::TeacherLogs => 'bi-journal-text', self::Assignments => 'bi-clipboard-check', self::Lateness => 'bi-clock-history', self::Grades => 'bi-award', self::MonthlyAssessments => 'bi-clipboard2-data', self::Remedial => 'bi-arrow-repeat', self::Portfolio => 'bi-images', self::ActivityDocumentations => 'bi-camera', self::Notes => 'bi-exclamation-diamond', self::Sessions => 'bi-journal-bookmark', self::Documents => 'bi-folder2-open',
        };
    }
}

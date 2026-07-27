<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Students = 'students';
    case Teachers = 'teachers';
    case ClassRoom = 'class';
    case Session = 'session';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Semua pengguna', self::Students => 'Siswa', self::Teachers => 'Pembina dan staff',
            self::ClassRoom => 'Kelas tertentu', self::Session => 'Pertemuan tertentu',
        };
    }
}

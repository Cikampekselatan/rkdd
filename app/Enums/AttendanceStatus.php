<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Sick = 'sick';
    case Permitted = 'permitted';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Sick => 'Sakit',
            self::Permitted => 'Izin',
            self::Absent => 'Alpa',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Present => 'bi-check-circle-fill',
            self::Late => 'bi-clock-fill',
            self::Sick => 'bi-heart-pulse-fill',
            self::Permitted => 'bi-envelope-check-fill',
            self::Absent => 'bi-x-circle-fill',
        };
    }

    public function countsAsAttended(): bool
    {
        return in_array($this, [self::Present, self::Late], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

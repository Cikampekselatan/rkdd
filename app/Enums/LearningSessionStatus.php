<?php

namespace App\Enums;

enum LearningSessionStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::Published => 'Dipublikasikan',
            self::Ongoing => 'Berlangsung',
            self::Completed => 'Selesai',
            self::Archived => 'Diarsipkan',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Published, self::Completed => 'success',
            self::Ongoing => 'info',
            self::Scheduled => 'warning',
            self::Archived => 'neutral',
            self::Draft => 'neutral',
        };
    }

    public function isVisibleToStudents(): bool
    {
        return in_array($this, [self::Published, self::Ongoing, self::Completed], true);
    }

    /** @return list<string> */
    public static function studentVisibleValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            [self::Published, self::Ongoing, self::Completed],
        );
    }
}

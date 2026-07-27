<?php

namespace App\Enums;

enum PortfolioVisibility: string
{
    case Private = 'private';
    case TeacherOnly = 'teacher_only';
    case ClassRoom = 'class';
    case School = 'school';
    case PublicApproved = 'public_approved';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privat',self::TeacherOnly => 'Pembina saja',self::ClassRoom => 'Kelas',self::School => 'Sekolah',self::PublicApproved => 'Publik disetujui'
        };
    }

    public function requiresApproval(): bool
    {
        return in_array($this, [self::ClassRoom, self::School, self::PublicApproved], true);
    }
}

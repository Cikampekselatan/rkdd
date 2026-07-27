<?php

namespace App\Enums;

use App\Models\User;

enum DocumentAudience: string
{
    case StaffOnly = 'staff_only';
    case TeacherCoach = 'teacher_coach';
    case AllStaff = 'all_staff';
    case Students = 'students';
    case InternalPublic = 'internal_public';

    public function label(): string
    {
        return match ($this) {
            self::StaffOnly => 'Pengelola Dokumen',
            self::TeacherCoach => 'Guru dan Instruktur/Coach',
            self::AllStaff => 'Seluruh Staff',
            self::Students => 'Siswa',
            self::InternalPublic => 'Internal Sekolah',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::StaffOnly => 'Hanya admin dan guru pengelola.',
            self::TeacherCoach => 'Guru, instruktur/coach, dan super-admin.',
            self::AllStaff => 'Semua role staff sekolah.',
            self::Students => 'Siswa serta staff pendamping.',
            self::InternalPublic => 'Semua pengguna yang sudah login.',
        };
    }

    /** @return list<string> */
    public static function visibleValuesFor(User $user): array
    {
        if ($user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Teacher])) {
            return array_column(self::cases(), 'value');
        }

        if ($user->hasRole(RoleSlug::Coach)) {
            return [self::TeacherCoach->value, self::AllStaff->value, self::Students->value, self::InternalPublic->value];
        }

        if ($user->hasRole(RoleSlug::Principal)) {
            return [self::AllStaff->value, self::Students->value, self::InternalPublic->value];
        }

        if ($user->hasRole(RoleSlug::Student)) {
            return [self::Students->value, self::InternalPublic->value];
        }

        return [];
    }
}

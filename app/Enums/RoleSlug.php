<?php

namespace App\Enums;

enum RoleSlug: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Coach = 'coach';
    case Student = 'student';
    case Principal = 'principal';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin Sekolah',
            self::Teacher => 'Pembina/Guru',
            self::Coach => 'Instruktur/Coach',
            self::Student => 'Siswa',
            self::Principal => 'Kepala Sekolah',
        };
    }

    public function dashboardRouteName(): string
    {
        return $this->value.'.dashboard';
    }

    /**
     * @return list<self>
     */
    public static function staffRoles(): array
    {
        return [
            self::SuperAdmin,
            self::Admin,
            self::Teacher,
            self::Coach,
            self::Principal,
        ];
    }
}

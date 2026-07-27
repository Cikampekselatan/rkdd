<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }
}

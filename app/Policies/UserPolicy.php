<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function view(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->hasRole(RoleSlug::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function update(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->hasRole(RoleSlug::Admin);
    }

    public function delete(User $user, User $subject): bool
    {
        return false;
    }

    public function assignRole(User $user): bool
    {
        return false;
    }

    public function manageStaff(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function viewStudents(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Admin, RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function changeStudentStatus(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function viewStudentDashboard(User $user): bool
    {
        return $user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active;
    }
}

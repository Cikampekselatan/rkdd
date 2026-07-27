<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Assignment;
use App\Models\User;
use App\Services\ProgramContextService;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]) || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->isInActiveProgram($user, $assignment->program_batch_id);
        }

        $membership = app(ProgramContextService::class)->studentActiveMembership($user);

        return $user->hasRole(RoleSlug::Student)
            && $user->status === UserStatus::Active
            && $assignment->is_published
            && (! $assignment->available_from || $assignment->available_from->isPast())
            && $membership
            && $membership->academic_year_id === $assignment->academic_year_id
            && $membership->class_id === $assignment->class_id
            && ($assignment->program_batch_id === null || $membership->program_batch_id === $assignment->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $assignment->program_batch_id);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $assignment->program_batch_id)
            && ! $assignment->submissions()->exists();
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

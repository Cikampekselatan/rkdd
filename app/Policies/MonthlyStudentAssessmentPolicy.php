<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\MonthlyStudentAssessment;
use App\Models\User;
use App\Services\ProgramContextService;

class MonthlyStudentAssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function view(User $user, MonthlyStudentAssessment $assessment): bool
    {
        if ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->isInActiveProgram($user, $assessment->program_batch_id);
        }

        $membership = app(ProgramContextService::class)->studentActiveMembership($user);

        return
                $user->hasRole(RoleSlug::Student)
                && $user->status === UserStatus::Active
                && $assessment->is_published
                && $assessment->user_id === $user->id
                && ($assessment->program_batch_id === null || $membership?->program_batch_id === $assessment->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, MonthlyStudentAssessment $assessment): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $assessment->program_batch_id);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

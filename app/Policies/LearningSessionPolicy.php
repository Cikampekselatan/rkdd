<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\LearningSession;
use App\Models\User;
use App\Services\ProgramContextService;

class LearningSessionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function view(User $user, LearningSession $learningSession): bool
    {
        if ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->isInActiveProgram($user, $learningSession->program_batch_id);
        }

        $membership = app(ProgramContextService::class)->studentActiveMembership($user);
        $legacyClass = $user->studentProfile?->schoolClass;

        return $user->hasRole(RoleSlug::Student)
            && $user->status === UserStatus::Active
            && $learningSession->status->isVisibleToStudents()
            && (
                ($membership
                    && $membership->academic_year_id === $learningSession->academic_year_id
                    && ($learningSession->program_batch_id === null || $membership->program_batch_id === $learningSession->program_batch_id))
                || (! $membership
                    && $legacyClass?->academic_year_id === $learningSession->academic_year_id
                    && ($learningSession->program_batch_id === null || $legacyClass->program_batch_id === $learningSession->program_batch_id))
            );
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, LearningSession $learningSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningSession->program_batch_id);
    }

    public function delete(User $user, LearningSession $learningSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningSession->program_batch_id);
    }

    public function preview(User $user, LearningSession $learningSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningSession->program_batch_id);
    }

    public function publish(User $user, LearningSession $learningSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningSession->program_batch_id);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

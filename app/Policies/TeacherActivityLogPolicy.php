<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\TeacherActivityLog;
use App\Models\User;
use App\Services\ProgramContextService;

class TeacherActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(RoleSlug::staffRoles());
    }

    public function view(User $user, TeacherActivityLog $log): bool
    {
        if (! $this->isInActiveProgram($user, $log->program_batch_id)) {
            return false;
        }

        if ($user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Principal])) {
            return $log->status->value === 'verified';
        }

        if ($log->teacher_id === $user->id) {
            return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
        }

        if (! in_array($log->status->value, ['submitted', 'verified'], true)) {
            return false;
        }

        return $user->hasRole(RoleSlug::Teacher) && $log->teacher?->hasRole(RoleSlug::Coach);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Coach);
    }

    public function update(User $user, TeacherActivityLog $log): bool
    {
        return $user->hasRole(RoleSlug::Coach)
            && $this->isInActiveProgram($user, $log->program_batch_id)
            && $log->teacher_id === $user->id
            && $log->isEditable();
    }

    public function submit(User $user, TeacherActivityLog $log): bool
    {
        return $this->update($user, $log);
    }

    public function review(User $user, TeacherActivityLog $log): bool
    {
        if (! $this->isInActiveProgram($user, $log->program_batch_id) || $log->status->value !== 'submitted' || $log->teacher_id === $user->id) {
            return false;
        }

        return $user->hasRole(RoleSlug::Teacher) && $log->teacher?->hasRole(RoleSlug::Coach);
    }

    public function downloadSignature(User $user, TeacherActivityLog $log): bool
    {
        return $this->view($user, $log);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

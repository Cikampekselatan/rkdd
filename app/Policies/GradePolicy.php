<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Grade;
use App\Models\User;
use App\Services\ProgramContextService;

class GradePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]) || ($u->hasRole(RoleSlug::Student) && $u->status === UserStatus::Active);
    }

    public function view(User $u, Grade $g): bool
    {
        $programBatchId = $g->submission?->assignment?->program_batch_id;

        if ($u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->isInActiveProgram($u, $programBatchId);
        }

        $membership = app(ProgramContextService::class)->studentActiveMembership($u);

        return $u->hasRole(RoleSlug::Student)
            && $u->status === UserStatus::Active
            && $g->is_published
            && $g->submission->user_id === $u->id
            && ($programBatchId === null || $membership?->program_batch_id === $programBatchId);
    }

    public function manage(User $u, Grade $g): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $g->submission?->assignment?->program_batch_id);
    }

    public function update(User $u, Grade $g): bool
    {
        return $this->manage($u, $g);
    }

    public function completeRemedial(User $u, Grade $g): bool
    {
        return $this->manage($u, $g);
    }

    public function submitRemedial(User $u, Grade $g): bool
    {
        $programBatchId = $g->submission?->assignment?->program_batch_id;
        $membership = app(ProgramContextService::class)->studentActiveMembership($u);

        return $u->hasRole(RoleSlug::Student)
            && $u->status === UserStatus::Active
            && $g->is_published
            && $g->submission->user_id === $u->id
            && ($programBatchId === null || $membership?->program_batch_id === $programBatchId)
            && $g->remedial_status->value === 'assigned';
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

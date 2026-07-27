<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\ProgramContextService;

class LearningModulePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function view(User $user, LearningModule $learningModule): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningModule->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, LearningModule $learningModule): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningModule->program_batch_id);
    }

    public function delete(User $user, LearningModule $learningModule): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningModule->program_batch_id);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

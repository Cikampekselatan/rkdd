<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Services\ProgramContextService;

class LearningMaterialPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function update(User $user, LearningMaterial $learningMaterial): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningMaterial->learningSession?->program_batch_id);
    }

    public function delete(User $user, LearningMaterial $learningMaterial): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $learningMaterial->learningSession?->program_batch_id);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

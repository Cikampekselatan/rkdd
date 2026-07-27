<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Rubric;
use App\Models\User;
use App\Services\ProgramContextService;

class RubricPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function view(User $u, Rubric $r): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $r->program_batch_id);
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $u, Rubric $r): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $r->program_batch_id);
    }

    public function delete(User $u, Rubric $r): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $r->program_batch_id)
            && ! $r->assignments()->exists();
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

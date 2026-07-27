<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\ActivityDocumentation;
use App\Models\User;
use App\Services\ProgramContextService;

class ActivityDocumentationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ActivityDocumentation $documentation): bool
    {
        return $user->isStaff() && $this->matchesActiveProgram($user, $documentation->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, ActivityDocumentation $documentation): bool
    {
        return $this->create($user)
            && $documentation->created_by === $user->id
            && $this->matchesActiveProgram($user, $documentation->program_batch_id);
    }

    public function delete(User $user, ActivityDocumentation $documentation): bool
    {
        return $this->update($user, $documentation);
    }

    private function matchesActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\ShowcaseHighlight;
use App\Models\User;
use App\Services\ProgramContextService;

class ShowcaseHighlightPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, ShowcaseHighlight $showcaseHighlight): bool
    {
        return $this->manage($user) && $this->isInActiveProgram($user, $showcaseHighlight->program_batch_id);
    }

    public function delete(User $user, ShowcaseHighlight $showcaseHighlight): bool
    {
        return $this->manage($user) && $this->isInActiveProgram($user, $showcaseHighlight->program_batch_id);
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([
            RoleSlug::SuperAdmin,
            RoleSlug::Admin,
            RoleSlug::Teacher,
            RoleSlug::Coach,
        ]);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

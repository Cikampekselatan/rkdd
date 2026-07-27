<?php

namespace App\Policies;

use App\Enums\ImportantNoteStatus;
use App\Enums\RoleSlug;
use App\Models\ImportantNote;
use App\Models\User;
use App\Services\ProgramContextService;

class ImportantNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(RoleSlug::staffRoles());
    }

    public function view(User $user, ImportantNote $note): bool
    {
        if (! $this->isInActiveProgram($user, $note->program_batch_id)) {
            return false;
        }

        if ($user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Principal])) {
            return $note->status === ImportantNoteStatus::Verified;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Coach);
    }

    public function update(User $user, ImportantNote $note): bool
    {
        return $user->hasRole(RoleSlug::Coach)
            && $this->isInActiveProgram($user, $note->program_batch_id)
            && $note->status !== ImportantNoteStatus::Verified;
    }

    public function sign(User $user, ImportantNote $note): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $note->program_batch_id)
            && $note->status !== ImportantNoteStatus::Verified;
    }

    public function downloadInitial(User $user, ImportantNote $note): bool
    {
        return $this->view($user, $note);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

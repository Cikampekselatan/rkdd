<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\RegistrationCode;
use App\Models\User;
use App\Services\ProgramContextService;

class RegistrationCodePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleSlug::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function view(User $user, RegistrationCode $registrationCode): bool
    {
        return $user->hasRole(RoleSlug::Admin)
            && $this->isInActiveProgram($user, $registrationCode->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    public function update(User $user, RegistrationCode $registrationCode): bool
    {
        return $user->hasRole(RoleSlug::Admin)
            && $this->isInActiveProgram($user, $registrationCode->program_batch_id);
    }

    public function delete(User $user, RegistrationCode $registrationCode): bool
    {
        return $user->hasRole(RoleSlug::Admin)
            && $this->isInActiveProgram($user, $registrationCode->program_batch_id);
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

<?php

namespace App\Policies;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Services\ProgramContextService;

class PortfolioItemPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach, RoleSlug::Student]);
    }

    public function view(User $u, PortfolioItem $item): bool
    {
        if ($u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->isInActiveProgram($u, $item->program_batch_id);
        }

        if ($item->user_id === $u->id) {
            return $this->studentCanAccessProgram($u, $item->program_batch_id);
        }if (! $u->hasRole(RoleSlug::Student) || $u->status !== UserStatus::Active) {
            return false;
        }if ($item->visibility === PortfolioVisibility::TeacherOnly || $item->visibility === PortfolioVisibility::Private) {
            return false;
        }if ($item->approval_status !== PortfolioApprovalStatus::Approved) {
            return false;
        }if ($item->visibility === PortfolioVisibility::ClassRoom) {
            return $u->classMemberships()
                ->where('class_id', $item->class_id)
                ->when($item->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('status', 'active')
                ->exists();
        }

        return $this->studentCanAccessProgram($u, $item->program_batch_id)
            && in_array($item->visibility, [PortfolioVisibility::School, PortfolioVisibility::PublicApproved], true);
    }

    public function create(User $u): bool
    {
        return $u->hasRole(RoleSlug::Student) && $u->status === UserStatus::Active;
    }

    public function update(User $u, PortfolioItem $item): bool
    {
        return $u->hasRole(RoleSlug::Student)
            && $item->user_id === $u->id
            && $this->studentCanAccessProgram($u, $item->program_batch_id);
    }

    public function delete(User $u, PortfolioItem $item): bool
    {
        return $this->update($u, $item);
    }

    public function approve(User $u, PortfolioItem $item): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $item->program_batch_id)
            && $item->visibility->requiresApproval();
    }

    public function feature(User $u, PortfolioItem $item): bool
    {
        return $u->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($u, $item->program_batch_id)
            && $item->approval_status === PortfolioApprovalStatus::Approved;
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }

    private function studentCanAccessProgram(User $user, ?int $programBatchId): bool
    {
        $membership = app(ProgramContextService::class)->studentActiveMembership($user);

        return $programBatchId === null || $membership?->program_batch_id === $programBatchId;
    }
}

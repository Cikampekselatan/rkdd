<?php

namespace App\Policies;

use App\Enums\AnnouncementAudience;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\User;
use App\Services\ProgramContextService;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach, RoleSlug::Student]);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return $this->matchesActiveProgram($user, $announcement->program_batch_id);
        }
        if (! $user->hasRole(RoleSlug::Student) || $user->status !== UserStatus::Active || ! $announcement->is_published || ($announcement->published_at && $announcement->published_at->isFuture()) || ($announcement->expires_at && $announcement->expires_at->isPast())) {
            return false;
        }

        if (! $this->matchesStudentProgram($user, $announcement->program_batch_id)) {
            return false;
        }

        return match ($announcement->audience) {
            AnnouncementAudience::All, AnnouncementAudience::Students => true,
            AnnouncementAudience::ClassRoom => $user->classMemberships()
                ->where('class_id', $announcement->class_id)
                ->where('status', 'active')
                ->when($announcement->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->exists(),
            AnnouncementAudience::Session => $user->classMemberships()
                ->where('status', 'active')
                ->where('academic_year_id', $announcement->learningSession?->academic_year_id)
                ->when($announcement->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->exists(),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->matchesActiveProgram($user, $announcement->program_batch_id);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    private function matchesActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }

    private function matchesStudentProgram(User $user, ?int $programBatchId): bool
    {
        if ($programBatchId === null) {
            return true;
        }

        return app(ProgramContextService::class)->studentActiveMembership($user)?->program_batch_id === $programBatchId;
    }
}

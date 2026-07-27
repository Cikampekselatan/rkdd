<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\ProgramContextService;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        $programBatchId = $attendanceRecord->attendanceSession?->program_batch_id;

        return ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
                && $this->isInActiveProgram($user, $programBatchId))
            || ($user->hasRole(RoleSlug::Student)
                && $user->status === UserStatus::Active
                && $attendanceRecord->user_id === $user->id
                && $this->studentCanAccessProgram($user, $programBatchId));
    }

    public function amend(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $attendanceRecord->attendanceSession?->program_batch_id)
            && ! $attendanceRecord->attendanceSession->isOpen();
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }

    private function studentCanAccessProgram(User $user, ?int $programBatchId): bool
    {
        if ($programBatchId === null) {
            return true;
        }

        return app(ProgramContextService::class)->studentActiveMembership($user)?->program_batch_id === $programBatchId;
    }
}

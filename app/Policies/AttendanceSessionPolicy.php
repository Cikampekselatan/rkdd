<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Services\ProgramContextService;

class AttendanceSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $attendanceSession->program_batch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]);
    }

    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $attendanceSession->program_batch_id)
            && $attendanceSession->isOpen();
    }

    public function close(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $attendanceSession->program_batch_id)
            && $attendanceSession->isOpen();
    }

    private function isInActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}

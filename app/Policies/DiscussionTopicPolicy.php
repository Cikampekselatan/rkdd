<?php

namespace App\Policies;

use App\Enums\DiscussionStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\DiscussionTopic;
use App\Models\User;
use App\Services\ProgramContextService;

class DiscussionTopicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->hasRole(RoleSlug::Student);
    }

    public function view(User $user, DiscussionTopic $topic): bool
    {
        if ($user->isStaff()) {
            return $this->matchesActiveProgram($user, $topic->program_batch_id);
        }

        return $user->hasRole(RoleSlug::Student)
            && $user->status === UserStatus::Active
            && ! $topic->is_hidden
            && $this->matchesStudentProgram($user, $topic->program_batch_id)
            && $user->classMemberships()
                ->where('class_id', $topic->class_id)
                ->where('status', 'active')
                ->when($topic->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isStaff() || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function reply(User $user, DiscussionTopic $topic): bool
    {
        return $topic->status === DiscussionStatus::Open && $this->view($user, $topic);
    }

    public function moderate(User $user, DiscussionTopic $topic): bool
    {
        return $user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Teacher, RoleSlug::Coach])
            && $this->matchesActiveProgram($user, $topic->program_batch_id);
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

<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use App\Services\ProgramContextService;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        $programBatchId = $submission->assignment?->program_batch_id;

        return ($user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach]) && $this->isInActiveProgram($user, $programBatchId))
            || ($user->hasRole(RoleSlug::Student)
                && $submission->user_id === $user->id
                && $this->studentCanAccessProgram($user, $programBatchId));
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->hasRole(RoleSlug::Student)
            && $submission->user_id === $user->id
            && $this->studentCanAccessProgram($user, $submission->assignment?->program_batch_id)
            && in_array($submission->status, [SubmissionStatus::Draft, SubmissionStatus::RevisionRequested], true);
    }

    public function review(User $user, Submission $submission): bool
    {
        return $user->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])
            && $this->isInActiveProgram($user, $submission->assignment?->program_batch_id)
            && in_array($submission->status, [SubmissionStatus::Submitted, SubmissionStatus::Late, SubmissionStatus::Resubmitted, SubmissionStatus::UnderReview], true);
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

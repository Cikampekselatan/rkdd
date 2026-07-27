<?php

namespace App\Policies;

use App\Models\SubmissionFile;
use App\Models\User;

class SubmissionFilePolicy
{
    public function view(User $user, SubmissionFile $file): bool
    {
        return $user->can('view', $file->version->submission);
    }
}

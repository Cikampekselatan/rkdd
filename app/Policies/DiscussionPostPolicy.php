<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\DiscussionPost;
use App\Models\User;

class DiscussionPostPolicy
{
    public function report(User $user, DiscussionPost $post): bool
    {
        return ! $post->is_hidden && $post->user_id !== $user->id && $user->can('view', $post->topic);
    }

    public function moderate(User $user, DiscussionPost $post): bool
    {
        return $user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Teacher, RoleSlug::Coach])
            && $user->can('moderate', $post->topic);
    }
}

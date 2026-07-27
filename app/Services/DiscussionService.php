<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\DiscussionPost;
use App\Models\DiscussionTopic;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\DiscussionReplyNotification;
use Illuminate\Support\Facades\DB;

class DiscussionService
{
    public function createTopic(array $data, User $actor): DiscussionTopic
    {
        if ($actor->hasRole(RoleSlug::Student)) {
            $membership = app(ProgramContextService::class)->studentActiveMembership($actor);
            $data['class_id'] = $membership?->class_id;
        }
        $class = SchoolClass::findOrFail($data['class_id']);
        $data['academic_year_id'] = $class->academic_year_id;
        $data['program_batch_id'] = $class->program_batch_id;

        return DiscussionTopic::create([...$data, 'created_by' => $actor->id]);
    }

    public function post(DiscussionTopic $topic, array $data, User $actor): DiscussionPost
    {
        return DB::transaction(function () use ($topic, $data, $actor): DiscussionPost {
            $parentId = $data['parent_id'] ?? null;
            $post = $topic->posts()->create([...$data, 'parent_id' => $parentId, 'user_id' => $actor->id]);
            $recipient = $parentId ? DiscussionPost::find($parentId)?->author : $topic->author;
            if ($recipient && $recipient->id !== $actor->id) {
                $recipient->notify(new DiscussionReplyNotification($post->load(['topic', 'author'])));
            }

            return $post;
        });
    }

    public function report(DiscussionPost $post, string $reason, User $actor): void
    {
        $post->reports()->firstOrCreate(['reported_by' => $actor->id], ['reason' => $reason]);
    }

    public function moderatePost(DiscussionPost $post, User $actor): void
    {
        $hidden = ! $post->is_hidden;
        $post->update(['is_hidden' => $hidden, 'hidden_by' => $hidden ? $actor->id : null, 'hidden_at' => $hidden ? now() : null]);
        if ($hidden) {
            $post->reports()->whereNull('resolved_at')->update(['resolved_by' => $actor->id, 'resolved_at' => now()]);
        }
    }

    public function toggleTopic(DiscussionTopic $topic, string $action, User $actor): void
    {
        if ($action === 'pin') {
            $topic->update(['is_pinned' => ! $topic->is_pinned]);
        } elseif ($action === 'close') {
            $topic->update(['status' => $topic->status->value === 'open' ? 'closed' : 'open']);
        } else {
            $hidden = ! $topic->is_hidden;
            $topic->update(['is_hidden' => $hidden, 'hidden_by' => $hidden ? $actor->id : null, 'hidden_at' => $hidden ? now() : null]);
        }
    }
}

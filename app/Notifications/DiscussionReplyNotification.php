<?php

namespace App\Notifications;

use App\Models\DiscussionPost;
use App\Services\AnnouncementService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DiscussionReplyNotification extends Notification
{
    use Queueable;

    public function __construct(public DiscussionPost $post) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'discussion_reply',
            'topic_id' => $this->post->topic_id,
            'title' => $this->post->topic->title,
            'author' => $this->post->author->name,
            'url' => route('interactions.discussions.show', $this->post->topic_id),
            ...AnnouncementService::programMeta($this->post->topic->program_batch_id),
        ];
    }
}

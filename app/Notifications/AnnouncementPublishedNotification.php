<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'announcement',
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'priority' => $this->announcement->priority->value,
            'url' => route('interactions.announcements.show', $this->announcement),
            ...AnnouncementService::programMeta($this->announcement->program_batch_id),
        ];
    }
}
